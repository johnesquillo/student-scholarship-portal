import json
from support import PortalCase, PDF, JPEG


class RequirementsTest(PortalCase):
    def test_anonymous_requests_require_login(self):
        for endpoint in ('application_requirements.php', 'view_requirement.php', 'manage_applications.php', 'inbox.php', 'delete_message.php'):
            status, headers, body = self.request(endpoint)
            self.assertEqual(status, 302)
            self.assertEqual(headers['Location'], 'admin_login.php')
            self.assertNotIn(b'Submitted Requirements', body)
        status, _, body = self.request('update_status.php')
        self.assertEqual(status, 401)
        self.assertFalse(json.loads(body)['success'])

    def test_students_and_missing_roles_are_denied(self):
        for role in ('user', 'student', '', 'administrator'):
            self.request('test_session.php' + ('?role=' + role if role else ''))
            for endpoint in ('application_requirements.php', 'view_requirement.php', 'manage_applications.php', 'inbox.php', 'delete_message.php', 'update_status.php'):
                self.assertEqual(self.request(endpoint)[0], 403)

    def test_admin_and_staff_can_review_and_download(self):
        for role in ('admin', 'staff'):
            client = self.new_client()
            self.login(role, client)
            status, _, body = self.request('application_requirements.php?user_id=7&scholarship_id=3', client=client)
            self.assertEqual(status, 200)
            self.assertIn(b'3 submitted', body)
            self.assertIn(b'Student &lt;script&gt;', body)
            self.assertNotIn(b'Student <script>', body)
            self.assertIn(b'This document is unavailable', body)
            self.assertIn(b'<iframe', body)
            self.assertIn(b'<img', body)
            for index, mime, content in ((0, 'application/pdf', PDF), (1, 'image/jpeg', JPEG)):
                path = f'view_requirement.php?user_id=7&scholarship_id=3&file={index}'
                status, headers, body = self.request(path, client=client)
                self.assertEqual(status, 200, body)
                self.assertEqual(headers['Content-Type'], mime)
                self.assertIn('inline', headers['Content-Disposition'])
                self.assertEqual(headers['X-Content-Type-Options'], 'nosniff')
                self.assertIn('no-store', headers['Cache-Control'])
                self.assertEqual(body, content)
                status, headers, body = self.request(path + '&download=1', client=client)
                self.assertEqual(status, 200)
                self.assertIn('attachment', headers['Content-Disposition'])
                self.assertEqual(body, content)

    def test_empty_and_nonexistent_applications(self):
        self.login('admin')
        status, _, body = self.request('application_requirements.php?user_id=7&scholarship_id=4')
        self.assertEqual(status, 200)
        self.assertIn(b'No requirements were uploaded', body)
        for uid, sid in ((9, 3), (7, 99)):
            for endpoint in ('application_requirements.php', 'view_requirement.php'):
                self.assertEqual(self.request(f'{endpoint}?user_id={uid}&scholarship_id={sid}&file=0')[0], 404)

    def test_invalid_and_out_of_range_inputs(self):
        self.login('admin')
        for query in ('', 'user_id=-1&scholarship_id=3', 'user_id=7&scholarship_id[]=3', 'user_id=7%20OR%201=1&scholarship_id=3'):
            self.assertEqual(self.request('application_requirements.php?' + query)[0], 400)
        for index in ('-1', '../db.php', '', '0&file[]=1'):
            self.assertEqual(self.request('view_requirement.php?user_id=7&scholarship_id=3&file=' + index)[0], 400)
        for sid, index in ((3, 2), (3, 99), (6, 1)):
            self.assertEqual(self.request(f'view_requirement.php?user_id=7&scholarship_id={sid}&file={index}')[0], 404)

    def test_paths_symlinks_and_disguised_files_are_rejected(self):
        self.login('admin')
        for index in range(3):
            self.assertEqual(self.request(f'view_requirement.php?user_id=7&scholarship_id=5&file={index}')[0], 404)
        self.assertEqual(self.request('view_requirement.php?user_id=7&scholarship_id=5&file=3')[0], 415)
