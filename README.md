Student Scholarship Portal

This is my very first project which is The Student Scholarship Portal is a web-based system designed to simplify and manage the process of scholarship applications for students. This project was developed as part of my journey as a BS Computer Science student, aiming to apply real-world concepts in web development and database management.

The system allows students to easily browse available scholarships, submit their applications online, and track their application status. On the admin side, it provides tools to review applications, manage student records, and organize scholarship data efficiently.

This project was built using PHP, MySQL, HTML, CSS, and JavaScript, focusing on creating a clean user interface and a functional backend system. It also implements basic authentication, form validation, and database integration to ensure a smooth and secure user experience.

Overall, the goal of this project is to reduce manual processing, improve accessibility, and provide a centralized platform for both students and administrators.

Features
* Student registration and login system
* Scholarship listing and application submission
* Admin dashboard for managing applications
* Status tracking for submitted applications
* Responsive and user-friendly interface
* Admin and staff review of submitted requirements, with PDF/JPG previews and downloads

## React Native student and admin app

The new frontend is in `studentportal/mobile`, built with Expo, Expo Router, and React Native.
The same screens run in the browser and are ready for Android/iOS development.
File-based screens live in `mobile/src/app`, with shared UI and services in
`mobile/src`. Student and admin navigation, registration, login, scholarships, submissions,
status tracking, contact, FAQs, messages, and requirements review use the existing
PHP/MySQL backend through `www/api.php`. The green/gold PLMun identity is retained,
with rounded icon buttons, loading/disabled states, responsive navigation, and a
saved dark/light theme on both sides of the portal.

### Browser preview

Keep XAMPP MySQL running. From the project folder:

```sh
cd studentportal/mobile
npm ci
npm run build:web
cd ..
npm start
```

Open **http://127.0.0.1:8080**. The PHP router serves the exported React Native app
and API together, so existing accounts and documents continue to work. Existing
page links such as `scholarships.php` and `admin_login.php` redirect to the matching
new screen. Restart a previously running PHP server after changing the start script.
The generated `mobile/dist` folder is ignored by Git and must be rebuilt on a new
checkout. Without an export, the router falls back to the original PHP pages.

For live frontend editing, leave `npm start` running and use a second terminal:

```sh
cd studentportal
npm run mobile:web
```

Open **http://localhost:8081**. Changes to `mobile/src` update through Metro. Run
`npm run build:web` again when you want the main `8080` preview to use those edits.
The browser uses the API on the same origin in the exported app, or port `8080`
when using the Expo development server on `8081`.

### Phone development

Run `npm run mobile:start` from `studentportal`, or `npm run android` / `npm run ios`
inside `mobile`. Use a development client compatible with the installed Expo SDK;
physical-device and app-store builds have not been verified by the browser tests.
A phone must reach your PHP server through your Mac's LAN address or an HTTPS
backend, rather than the phone's own `127.0.0.1`. Set `EXPO_PUBLIC_API_URL` in
`mobile/.env` (see `.env.example`) and restart Metro. For local LAN testing, bind
PHP to `0.0.0.0:8080`; production deployments should use HTTPS. Native upload uses
the system document picker, and documents open/save through the device share sheet.
Browser requirements have in-app PDF/JPG previews and downloads.

Browser login uses HttpOnly PHP session cookies plus CSRF tokens. Native clients
use revocable seven-day bearer tokens stored with Expo SecureStore. Token records
are hashed on disk under private `storage/api-sessions`, and account roles are
checked against the database on API requests. Dark mode stores only the theme
preference in AsyncStorage. CORS defaults to the local `8081`/`19006` origins; set
`PORTAL_ALLOWED_ORIGINS` to an explicit comma-separated list for a deployed frontend.
The PHP development router blocks direct requests to legacy `/uploads/` files.

See [Expo's project documentation](https://docs.expo.dev/get-started/create-a-project/)
and [DocumentPicker documentation](https://docs.expo.dev/versions/latest/sdk/document-picker/)
for the framework and system file picker used by this app.

## Database and PHP setup

The backend requires PHP 8.2+ with MySQLi, Fileinfo, and sessions, plus MySQL or
MariaDB (including XAMPP's bundled database). The original Cordova files are
legacy scaffolding; the new app lives in `mobile`.

1. Create an empty database named `scholarship_system`, using a MySQL account with
   permission to create the portal tables.
2. For a **new installation**, import [database/schema.sql](studentportal/database/schema.sql)
   into that database with phpMyAdmin, or run:

   ```sh
   mysql -u YOUR_DATABASE_USER -p scholarship_system < studentportal/database/schema.sql
   ```

   The script creates `users`, `scholarships`, `applications`, and `contact_message`.
   Application identity is the pair `(user_id, scholarship_id)`, which must be unique.
   **For an existing database**, back it up and compare its tables with this schema
   first. `CREATE TABLE IF NOT EXISTS` does not update existing columns or indexes.
   Resolve any duplicate applications before adding the composite primary key.
   Existing passwords must be hashes produced by PHP's `password_hash`; plaintext
   passwords need to be reset before those accounts can sign in.
3. Copy [config/local.example.php](studentportal/config/local.example.php) to
   `studentportal/config/local.php` and enter your database settings. This private
   file is excluded from Git. The existing installation's connection settings have
   been preserved there in this working checkout. On a new clone, create the file.
   Alternatively, set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME`;
   environment variables override the private file. Every database entry point now
   uses these same settings. If local pages time out while connecting, check this
   file first: the preserved settings may still point to the hosted database.
   For a local MySQL installation, use `127.0.0.1` and that installation's actual
   port, username, password, and database name. Starting the PHP server does not
   start MySQL. Connections and server reads have five-second timeouts so an
   unresponsive database produces a controlled error.
4. Start the development server:

   ```sh
   cd studentportal
   npm start
   ```

   Open `http://127.0.0.1:8080`. Build the React Native web frontend first as shown
   above. Without Node, the PHP backend and legacy pages can run directly:

   ```sh
   php -d upload_max_filesize=5M -d post_max_size=52M -S 127.0.0.1:8080 -t www scripts/browser-router.php
   ```

5. Register a student account. To make a registered account an admin or staff
   member, run one of these commands **on the server**, from `studentportal`:

   ```sh
   php scripts/set-role.php admin@example.com admin
   php scripts/set-role.php staff@example.com staff
   ```

   Use the actual registered email address. Sign out and sign in again after a role
   change. The registration form labels the registrant as **Student** or **Admin
   (activation required)**. Selecting Admin displays activation instructions; it
   does not grant admin privileges. All newly registered accounts start with
   student access until the server-side role command activates admin access.
   Account roles are never assigned directly from form input.
   After successful registration, Student/Applicant registrants are redirected to
   Student Login (`index.php`), and Admin registrants to Admin / Staff Login
   (`admin_login.php`). After sign-in, the saved account role determines the
   dashboard: admins/staff go to `manage_applications.php` and students/applicants
   to `home.php`.
6. Import [database/scholarship-programs.sql](studentportal/database/scholarship-programs.sql)
   to add CARRI, BOARD Program, CREATE, and SEAL. Re-running the import does not
   duplicate existing names or overwrite their settings. For a database created
   with the earlier schema, first run
   [001_optional_scholarship_deadline.sql](studentportal/database/migrations/001_optional_scholarship_deadline.sql).
   The migration preserves existing dates and allows an unannounced deadline to
   be stored as `NULL`. These four programs start as active with **No deadline
   announced**, and applicants can choose and apply to them. Set a real deadline
   (`YYYY-MM-DD`) through your database tool when one is announced. Programs with
   a passed deadline or an inactive status cannot accept applications.

## Applications and requirements

Students see only their own application records and statistics. Applications require
1–10 valid PDF/JPG documents, up to 5 MB per file. A student can submit one application
per scholarship. Duplicate submissions and failed saves do not leave new files behind.
The application form preserves entered details when validation fails.

Admins and staff sign in through `admin_login.php` or the main login page, then use
`manage_applications.php`. **View Requirements** is available in the All, Approved,
and Rejected lists. Expand a document to preview it, open it in a new tab, or download
it. The page also shows applicant details, academic information, and application status.
**Return to Pending** reopens a rejected application without changing its original
submission date. Removing an application removes its database record; existing files
are retained on disk and can be removed according to your retention policy.

New documents are stored in `studentportal/storage/requirements`, outside the web
folder. The PHP process needs permission to create and write that directory. Keep
`config/`, `database/`, `scripts/`, `tests/`, and `storage/` outside the public document
root: serve **only `studentportal/www`**. Use HTTPS in production. Set PHP's
`upload_max_filesize` to at least `5M`, `post_max_size` to at least `52M`, and
`max_file_uploads` to at least `20`. The portal uses Asia/Manila time by default;
`PORTAL_TIMEZONE` can override it. Scholarship deadlines include the whole listed
calendar date in the database server's time zone; configure MySQL's time zone to
match the portal.

Older files saved by the original upload form remain supported in `www/uploads`.
Deploy `uploads/.htaccess` and allow its access directives on Apache to block direct
access to those legacy files. On Nginx, configure the equivalent denial of requests
to `/uploads/` (adjust for your site's URL prefix). PHP's development server ignores
`.htaccess`; the supplied development router blocks `/uploads/` directly. New files are private
regardless of `.htaccess`, and uploaded files are excluded from Git. Back up both the
database and document storage when moving the portal to another server.

Admin status updates, message deletion, application removal/reopening, logout, and
form submissions require POST requests and session CSRF tokens. Database errors are
logged on the server and return a generic message to the browser.

## Verification

From `studentportal`:

```sh
npm run lint
npm test
npm run typecheck
npm run build:web
npm run test:e2e
```

Or, from the repository root:

```sh
php studentportal/scripts/lint.php
python3 -m unittest discover -s studentportal/tests -v
```

The HTTP integration tests run the real PHP pages in an isolated temporary copy,
with temporary sessions, documents, and a SQLite-backed test adapter in place of
`db.php`. They cover registration and login, role checks, session rotation and logout,
student data isolation, search, upload validation and rollback, admin actions, messages,
PDF/JPG viewing and downloading, missing documents, malicious file paths, API CSRF/CORS checks, and native token revocation. The
tests require Python 3 and PHP CLI with MySQLi, PDO SQLite, and Fileinfo. They never
connect to the configured database or alter live records. MySQL-specific schema,
constraints, locking, and your production web-server configuration still need to be
verified on the deployment server.

A separate regression test uses the real MySQL driver against a deliberately
unresponsive local listener to check that connection attempts time out cleanly.

The Chrome end-to-end tests use synthetic accounts in an isolated temporary PHP
server on `8099`. Browser API requests are intercepted and forwarded to that server;
the configured MySQL database is never used. They cover student uploads, admin
requirements previews and decisions, registration routing, theme persistence, and
navigation at phone width. Google Chrome must be installed. Ports `8081` and `8099`
must be free while these tests run.

Dependency installation currently reports upstream Expo/Metro tooling advisories
in `braces`, `node-forge`, and `uuid`. Compatible `npm audit fix` updates were applied;
the remaining automatic suggestion downgrades Expo to SDK 44 and was not applied.
Review those upstream fixes before a production release.

Technologies Used
* Frontend: React Native, Expo, React Native Web, TypeScript
* Backend: PHP
* Database: MySQL

Purpose:
This project serves as a practical application of concepts learned in software development, particularly in building dynamic web applications with database integration. It also reflects my understanding of user-centered design and system functionality.
