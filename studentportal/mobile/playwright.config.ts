import { defineConfig } from "@playwright/test";
export default defineConfig({
  testDir: "./e2e",
  timeout: 45000,
  expect: { timeout: 12000 },
  workers: 1,
  fullyParallel: false,
  use: {
    baseURL: "http://localhost:8081",
    browserName: "chromium",
    channel: "chrome",
    colorScheme: "light",
    viewport: { width: 1440, height: 1000 },
    screenshot: "only-on-failure",
    trace: "retain-on-failure",
  },
  webServer: [
    {
      command: "python3 e2e/fixture-server.py",
      url: "http://127.0.0.1:8099/api.php?action=session",
      reuseExistingServer: false,
      timeout: 30000,
    },
    {
      command: "npx expo start --web --host localhost --port 8081",
      url: "http://localhost:8081",
      reuseExistingServer: false,
      timeout: 120000,
      env: { CI: "1", EXPO_NO_TELEMETRY: "1", EXPO_OFFLINE: "1" },
    },
  ],
});
