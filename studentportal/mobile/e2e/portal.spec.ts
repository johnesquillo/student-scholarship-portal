import { test, expect, Page } from "@playwright/test";
import { Buffer } from "node:buffer";

test.beforeEach(async ({ page, request }) => {
  await request.get("http://127.0.0.1:8099/test_reset.php");
  await page.route(
    /http:\/\/(localhost|127\.0\.0\.1):8080\/api\.php.*/,
    async (route) => {
      const url = new URL(route.request().url());
      url.hostname = "127.0.0.1";
      url.port = "8099";
      const response = await route.fetch({ url: url.toString() });
      await route.fulfill({ response });
    },
  );
});
async function login(page: Page, admin = false) {
  await page.goto("/");
  if (admin)
    await page
      .getByRole("button", { name: "Admin / Staff", exact: true })
      .click();
  await page
    .getByRole("textbox", { name: "Email address", exact: true })
    .fill(admin ? "admin@example.test" : "student@example.test");
  await page.getByLabel("Password", { exact: true }).fill("TestPass123!");
  await page.getByRole("button", { name: "Sign in", exact: true }).click();
  await expect(
    page.getByRole("button", { name: "Sign out", exact: true }),
  ).toBeVisible();
}

async function checkSidebarToggle(page: Page) {
  await page.getByRole("button", { name: "Hide sidebar", exact: true }).click();
  await expect(
    page.getByRole("button", { name: "Sign out", exact: true }),
  ).toBeHidden();
  await page.getByRole("button", { name: "Show sidebar", exact: true }).click();
  await expect(
    page.getByRole("button", { name: "Sign out", exact: true }),
  ).toBeVisible();
}

test("student scholarships, uploads, dark mode, and saved theme", async ({
  page,
}) => {
  await page.addInitScript(() => {
    if (!localStorage.getItem("portal-theme"))
      localStorage.setItem("theme", "dark");
  });
  await login(page);
  await checkSidebarToggle(page);
  await expect(
    page.getByText("Your education. Your next opportunity."),
  ).toBeVisible();
  await page.goto("/inbox");
  await expect(
    page.getByText("Your education. Your next opportunity."),
  ).toBeVisible();
  await expect(page.getByLabel("Dark mode", { exact: true })).toBeChecked();
  await page.getByLabel("Dark mode", { exact: true }).click();
  await expect
    .poll(() => page.evaluate(() => localStorage.getItem("portal-theme")))
    .toBe("light");
  await page.getByLabel("Dark mode", { exact: true }).click();
  await expect
    .poll(() => page.evaluate(() => localStorage.getItem("portal-theme")))
    .toBe("dark");
  await page.reload();
  await expect(page.getByLabel("Dark mode", { exact: true })).toBeChecked();
  await page.getByRole("button", { name: "Scholarships", exact: true }).click();
  for (const name of ["CARRI", "BOARD Program", "CREATE", "SEAL"])
    await expect(page.getByText(name, { exact: true })).toBeVisible();
  await page
    .getByRole("textbox", { name: "Search scholarships" })
    .fill("CARRI");
  await page.getByRole("button", { name: "Apply now", exact: true }).click();
  await page.getByRole("textbox", { name: "Course", exact: true }).fill("BSCS");
  await page.getByRole("textbox", { name: "GWA", exact: true }).fill("1.5");
  await page.getByRole("button", { name: /^Year level:/ }).click();
  await page.getByRole("radio", { name: "2nd Year", exact: true }).click();
  const fileChooser = page.waitForEvent("filechooser");
  await page
    .getByRole("button", { name: "Choose requirement files", exact: true })
    .click();
  await (
    await fileChooser
  ).setFiles({
    name: "grades.pdf",
    mimeType: "application/pdf",
    buffer: Buffer.from("%PDF-1.4\n1 0 obj<</Type /Catalog>>endobj\n%%EOF\n"),
  });
  await page
    .getByRole("button", { name: "Submit application", exact: true })
    .click();
  await expect(
    page.getByText("Your application has been submitted successfully."),
  ).toBeVisible();
  await expect(
    page.getByText("CARRI (Cultural, Athletics, Rescue and ROTC Incentives)", {
      exact: true,
    }),
  ).toBeVisible();
  await page.screenshot({
    path: "test-results/student-dark.png",
    fullPage: true,
  });
});

test("admin reviews requirements, previews documents, and approves an application", async ({
  page,
}) => {
  await login(page, true);
  await checkSidebarToggle(page);
  await expect(
    page.getByText("Manage applications", { exact: true }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "View requirements", exact: true })
    .first()
    .click();
  await expect(
    page.getByText("Documents · 3 submitted", { exact: true }),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "Preview", exact: true })
    .first()
    .click();
  await expect(page.locator('iframe[title="grades.pdf"]')).toBeVisible();
  await page
    .getByRole("button", { name: "Close preview", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Back to applications", exact: true })
    .click();
  await page
    .getByRole("button", { name: "Approve", exact: true })
    .first()
    .click();
  await page.getByRole("button", { name: "Confirm", exact: true }).click();
  await expect(
    page.getByText("Application approved.", { exact: true }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Approved (2)", exact: true }),
  ).toBeVisible();
  await page.getByLabel("Dark mode", { exact: true }).click();
  await page.screenshot({
    path: "test-results/admin-dark.png",
    fullPage: true,
  });
  await page.getByRole("button", { name: "Messages", exact: true }).click();
  await expect(page.getByText("You’re all caught up")).toBeVisible();
});

test("mobile browser navigation and registration stay usable", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/");
  await page
    .getByRole("button", { name: "Create an account", exact: true })
    .click();
  await page.getByRole("button", { name: /^Registering as:/ }).click();
  await page
    .getByRole("radio", { name: "Admin (activation required)", exact: true })
    .click();
  await page
    .getByRole("textbox", { name: "Full name", exact: true })
    .fill("New Admin");
  await page
    .getByRole("textbox", { name: "Email address", exact: true })
    .fill("new-admin@example.test");
  await page.getByLabel("Password", { exact: true }).fill("TestPass123!");
  await page
    .getByRole("button", { name: "Create account", exact: true })
    .click();
  await expect(
    page.getByText("Admin / staff login", { exact: true }),
  ).toBeVisible();
  await expect(
    page.getByText(
      "Account created. Contact the scholarship office to activate admin access.",
    ),
  ).toBeVisible();
  await page
    .getByRole("button", { name: "Student / Applicant", exact: true })
    .click();
  await page
    .getByRole("textbox", { name: "Email address", exact: true })
    .fill("student@example.test");
  await page.getByLabel("Password", { exact: true }).fill("TestPass123!");
  await page.getByRole("button", { name: "Sign in", exact: true }).click();
  await page.getByRole("button", { name: "Menu", exact: true }).click();
  await page.getByRole("button", { name: "Hide sidebar", exact: true }).click();
  await expect(
    page.getByRole("button", { name: "Close menu", exact: true }),
  ).toBeHidden();
  await page.getByRole("button", { name: "Menu", exact: true }).click();
  await page.getByRole("button", { name: "FAQs", exact: true }).click();
  await expect(
    page.getByText("Frequently asked questions", { exact: true }),
  ).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Close menu", exact: true }),
  ).toBeHidden();
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBeTruthy();
  await page.screenshot({
    path: "test-results/student-mobile.png",
    fullPage: true,
  });
});
