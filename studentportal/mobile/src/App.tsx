import React, { useCallback, useEffect, useMemo, useState } from "react";
import {
  Href,
  useLocalSearchParams,
  usePathname,
  useRouter,
} from "expo-router";
import { useSession } from "./session";
import { useSidebar } from "./sidebar";
import {
  Image,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  Switch,
  View,
  useWindowDimensions,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import { StatusBar } from "expo-status-bar";
import { api } from "./api";
import {
  Button,
  Card,
  Field,
  Icon,
  IconName,
  Loader,
  Notice,
  Select,
  styles,
  Txt,
} from "./components";
import {
  Applications,
  Apply,
  Contact,
  Dashboard,
  FAQ,
  Inbox,
  Requirements,
  Scholarships,
} from "./screens";
import { useTheme } from "./theme";
import { Page, Route, User } from "./types";

const logo = require("../assets/plmun-logo.png");
const titles: Record<Page, string> = {
  home: "Overview",
  scholarships: "Scholarships",
  apply: "Apply",
  applications: "My applications",
  faq: "FAQs",
  contact: "Contact",
  admin: "Applications",
  inbox: "Messages",
  requirements: "Requirements",
};
type PortalPage = Page | "index" | "login" | "admin-login" | "register";
function Brand({ inverse = false }: { inverse?: boolean }) {
  const { colors } = useTheme();
  return (
    <View style={[styles.row, { flexWrap: "nowrap", gap: 12 }]}>
      <Image
        source={logo}
        style={{ width: 43, height: 43 }}
        accessibilityLabel="PLMun logo"
      />
      <View>
        <Txt
          bold
          size={18}
          style={{
            color: inverse ? "#F5F5E8" : colors.text,
            letterSpacing: 0.4,
          }}
        >
          PLMun
        </Txt>
        <Txt
          size={9}
          bold
          style={{
            color: inverse ? "#B6CEBD" : colors.muted,
            letterSpacing: 1.8,
          }}
        >
          SCHOLARSHIP PORTAL
        </Txt>
      </View>
    </View>
  );
}
function ThemeToggle({
  compact = false,
  inverse = false,
}: {
  compact?: boolean;
  inverse?: boolean;
}) {
  const { colors, dark, toggle } = useTheme();
  return (
    <View style={[styles.row, { gap: 9, flexWrap: "nowrap" }]}>
      <Icon
        name={dark ? "moon" : "sun"}
        size={17}
        color={inverse ? "#C4D9CB" : colors.muted}
      />
      {!compact && (
        <Txt size={13} style={{ color: inverse ? "#C4D9CB" : colors.muted }}>
          Dark mode
        </Txt>
      )}
      <Switch
        accessibilityLabel="Dark mode"
        value={dark}
        onValueChange={toggle}
        trackColor={{ false: "#899C8B", true: "#458F68" }}
        thumbColor={dark ? "#D5EDDC" : "#FFFFFF"}
      />
    </View>
  );
}

function Auth({ onLogin }: { onLogin: (user: User) => void }) {
  const { colors } = useTheme();
  const { width } = useWindowDimensions();
  const router = useRouter();
  const pathname = usePathname();
  const params = useLocalSearchParams<{ type?: string }>();
  const register = pathname === "/register";
  const [type, setType] = useState(
    pathname === "/admin-login" || params.type === "admin"
      ? "admin"
      : "student",
  );
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [visible, setVisible] = useState(false);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const { authNotice: notice, setAuthNotice: setNotice } = useSession();
  const submit = async () => {
    setError("");
    setBusy(true);
    try {
      if (register) {
        const result = await api("register", {
          name,
          email,
          password,
          account_type: type,
        });
        setPassword("");
        setNotice(result.message);
        router.replace(
          result.login_type === "admin" ? "/admin-login" : "/login",
        );
      } else {
        const result = await api("login", {
          email,
          password,
          account_type: type,
        });
        setPassword("");
        onLogin(result.user);
      }
    } catch (error) {
      setError((error as Error).message);
    } finally {
      setBusy(false);
    }
  };
  return (
    <KeyboardAvoidingView
      style={{ flex: 1 }}
      behavior={Platform.OS === "ios" ? "padding" : undefined}
    >
      <ScrollView
        keyboardShouldPersistTaps="handled"
        contentContainerStyle={{
          flexGrow: 1,
          backgroundColor: colors.background,
        }}
      >
        <View
          style={{
            padding: 24,
            flexDirection: "row",
            alignItems: "center",
            justifyContent: "space-between",
            gap: 16,
          }}
        >
          <Brand />
          <ThemeToggle compact />
        </View>
        <View
          style={{
            flexGrow: 1,
            flexDirection: width >= 1000 ? "row" : "column",
            maxWidth: 1320,
            width: "100%",
            alignSelf: "center",
            padding: width < 600 ? 20 : 40,
            gap: width >= 1000 ? 80 : 24,
            alignItems: "center",
            justifyContent: "center",
          }}
        >
          {width >= 1000 && (
            <View style={{ flex: 1, padding: 16, gap: 26 }}>
              <View
                style={{
                  alignSelf: "flex-start",
                  backgroundColor: colors.soft,
                  paddingVertical: 8,
                  paddingHorizontal: 14,
                  borderRadius: 30,
                }}
              >
                <Txt
                  size={11}
                  bold
                  style={{ letterSpacing: 1.6, color: colors.primary }}
                >
                  OPPORTUNITY STARTS HERE
                </Txt>
              </View>
              <Txt
                bold
                size={54}
                style={{ lineHeight: 62, letterSpacing: -2, maxWidth: 540 }}
              >
                A brighter future,{"\n"}one opportunity{"\n"}at a time.
              </Txt>
              <Txt muted size={17} style={{ maxWidth: 440 }}>
                Your home for scholarship opportunities at Pamantasan ng Lungsod
                ng Muntinlupa.
              </Txt>
              <View
                style={{
                  height: 3,
                  width: 64,
                  backgroundColor: colors.accent,
                  borderRadius: 2,
                }}
              />
              {[
                ["award", "Explore the right scholarship"],
                ["file-text", "Submit requirements with ease"],
                ["trending-up", "Follow your application’s progress"],
              ].map(([icon, text]) => (
                <View style={styles.row} key={icon}>
                  <View
                    style={{
                      padding: 10,
                      borderRadius: 12,
                      backgroundColor: colors.soft,
                    }}
                  >
                    <Icon name={icon as IconName} color={colors.primary} />
                  </View>
                  <Txt size={14}>{text}</Txt>
                </View>
              ))}
            </View>
          )}
          <Card
            style={{
              width: "100%",
              maxWidth: 460,
              padding: width < 600 ? 24 : 36,
              gap: 20,
            }}
          >
            <View style={{ gap: 6 }}>
              <Txt muted bold size={11} style={{ letterSpacing: 1.8 }}>
                {register ? "JOIN THE PORTAL" : "WELCOME BACK"}
              </Txt>
              <Txt size={29} bold style={{ letterSpacing: -0.8 }}>
                {register
                  ? "Create your account"
                  : type === "admin"
                    ? "Admin / staff login"
                    : "Student login"}
              </Txt>
              <Txt muted size={14}>
                {register
                  ? "Start your scholarship journey with PLMun."
                  : "Sign in to continue to your portal."}
              </Txt>
            </View>
            {register ? (
              <Select
                label="Registering as"
                value={type}
                onChange={setType}
                options={[
                  { value: "student", label: "Student / Applicant" },
                  { value: "admin", label: "Admin (activation required)" },
                ]}
              />
            ) : (
              <View
                style={[
                  styles.row,
                  {
                    backgroundColor: colors.input,
                    borderRadius: 14,
                    padding: 5,
                    flexWrap: "nowrap",
                    gap: 6,
                  },
                ]}
              >
                {[
                  ["student", "Student / Applicant"],
                  ["admin", "Admin / Staff"],
                ].map(([value, label]) => (
                  <Button
                    title={label}
                    key={value}
                    variant={type === value ? "primary" : "ghost"}
                    compact
                    onPress={() => {
                      setType(value);
                      setError("");
                    }}
                    style={{ flex: 1 }}
                  />
                ))}
              </View>
            )}
            {notice && <Notice text={notice} />}
            {error && <Notice text={error} error />}
            {register && (
              <Field
                label="Full name"
                placeholder="Your full name"
                value={name}
                onChangeText={setName}
                autoComplete="name"
                maxLength={100}
              />
            )}
            <Field
              label="Email address"
              placeholder="you@example.com"
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              keyboardType="email-address"
              autoComplete="email"
            />
            <View style={{ gap: 5 }}>
              <Field
                label="Password"
                placeholder={
                  register ? "Create a password" : "Enter your password"
                }
                value={password}
                onChangeText={setPassword}
                secureTextEntry={!visible}
                autoComplete={register ? "new-password" : "current-password"}
                maxLength={72}
                onSubmitEditing={submit}
              />
              <Button
                title={visible ? "Hide password" : "Show password"}
                variant="ghost"
                icon={visible ? "eye-off" : "eye"}
                compact
                onPress={() => setVisible(!visible)}
              />
            </View>
            {register && (
              <Txt muted size={12}>
                {type === "admin"
                  ? "Admin access must be activated by the scholarship office after registration. "
                  : ""}
                Use a password of 8–72 bytes.
              </Txt>
            )}
            <Button
              title={register ? "Create account" : "Sign in"}
              icon="arrow-right"
              loading={busy}
              onPress={submit}
              style={{ width: "100%" }}
            />
            <View style={{ alignItems: "center", gap: 4 }}>
              <Txt muted size={13}>
                {register
                  ? "Already have an account?"
                  : "New to the scholarship portal?"}
              </Txt>
              <Button
                title={register ? "Back to sign in" : "Create an account"}
                variant="ghost"
                compact
                onPress={() => {
                  router.navigate(
                    (register
                      ? type === "admin"
                        ? "/admin-login"
                        : "/login"
                      : "/register") as Href,
                  );
                  setPassword("");
                  setError("");
                  setNotice("");
                }}
              />
            </View>
          </Card>
        </View>
        <Txt size={11} muted style={{ textAlign: "center", padding: 24 }}>
          Pamantasan ng Lungsod ng Muntinlupa · Student Scholarship Portal
        </Txt>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

export function Portal({ page }: { page: PortalPage }) {
  const { colors, dark } = useTheme();
  const { width } = useWindowDimensions();
  const insets = useSafeAreaInsets();
  const wide = width >= 1024;
  const { visible: sidebarVisible, setVisible: setSidebarVisible } = useSidebar();
  const { user, setUser, ready, failure, boot, notice, setNotice } =
    useSession();
  const router = useRouter();
  const params = useLocalSearchParams<{
    scholarship?: string;
    user?: string;
  }>();
  const route: Route = useMemo(
    () => ({
      page: ["index", "login", "admin-login", "register"].includes(page)
        ? "home"
        : (page as Page),
      scholarshipId: Number(params.scholarship) || undefined,
      userId: Number(params.user) || undefined,
    }),
    [page, params.scholarship, params.user],
  );
  const [drawer, setDrawer] = useState(false);
  const [loggingOut, setLoggingOut] = useState(false);
  const admin = !!user && ["admin", "staff"].includes(user.role);
  const notify = (text: string, error = false) => setNotice({ text, error });
  const navigate = useCallback(
    (next: Route, replace = false) => {
      setDrawer(false);
      const query: Record<string, string> = {};
      if (next.scholarshipId) query.scholarship = String(next.scholarshipId);
      if (next.userId) query.user = String(next.userId);
      const target = { pathname: "/" + next.page, params: query } as Href;
      if (replace) router.replace(target);
      else router.navigate(target);
    },
    [router],
  );
  useEffect(() => {
    if (
      ready &&
      !failure &&
      !user &&
      !["index", "login", "admin-login", "register"].includes(page)
    ) {
      router.replace(
        ["admin", "inbox", "requirements"].includes(page)
          ? "/admin-login"
          : "/login",
      );
      return;
    }
    if (!user) return;
    if (["index", "login", "admin-login", "register"].includes(page))
      router.replace(admin ? "/admin" : "/home");
    else if (!admin && ["admin", "inbox", "requirements"].includes(route.page))
      router.replace("/home");
  }, [ready, failure, user, admin, page, route.page, navigate, router]);
  const logout = async () => {
    setLoggingOut(true);
    try {
      await api("logout", {});
      setUser(null);
      setNotice(null);
      router.replace("/login");
    } catch (error) {
      notify((error as Error).message, true);
    } finally {
      setLoggingOut(false);
    }
  };
  const nav: { page: Page; label: string; icon: IconName }[] = admin
    ? [
        { page: "home", label: "Overview", icon: "grid" },
        { page: "admin", label: "Applications", icon: "file-text" },
        { page: "inbox", label: "Messages", icon: "mail" },
      ]
    : [
        { page: "home", label: "Overview", icon: "grid" },
        { page: "scholarships", label: "Scholarships", icon: "award" },
        { page: "applications", label: "My applications", icon: "file-text" },
        { page: "faq", label: "FAQs", icon: "help-circle" },
        { page: "contact", label: "Contact", icon: "message-circle" },
      ];
  const sidebar = (
    <View
      style={{
        flex: 1,
        backgroundColor: colors.sidebar,
        padding: 24,
        paddingTop: insets.top + 32,
        gap: 28,
      }}
    >
      <Brand inverse />
      <Pressable
        accessibilityRole="button"
        accessibilityLabel="Hide sidebar"
        accessibilityState={{ expanded: true }}
        onPress={() => {
          if (wide) setSidebarVisible(false);
          else setDrawer(false);
        }}
        style={({ pressed }) => ({
          minHeight: 44,
          borderRadius: 12,
          backgroundColor: pressed ? "#FFFFFF25" : "#FFFFFF12",
          flexDirection: "row",
          alignItems: "center",
          justifyContent: "center",
          gap: 10,
          paddingHorizontal: 12,
        })}
      >
        <Icon name="chevrons-left" size={17} color="#BCD1C2" />
        <Txt bold size={13} style={{ color: "#EDF3E9" }}>
          Hide sidebar
        </Txt>
      </Pressable>
      <View style={{ height: 1, backgroundColor: "#FFFFFF16" }} />
      <Txt size={9} bold style={{ color: "#8BA995", letterSpacing: 2 }}>
        {admin ? "ADMINISTRATION" : "STUDENT WORKSPACE"}
      </Txt>
      <View style={{ gap: 8 }}>
        {nav.map((item) => {
          const selected =
            route.page === item.page ||
            (item.page === "scholarships" && route.page === "apply") ||
            (item.page === "admin" && route.page === "requirements");
          return (
            <Pressable
              key={item.page}
              accessibilityRole="button"
              accessibilityLabel={item.label}
              accessibilityState={{ selected }}
              onPress={() => {
                setNotice(null);
                navigate({ page: item.page });
              }}
              style={({ pressed }) => ({
                backgroundColor: selected
                  ? "#FFFFFF18"
                  : pressed
                    ? "#FFFFFF0A"
                    : "transparent",
                borderRadius: 12,
                paddingHorizontal: 14,
                paddingVertical: 14,
                flexDirection: "row",
                gap: 12,
                alignItems: "center",
                borderLeftWidth: 3,
                borderLeftColor: selected ? "#D8B575" : "transparent",
              })}
            >
              <Icon
                name={item.icon}
                color={selected ? "#E5C58B" : "#B5CEBD"}
                size={18}
              />
              <Txt
                size={14}
                bold={selected}
                style={{ color: selected ? "#F5F7EE" : "#BCD1C2" }}
              >
                {item.label}
              </Txt>
            </Pressable>
          );
        })}
      </View>
      <View style={{ flex: 1 }} />
      <View style={{ gap: 22, paddingBottom: insets.bottom }}>
        <ThemeToggle inverse />
        <View style={{ height: 1, backgroundColor: "#FFFFFF16" }} />
        <View style={styles.row}>
          <View
            style={{
              width: 38,
              height: 38,
              borderRadius: 13,
              backgroundColor: "#FFFFFF16",
              alignItems: "center",
              justifyContent: "center",
            }}
          >
            <Txt bold style={{ color: "#E5C58B" }}>
              {user?.name?.[0]?.toUpperCase()}
            </Txt>
          </View>
          <View style={{ flex: 1 }}>
            <Txt size={13} bold numberOfLines={1} style={{ color: "#EDF3E9" }}>
              {user?.name}
            </Txt>
            <Txt
              size={10}
              style={{
                color: "#91AD9B",
                textTransform: "uppercase",
                letterSpacing: 1,
              }}
            >
              {admin ? user?.role : "Student / Applicant"}
            </Txt>
          </View>
        </View>
        <Button
          title="Sign out"
          icon="log-out"
          variant="secondary"
          compact
          loading={loggingOut}
          onPress={logout}
          style={{ width: "100%" }}
        />
      </View>
    </View>
  );
  const props = user ? { user, navigate, notify } : null;
  let content: React.ReactNode = null;
  if (props) {
    switch (route.page) {
      case "home":
        content = <Dashboard {...props} />;
        break;
      case "scholarships":
        content = <Scholarships {...props} />;
        break;
      case "apply":
        content = (
          <Apply
            key={route.scholarshipId}
            scholarshipId={route.scholarshipId}
            {...props}
          />
        );
        break;
      case "applications":
        content = <Applications {...props} />;
        break;
      case "admin":
        content = admin ? <Applications {...props} /> : null;
        break;
      case "inbox":
        content = admin ? <Inbox {...props} /> : null;
        break;
      case "requirements":
        content =
          admin && route.userId && route.scholarshipId ? (
            <Requirements
              key={`${route.userId}-${route.scholarshipId}`}
              userId={route.userId}
              scholarshipId={route.scholarshipId}
              {...props}
            />
          ) : (
            <Notice text="Select an application to review its requirements." />
          );
        break;
      case "contact":
        content = <Contact {...props} />;
        break;
      case "faq":
        content = <FAQ {...props} />;
        break;
    }
  }
  return (
    <View style={{ flex: 1, backgroundColor: colors.background }}>
      <StatusBar style={dark ? "light" : "dark"} />
      {!ready ? (
        <Loader />
      ) : failure ? (
        <View
          style={{
            flex: 1,
            alignItems: "center",
            justifyContent: "center",
            padding: 24,
          }}
        >
          <Card style={{ maxWidth: 460 }}>
            <Brand />
            <Notice error text={failure} />
            <Button title="Reconnect" icon="refresh-cw" onPress={boot} />
          </Card>
        </View>
      ) : !user ? (
        <Auth
          onLogin={(account) => {
            setUser(account);
            setNotice(null);
            navigate(
              {
                page: ["admin", "staff"].includes(account.role)
                  ? "admin"
                  : "home",
              },
              true,
            );
          }}
        />
      ) : (
        <View style={{ flex: 1, flexDirection: "row" }}>
          {wide && sidebarVisible && (
            <View style={{ width: 250 }}>{sidebar}</View>
          )}
          <View style={{ flex: 1, minWidth: 0 }}>
            <View
              style={{
                minHeight: 78,
                paddingTop: insets.top,
                paddingHorizontal: wide ? 40 : 20,
                backgroundColor: colors.surface,
                borderBottomWidth: 1,
                borderColor: colors.border,
                flexDirection: "row",
                alignItems: "center",
                justifyContent: "space-between",
                gap: 12,
              }}
            >
              {!wide || !sidebarVisible ? (
                <Button
                  title={wide ? "Show sidebar" : "Menu"}
                  icon="menu"
                  variant="ghost"
                  compact
                  onPress={() => {
                    if (wide) setSidebarVisible(true);
                    else setDrawer(true);
                  }}
                />
              ) : (
                <Txt muted size={13}>
                  Workspace{" "}
                  <Txt muted size={13}>
                    {" "}
                    /{" "}
                  </Txt>
                  <Txt bold size={13}>
                    {titles[route.page]}
                  </Txt>
                </Txt>
              )}
              {!wide && <Brand />}
              <View style={styles.row}>
                {wide ? (
                  <Txt muted size={12}>
                    {admin
                      ? "Admin / Staff Portal"
                      : "Student / Applicant Portal"}
                  </Txt>
                ) : (
                  <ThemeToggle compact />
                )}
                {wide && (
                  <View
                    style={{
                      backgroundColor: colors.soft,
                      width: 36,
                      height: 36,
                      borderRadius: 12,
                      alignItems: "center",
                      justifyContent: "center",
                    }}
                  >
                    <Icon
                      name={admin ? "shield" : "user"}
                      color={colors.primary}
                      size={17}
                    />
                  </View>
                )}
              </View>
            </View>
            <KeyboardAvoidingView
              style={{ flex: 1 }}
              behavior={Platform.OS === "ios" ? "padding" : undefined}
            >
              <ScrollView
                keyboardShouldPersistTaps="handled"
                contentContainerStyle={{
                  padding: wide ? 36 : 20,
                  paddingBottom: Math.max(insets.bottom, 24),
                  gap: 20,
                  width: "100%",
                  maxWidth: 1420,
                  alignSelf: "center",
                }}
              >
                {notice && (
                  <View style={{ gap: 5 }}>
                    <Notice text={notice.text} error={notice.error} />
                    <Button
                      title="Dismiss message"
                      variant="ghost"
                      compact
                      onPress={() => setNotice(null)}
                    />
                  </View>
                )}
                {content}
                <Txt
                  muted
                  size={11}
                  style={{ textAlign: "center", paddingTop: 28 }}
                >
                  PLMun Scholarship Portal · {new Date().getFullYear()}
                </Txt>
              </ScrollView>
            </KeyboardAvoidingView>
          </View>
          {!wide && (
            <Modal
              transparent
              visible={drawer}
              animationType="fade"
              onRequestClose={() => setDrawer(false)}
            >
              <View style={{ flex: 1, flexDirection: "row" }}>
                <View style={{ width: Math.min(300, width - 48) }}>
                  {sidebar}
                </View>
                <Pressable
                  accessibilityLabel="Close menu"
                  accessibilityRole="button"
                  style={{ flex: 1, backgroundColor: "#00000080" }}
                  onPress={() => setDrawer(false)}
                />
              </View>
            </Modal>
          )}
        </View>
      )}
    </View>
  );
}
