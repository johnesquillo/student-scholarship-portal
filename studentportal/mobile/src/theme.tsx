import React, { createContext, useContext, useEffect, useState } from "react";
import { useColorScheme } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";

const light = {
  background: "#F5F6F2",
  surface: "#FFFFFF",
  soft: "#EBF1EC",
  text: "#183B30",
  muted: "#697C72",
  border: "#DFE6DD",
  primary: "#1C6849",
  onPrimary: "#FFFFFF",
  accent: "#D2AB63",
  danger: "#B43F47",
  dangerBg: "#FCECEE",
  successBg: "#E3F3E9",
  warning: "#936B24",
  warningBg: "#FBF1DA",
  sidebar: "#123C2E",
  sidebarText: "#D8E7DC",
  input: "#F9FAF7",
  shadow: "#152A1E",
};
const dark: typeof light = {
  background: "#101A16",
  surface: "#192720",
  soft: "#23372C",
  text: "#EBF2E8",
  muted: "#A2B5A7",
  border: "#314438",
  primary: "#8ED6AD",
  onPrimary: "#12291C",
  accent: "#E0BE7D",
  danger: "#F3979B",
  dangerBg: "#45272D",
  successBg: "#244334",
  warning: "#E8C789",
  warningBg: "#403722",
  sidebar: "#0C1511",
  sidebarText: "#D8E7DC",
  input: "#142019",
  shadow: "#000000",
};
type ThemeValue = { colors: typeof light; dark: boolean; toggle: () => void };
const Context = createContext<ThemeValue>({
  colors: light,
  dark: false,
  toggle: () => {},
});
export function ThemeProvider({ children }: React.PropsWithChildren) {
  const system = useColorScheme();
  const [mode, setMode] = useState<"light" | "dark" | null>(null);
  useEffect(() => {
    AsyncStorage.multiGet(["portal-theme", "theme"])
      .then((entries) => {
        const current = entries[0][1];
        const value =
          current === "light" || current === "dark" ? current : entries[1][1];
        if (value === "light" || value === "dark") {
          setMode(value);
          if (value !== current)
            AsyncStorage.setItem("portal-theme", value).catch(() => {});
        }
      })
      .catch(() => {});
  }, []);
  const isDark = (mode || system) === "dark";
  const toggle = () => {
    const next = isDark ? "light" : "dark";
    setMode(next);
    AsyncStorage.setItem("portal-theme", next).catch(() => {});
  };
  return (
    <Context.Provider
      value={{ colors: isDark ? dark : light, dark: isDark, toggle }}
    >
      {children}
    </Context.Provider>
  );
}
export const useTheme = () => useContext(Context);
