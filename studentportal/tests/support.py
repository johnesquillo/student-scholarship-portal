"""Isolated HTTP integration harness; no real account or MySQL server is used."""
import base64
import http.cookiejar
import os
from pathlib import Path
import re
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.parse
import urllib.request


PDF = b"%PDF-1.4\n1 0 obj<</Type /Catalog>>endobj\n%%EOF\n"
JPEG = base64.b64decode(
    "/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAP//////////////////////////////////"
    "//////////////////////////////////////////////////////2wBDAf////////"
    "//////////////////////////////////////////////////////////////////"
    "////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAf/"
    "xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAA"
    "AAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF/"
    "/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAA"
    "AAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oA"
    "DAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EH//xAAUEQEA"
    "AAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EH//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oA"
    "CAEBAAE/EH//2Q==")


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, request, response, code, message, headers, new_url):
        return None


class PortalCase(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        temporary = tempfile.TemporaryDirectory(prefix="portal-integration-")
        cls.addClassCleanup(temporary.cleanup)
        cls.temp = Path(temporary.name)
        cls.root = cls.temp / "www"
        source = Path(__file__).resolve().parents[1]
        cls.program_sql = (source / 'database/scholarship-programs.sql').read_text()
        shutil.copytree(source / "www", cls.root)
        shutil.copy2(source / "tests/fixture_db.php", cls.root / "db.php")
        cls.private = cls.temp / "storage/requirements"
        cls.private.mkdir(parents=True)
        cls.uploads = cls.root / "uploads"
        cls.uploads.mkdir(exist_ok=True)
        (cls.private / "grades.pdf").write_bytes(PDF)
        (cls.uploads / "id.jpg").write_bytes(JPEG)
        (cls.uploads / "fake.pdf").write_text("<script>not a PDF</script>")
        (cls.uploads / "execute.php").write_text("private data")
        (cls.root / "secret.pdf").write_bytes(PDF)
        (cls.uploads / "escape.pdf").symlink_to(cls.root / "secret.pdf")
        (cls.root / "test_session.php").write_text("""<?php
require __DIR__ . '/bootstrap.php';
$_SESSION = ['user_id' => 7, 'email' => 'student@example.test'];
if (isset($_GET['role'])) { $_SESSION['role'] = $_GET['role']; }
""")
        password_hash = subprocess.check_output(
            ['php', '-r', "echo password_hash('TestPass123!', PASSWORD_DEFAULT);"], text=True)
        cls.baseline = cls.temp / "baseline.sqlite"
        with sqlite3.connect(cls.baseline) as db:
            db.executescript("""
CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL,
 email TEXT NOT NULL UNIQUE, password TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'user');
CREATE TABLE scholarships (scholarship_id INTEGER PRIMARY KEY, name TEXT, description TEXT,
 deadline TEXT, status TEXT);
CREATE TABLE applications (user_id INTEGER, scholarship_id INTEGER, name TEXT, course TEXT,
 email TEXT, gwa NUMERIC, year_level TEXT, uploaded_files TEXT, application_date TEXT DEFAULT CURRENT_TIMESTAMP,
 status TEXT DEFAULT 'Pending', PRIMARY KEY(user_id, scholarship_id));
CREATE TABLE contact_message (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT, email TEXT, message TEXT,
 created_at TEXT DEFAULT CURRENT_TIMESTAMP);
CREATE TRIGGER fail_save BEFORE INSERT ON applications WHEN NEW.course = 'FAIL_SAVE'
 BEGIN SELECT RAISE(ABORT, 'Simulated database failure'); END;
""")
            for uid, name, email, role in ((1, "Admin", "admin@example.test", "admin"),
                                           (2, "Staff", "staff@example.test", "staff"),
                                           (7, "Student <script>alert(1)</script>", "student@example.test", "user"),
                                           (8, "Other Student Private", "other@example.test", "user")):
                db.execute("INSERT INTO users VALUES (?, ?, ?, ?, ?)", (uid, name, email, password_hash, role))
            for sid in (3, 4, 5, 6, 8, 9, 10):
                name = {3: "Merit Scholarship", 8: "Expired Scholarship", 9: "Inactive Scholarship", 10: "New Scholarship"}.get(sid, f"Scholarship {sid}")
                db.execute("INSERT INTO scholarships VALUES (?, ?, ?, ?, ?)",
                           (sid, name, "Scholarship description", "2000-01-01" if sid == 8 else "2099-12-31", "inactive" if sid == 9 else "active"))
            for sid, status, files in ((3, "Pending", "grades.pdf,id.jpg,missing.pdf"), (4, "Approved", ""),
                                       (5, "Rejected", "../secret.pdf,escape.pdf,execute.php,fake.pdf"), (6, "Pending", "id.jpg")):
                db.execute("INSERT INTO applications (user_id, scholarship_id, name, course, email, gwa, year_level, uploaded_files, status) VALUES (7, ?, ?, 'BSCS', 'student@example.test', 1.5, '2nd Year', ?, ?)",
                           (sid, "Student <script>alert(1)</script>", files, status))
            db.executescript(cls.program_sql)
            db.execute("INSERT INTO applications (user_id, scholarship_id, name, course, email, gwa, year_level, uploaded_files) VALUES (8, 3, 'Other Student Private', 'BSIT', 'other@example.test', 1.25, '1st Year', 'id.jpg')")
        cls.log = (cls.temp / "server.log").open("w+")
        cls.addClassCleanup(cls.log.close)
        with socket.socket() as sock:
            sock.bind(("127.0.0.1", int(os.environ.get('PORTAL_TEST_PORT', '0'))))
            port = sock.getsockname()[1]
        cls.url = f"http://127.0.0.1:{port}/"
        (cls.temp / "sessions").mkdir()
        cls.server = subprocess.Popen(
            ["php", "-d", f"session.save_path={cls.temp / 'sessions'}", "-d", "upload_max_filesize=5M",
             "-d", "post_max_size=52M", "-S", f"127.0.0.1:{port}", "-t", str(cls.root)],
            stdout=cls.log, stderr=cls.log)
        cls.addClassCleanup(cls.stop_server)
        for _ in range(100):
            try:
                urllib.request.urlopen(cls.url + "css/portal.css", timeout=1).close()
                break
            except OSError:
                if cls.server.poll() is not None:
                    cls.log.seek(0)
                    raise RuntimeError(cls.log.read())
                time.sleep(0.05)
        else:
            raise RuntimeError("PHP test server did not start")

    @classmethod
    def stop_server(cls):
        cls.server.terminate()
        cls.server.wait(timeout=5)

    def setUp(self):
        shutil.copy2(self.baseline, self.temp / "test.sqlite")
        for path in self.private.iterdir():
            if path.name != 'grades.pdf':
                path.unlink()
        self.client = self.new_client()

    def new_client(self):
        return urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))

    def request(self, path, data=None, headers=None, client=None):
        if isinstance(data, dict):
            data = urllib.parse.urlencode(data).encode()
        req = urllib.request.Request(self.url + path, data=data, headers=headers or {})
        try:
            response = (client or self.client).open(req, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.headers, response.read()

    def token(self, page, client=None):
        status, _, body = self.request(page, client=client)
        self.assertEqual(status, 200, body[:500])
        return re.search(rb'name="csrf_token" value="([a-f0-9]+)"', body)[1].decode()

    def login(self, role="user", client=None):
        email = {"user": "student", "admin": "admin", "staff": "staff", "other": "other"}[role] + "@example.test"
        token = self.token('index.php', client)
        status, headers, body = self.request("login_register.php", {
            "login": "", "email": email, "password": "TestPass123!", "csrf_token": token}, client=client)
        self.assertEqual(status, 303, body)
        self.assertEqual(headers['Location'], 'manage_applications.php' if role in ('admin', 'staff') else 'home.php')

    def rows(self, sql, parameters=()):
        with sqlite3.connect(self.temp / "test.sqlite") as db:
            db.row_factory = sqlite3.Row
            return [dict(row) for row in db.execute(sql, parameters)]

    def multipart(self, fields, files):
        boundary = 'PortalTestBoundary123456789'
        parts = []
        for name, value in fields.items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode())
        for name, content in files:
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="documents[]"; filename="{name}"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode() + content + b'\r\n')
        parts.append(f'--{boundary}--\r\n'.encode())
        return b''.join(parts), {"Content-Type": f"multipart/form-data; boundary={boundary}"}
