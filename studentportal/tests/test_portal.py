import json
import re
from urllib.parse import urlsplit
from support import PortalCase, PDF, JPEG


class PortalTest(PortalCase):
    def test_registration_hashes_password_and_prevents_role_escalation(self):
        token = self.token('index.php')
        status, headers, _ = self.request('login_register.php', {
            'register': '', 'name': 'New Student', 'email': 'NEW@example.test',
            'password': 'A new password!', 'account_type': 'student', 'role': 'admin', 'csrf_token': token})
        self.assertEqual(status, 303)
        self.assertEqual(headers['Location'], 'index.php')
        account = self.rows("SELECT * FROM users WHERE email='new@example.test'")[0]
        self.assertEqual(account['role'], 'user')
        self.assertNotEqual(account['password'], 'A new password!')
        self.assertTrue(account['password'].startswith('$2y$'))
        login_page = self.request(headers['Location'])
        self.assertEqual(login_page[0], 200)
        self.assertIn(b'Account created', login_page[2])
        self.assertIn(b'<h2>Student Login</h2>', login_page[2])
        self.assertEqual(self.request('login_register.php', {
            'login': '', 'email': 'new@example.test', 'password': 'A new password!',
            'csrf_token': token})[1]['Location'], 'home.php')

    def test_invalid_duplicate_and_unauthorized_registration(self):
        token = self.token('index.php')
        for extra in ({'email': 'bad'}, {'password': 'short'}, {'email': 'student@example.test'}):
            data = dict(register='', name='Student', email='unique@example.test', password='TestPass123!', csrf_token=token)
            data.update(extra)
            self.assertEqual(self.request('login_register.php', data)[0], 303)
            self.assertTrue(self.request('index.php')[2].count(b'error-message'))
        self.assertEqual(len(self.rows('SELECT * FROM users')), 4)
        self.assertEqual(self.request('login_register.php', {'register': ''})[0], 403)
        self.assertEqual(self.request('login_register.php')[0], 405)

    def test_admin_registration_shows_activation_instructions_without_granting_access(self):
        token = self.token('index.php?form=register')
        page = self.request('index.php?form=register')[2]
        self.assertIn(b'for="register-account-type">Registering as</label>', page)
        self.assertIn(b'Admin (activation required)', page)
        fields = dict(register='', name='Office Applicant', email='new-admin@example.test',
                      password='TestPass123!', account_type='admin', role='admin', csrf_token=token)
        status, headers, _ = self.request('login_register.php', fields)
        self.assertEqual(status, 303)
        self.assertEqual(headers['Location'], 'admin_login.php')
        self.assertEqual(self.rows("SELECT role FROM users WHERE email='new-admin@example.test'")[0]['role'], 'user')
        login_page = self.request(headers['Location'])
        self.assertEqual(login_page[0], 200)
        self.assertIn(b'activate admin access', login_page[2])
        self.assertIn(b'<h2>Admin / Staff Login</h2>', login_page[2])
        self.assertEqual(self.request('login_register.php', {
            'login': '', 'login_context': 'admin', 'email': fields['email'],
            'password': fields['password'], 'csrf_token': token})[1]['Location'], 'admin_login.php')
        self.assertEqual(self.request('manage_applications.php')[0], 302)

    def test_registration_type_validation_and_selection_survives_errors(self):
        token = self.token('index.php?form=register')
        fields = dict(register='', name='Applicant', email='new@example.test',
                      password='TestPass123!', account_type='invalid', csrf_token=token)
        self.assertEqual(self.request('login_register.php', fields)[0], 303)
        self.assertIn(b'Please choose Student or Admin', self.request('index.php')[2])
        self.assertEqual(self.request('login_register.php', dict(fields, account_type='admin', password='short'))[0], 303)
        self.assertRegex(self.request('index.php')[2], rb'<option value="admin" selected>')
        self.assertFalse(self.rows("SELECT * FROM users WHERE email='new@example.test'"))

    def test_login_rejects_invalid_password_and_student_admin_login(self):
        token = self.token('admin_login.php')
        for email, password in (('student@example.test', 'wrong'), ('nobody@example.test', 'wrong'),
                                ('student@example.test', 'TestPass123!')):
            status, headers, _ = self.request('login_register.php', {
                'login': '', 'login_context': 'admin', 'email': email, 'password': password, 'csrf_token': token})
            self.assertEqual(status, 303)
            self.assertEqual(headers['Location'], 'admin_login.php')
            self.assertIn(b'error-message', self.request('admin_login.php')[2])
            self.assertEqual(self.request('manage_applications.php')[0], 302)

    def test_login_rotates_session_and_revisiting_login_preserves_it(self):
        self.token('index.php')
        old = next(c.value for h in self.client.handlers if hasattr(h, 'cookiejar') for c in h.cookiejar)
        self.login()
        new = next(c.value for h in self.client.handlers if hasattr(h, 'cookiejar') for c in h.cookiejar)
        self.assertNotEqual(old, new)
        self.assertEqual(self.request('index.php')[1]['Location'], 'home.php')
        self.assertEqual(self.request('home.php')[0], 200)

    def test_logout_requires_csrf_and_ends_session(self):
        self.login()
        token = self.token('home.php')
        self.assertEqual(self.request('logout.php')[0], 405)
        self.assertEqual(self.request('logout.php', {'csrf_token': 'bad'})[0], 403)
        self.assertEqual(self.request('home.php')[0], 200)
        status, headers, _ = self.request('logout.php', {'csrf_token': token})
        self.assertEqual(status, 303)
        self.assertIn('expires=', headers.get('Set-Cookie', '').lower())
        self.assertEqual(self.request('home.php')[1]['Location'], 'index.php')

    def test_student_pages_require_login_and_only_show_own_applications(self):
        for path in ('home.php', 'view_applications.php', 'apply.php', 'scholarships.php', 'contact.php', 'FAQ.php'):
            self.assertEqual(self.request(path)[1]['Location'], 'index.php')
        self.login()
        for path in ('home.php', 'view_applications.php'):
            status, _, body = self.request(path)
            self.assertEqual(status, 200)
            self.assertNotIn(b'Other Student Private', body)
            self.assertIn(b'50%', body)
            self.assertIn(b'25%', body)
        body = self.request('view_applications.php')[2]
        self.assertIn(b'Student &lt;script&gt;', body)
        other = self.new_client()
        self.login('other', other)
        body = self.request('view_applications.php', client=other)[2]
        self.assertIn(b'Other Student Private', body)
        self.assertNotIn(b'Student &lt;script&gt;', body)

    def test_scholarship_search_deadlines_and_preselection(self):
        self.login()
        status, _, body = self.request('scholarships.php?search=Merit')
        self.assertEqual(status, 200)
        self.assertIn(b'Merit Scholarship', body)
        self.assertNotIn(b'New Scholarship', body)
        self.assertNotIn(b'Expired Scholarship', body)
        self.assertNotIn(b'Inactive Scholarship', body)
        self.assertIn(b'No scholarships found', self.request('scholarships.php?search=no-match')[2])
        self.assertEqual(self.request('scholarships.php?search[]=malformed')[0], 200)
        self.assertRegex(self.request('apply.php?scholarship_id=10')[2], rb'<option value="10" selected>')

    def application_fields(self):
        return dict(csrf_token=self.token('apply.php'), scholarship_id=10, name='New Applicant',
                    email='student@example.test', course='BSCS', gwa='1.5', year='2nd Year')

    def test_requested_programs_are_selectable_and_accept_applications_without_deadlines(self):
        self.login()
        status, _, listing = self.request('scholarships.php')
        self.assertEqual(status, 200)
        self.assertIn(b'No deadline announced', listing)
        for prefix in ('CARRI', 'BOARD Program', 'CREATE', 'SEAL'):
            program = self.rows('SELECT * FROM scholarships WHERE name LIKE ?', (prefix + '%',))[0]
            self.assertIsNone(program['deadline'])
            self.assertEqual(program['status'], 'active')
            self.assertIn(prefix.encode(), listing)
            sid = program['scholarship_id']
            self.assertIn(f'apply.php?scholarship_id={sid}'.encode(), listing)
            status, _, form = self.request(f'apply.php?scholarship_id={sid}')
            self.assertEqual(status, 200)
            self.assertIn(f'<option value="{sid}" selected>'.encode(), form)
            self.assertIn(b'No deadline announced', form)
            fields = dict(self.application_fields(), scholarship_id=sid)
            body, headers = self.multipart(fields, [('requirement.pdf', PDF)])
            status, response_headers, output = self.request('apply.php', body, headers)
            self.assertEqual(status, 303, output[:500])
            self.assertEqual(response_headers['Location'], 'apply.php?success=1')
            self.assertEqual(len(self.rows('SELECT * FROM applications WHERE user_id=7 AND scholarship_id=?', (sid,))), 1)

    def test_submit_documents_and_admin_review(self):
        self.login()
        fields = self.application_fields()
        body, headers = self.multipart(fields, [('grades.pdf', PDF), ('id.jpg', JPEG)])
        status, response_headers, output = self.request('apply.php', body, headers)
        self.assertEqual(status, 303, output[:500])
        self.assertEqual(response_headers['Location'], 'apply.php?success=1')
        application = self.rows('SELECT * FROM applications WHERE user_id=7 AND scholarship_id=10')[0]
        names = application['uploaded_files'].split(',')
        self.assertEqual(len(names), 2)
        for name in names:
            self.assertTrue((self.private / name).is_file())
            self.assertFalse((self.uploads / name).exists())
            self.assertEqual(self.request('uploads/' + name)[0], 404)
        reviewer = self.new_client()
        self.login('staff', reviewer)
        page = self.request('application_requirements.php?user_id=7&scholarship_id=10', client=reviewer)
        self.assertEqual(page[0], 200)
        self.assertIn(b'2 submitted', page[2])
        self.assertEqual(self.request('view_requirement.php?user_id=7&scholarship_id=10&file=0', client=reviewer)[2], PDF)
        # A duplicate submission must not leave additional documents.
        before = set(self.private.iterdir())
        body, headers = self.multipart(fields, [('again.pdf', PDF)])
        self.assertIn(b'already applied', self.request('apply.php', body, headers)[2])
        self.assertEqual(set(self.private.iterdir()), before)

    def test_failed_uploads_and_database_failures_leave_no_files(self):
        self.login()
        fields = self.application_fields()
        for files in ([], [('bad.pdf', b'not a PDF')], [('good.pdf', PDF), ('bad.jpg', PDF)],
                      [('oversize.pdf', PDF + b'0' * (5 * 1024 * 1024))], [('f.pdf', PDF)] * 11):
            before = set(self.private.iterdir())
            body, headers = self.multipart(fields, files)
            status, _, output = self.request('apply.php', body, headers)
            self.assertEqual(status, 200, output[:500])
            self.assertIn(b'error-box', output)
            self.assertEqual(set(self.private.iterdir()), before)
            self.assertFalse(self.rows('SELECT * FROM applications WHERE scholarship_id=10'))
        fields['course'] = 'FAIL_SAVE'
        body, headers = self.multipart(fields, [('good.pdf', PDF)])
        status, _, output = self.request('apply.php', body, headers)
        self.assertEqual(status, 200)
        self.assertIn(b'could not be saved', output)
        self.assertEqual([p.name for p in self.private.iterdir()], ['grades.pdf'])

    def test_application_validation_rejects_closed_scholarships_and_bad_fields(self):
        self.login()
        fields = self.application_fields()
        for overrides in ({'scholarship_id': 8}, {'scholarship_id': 9}, {'scholarship_id': 99},
                          {'gwa': 'not-a-grade'}, {'gwa': '-1'}, {'year': 'invalid'}, {'email': 'bad'}):
            body, headers = self.multipart(dict(fields, **overrides), [('test.pdf', PDF)])
            status, _, output = self.request('apply.php', body, headers)
            self.assertEqual(status, 200)
            self.assertIn(b'error-box', output)
        body, headers = self.multipart(dict(fields, csrf_token='wrong'), [('test.pdf', PDF)])
        self.assertEqual(self.request('apply.php', body, headers)[0], 403)
        self.assertEqual([p.name for p in self.private.iterdir()], ['grades.pdf'])

    def test_status_actions_are_protected_and_visible_to_student(self):
        self.login('admin')
        token = self.token('manage_applications.php')
        self.assertEqual(self.request('update_status.php')[0], 405)
        fields = dict(user_id=7, scholarship_id=3, status='Approved', csrf_token=token)
        self.assertEqual(self.request('update_status.php', dict(fields, csrf_token='wrong'))[0], 403)
        self.assertEqual(self.request('update_status.php', dict(fields, status='invalid'))[0], 400)
        self.assertEqual(self.request('update_status.php', dict(fields, user_id=99))[0], 404)
        status, _, body = self.request('update_status.php', fields)
        self.assertEqual(status, 200)
        self.assertTrue(json.loads(body)['success'])
        self.assertEqual(self.rows('SELECT status FROM applications WHERE user_id=7 AND scholarship_id=3')[0]['status'], 'Approved')
        student = self.new_client()
        self.login('user', student)
        self.assertIn(b'Approved', self.request('view_applications.php', client=student)[2])
        # Repeating an existing status remains successful.
        self.assertEqual(self.request('update_status.php', fields)[0], 200)

    def test_remove_and_return_to_pending(self):
        self.login('staff')
        token = self.token('manage_applications.php')
        fields = dict(user_id=7, scholarship_id=5, action='reapply', csrf_token=token)
        original_date = self.rows('SELECT application_date FROM applications WHERE scholarship_id=5')[0]['application_date']
        self.assertEqual(self.request('manage_applications.php', dict(fields, csrf_token='bad'))[0], 403)
        self.assertEqual(self.request('manage_applications.php', fields)[0], 303)
        row = self.rows('SELECT status, application_date FROM applications WHERE scholarship_id=5')[0]
        self.assertEqual(row, {'status': 'Pending', 'application_date': original_date})
        self.assertEqual(self.request('manage_applications.php', dict(fields, action='remove'))[0], 303)
        self.assertFalse(self.rows('SELECT * FROM applications WHERE scholarship_id=5'))
        self.assertEqual(self.request('application_requirements.php?user_id=7&scholarship_id=5')[0], 404)

    def test_contact_submission_inbox_and_delete(self):
        self.login()
        token = self.token('contact.php')
        fields = dict(name='Student', email='student@example.test', message='<script>hello</script>', csrf_token=token)
        self.assertEqual(self.request('submit_contact.php')[0], 405)
        self.assertEqual(self.request('submit_contact.php', dict(fields, csrf_token='bad'))[0], 403)
        self.assertEqual(self.request('submit_contact.php', dict(fields, email='bad'))[1]['Location'], 'contact.php?error=invalid')
        self.assertEqual(self.request('submit_contact.php', fields)[1]['Location'], 'contact.php?success=1')
        message = self.rows('SELECT * FROM contact_message')[0]
        self.assertEqual(self.request('delete_message.php', {'id': message['id'], 'csrf_token': token})[0], 403)
        reviewer = self.new_client()
        self.login('admin', reviewer)
        token = self.token('inbox.php', reviewer)
        page = self.request('inbox.php', client=reviewer)[2]
        self.assertIn(b'&lt;script&gt;hello&lt;/script&gt;', page)
        self.assertNotIn(b'<script>hello</script>', page)
        self.assertEqual(self.request('delete_message.php', client=reviewer)[0], 405)
        self.assertEqual(self.request('delete_message.php', {'id': message['id']}, client=reviewer)[0], 403)
        self.assertEqual(self.request('delete_message.php', {'id': message['id'], 'csrf_token': token}, client=reviewer)[1]['Location'], 'inbox.php?status=deleted')
        self.assertFalse(self.rows('SELECT * FROM contact_message'))

    def test_pages_have_working_local_assets_and_navigation(self):
        public = ('index.php', 'admin_login.php')
        student = ('home.php', 'view_applications.php', 'scholarships.php', 'apply.php', 'FAQ.php', 'contact.php')
        admin = ('manage_applications.php', 'inbox.php', 'application_requirements.php?user_id=7&scholarship_id=3')
        for role, pages in ((None, public), ('user', student), ('admin', admin)):
            client = self.new_client()
            if role:
                self.login(role, client)
            for page in pages:
                status, _, body = self.request(page, client=client)
                self.assertEqual(status, 200, (page, body[:300]))
                self.assertNotIn(b'Fatal error', body)
                for link in re.findall(rb'(?:src|href|action)="([^"<>]+)"', body):
                    link = link.decode()
                    if link.startswith(('http', '#', 'mailto:', 'tel:')):
                        continue
                    path = urlsplit(link.replace('&amp;', '&')).path
                    self.assertTrue((self.root / path).is_file(), (page, link))
