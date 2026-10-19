import { Platform } from "react-native";
import * as SecureStore from "expo-secure-store";

const host =
  Platform.OS === "web"
    ? window.location.hostname
    : Platform.OS === "android"
      ? "10.0.2.2"
      : "127.0.0.1";
export const API_URL = (
  process.env.EXPO_PUBLIC_API_URL ||
  (Platform.OS === "web" && window.location.port !== "8081"
    ? window.location.origin
    : `http://${host}:8080`)
).replace(/\/$/, "");
let csrf = "";
let token: string | null = null;
let expired: (() => void) | undefined;
export function onSessionExpired(callback: () => void) {
  expired = callback;
}
export function documentUrl(
  userId: number,
  scholarshipId: number,
  index: number,
) {
  return `${API_URL}/api.php?action=document&user_id=${userId}&scholarship_id=${scholarshipId}&file=${index}`;
}
export function documentHeaders(): Record<string, string> {
  return token ? { Authorization: `Bearer ${token}` } : {};
}
export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
  ) {
    super(message);
  }
}
export async function api<T = any>(
  action: string,
  body?: Record<string, unknown> | FormData,
  query: Record<string, number | string> = {},
): Promise<T> {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 45000);
  const headers: Record<string, string> = {
    Accept: "application/json",
    ...documentHeaders(),
  };
  if (Platform.OS !== "web") headers["X-Portal-Native"] = "1";
  if (body) headers["X-CSRF-Token"] = csrf;
  const multipart = body instanceof FormData;
  if (body && !multipart) headers["Content-Type"] = "application/json";
  const params = new URLSearchParams({
    action,
    ...Object.fromEntries(
      Object.entries(query).map(([k, v]) => [k, String(v)]),
    ),
  });
  try {
    const response = await fetch(`${API_URL}/api.php?${params}`, {
      method: body ? "POST" : "GET",
      credentials: "include",
      headers,
      body: body
        ? multipart
          ? (body as FormData)
          : JSON.stringify(body)
        : undefined,
      signal: controller.signal,
    });
    const text = await response.text();
    let data: any;
    try {
      data = JSON.parse(text);
    } catch {
      throw new ApiError(
        "The portal could not complete this request. Please try again.",
        response.status,
      );
    }
    if (!response.ok || !data.success) {
      if (response.status === 401 && action !== "login") {
        token = null;
        if (Platform.OS !== "web")
          await SecureStore.deleteItemAsync("portal-session").catch(() => {});
        expired?.();
      }
      throw new ApiError(
        data.message || "Something went wrong. Please try again.",
        response.status,
      );
    }
    if (data.csrf_token) csrf = data.csrf_token;
    if (data.token && Platform.OS !== "web") {
      token = data.token;
      await SecureStore.setItemAsync("portal-session", token!).catch(() => {});
    }
    if (action === "logout") {
      token = null;
      if (Platform.OS !== "web")
        await SecureStore.deleteItemAsync("portal-session").catch(() => {});
    }
    return data as T;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    throw new ApiError(
      "Could not reach the portal. Check your connection and try again.",
      0,
    );
  } finally {
    clearTimeout(timeout);
  }
}
export async function restoreSession() {
  if (Platform.OS !== "web")
    token = await SecureStore.getItemAsync("portal-session").catch(() => null);
  try {
    return await api("session");
  } catch (error) {
    if (error instanceof ApiError && error.status === 401)
      return api("session");
    throw error;
  }
}
