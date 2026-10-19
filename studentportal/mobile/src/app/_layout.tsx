import React from "react";
import { Slot } from "expo-router";
import { SafeAreaProvider } from "react-native-safe-area-context";
import { ThemeProvider } from "../theme";
import { SessionProvider } from "../session";
import { SidebarProvider } from "../sidebar";

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <ThemeProvider>
        <SessionProvider>
          <SidebarProvider>
            <Slot />
          </SidebarProvider>
        </SessionProvider>
      </ThemeProvider>
    </SafeAreaProvider>
  );
}
