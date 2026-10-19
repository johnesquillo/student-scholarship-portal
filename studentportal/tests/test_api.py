import json
import urllib.parse
from support import PortalCase, PDF


class ApiTest(PortalCase):
    def call(self, action, body=None, query=None, headers=None, client=None):
        path = 'api.php?' + urllib.parse.urlencode(dict(action=action, **(query or {})))
        payload = json.dumps(body).encode() if body is not None else None
        status, response_headers, output = self.request(path, payload, dict({'Content-Type': 'application/json'}, **(headers or {})), client)
        return status, response_headers, json.loads(output)

    def session(self, client=None):
        status, _, data = self.call('session', client=client)
        self.assertEqual(status, 200)
        return data['csrf_token']

    def sign_in(self, role='user', client=None):
        csrf = self.session(client)
        status, _, data = self.call('login', dict(email={'user': 'student', 'admin': 'admin', 'staff': 'staff'}[role] + '@example.test', password='TestPass123!', account_type='admin' if role != 'user' else 'student'), headers={'X-CSRF-Token': csrf}, client=client)
        self.assertEqual(status, 200, data)
        self.assertNotIn('password', data['user'])
        return data['csrf_token']

    def test_browser_authentication_csrf_and_student_privacy(self):
        self.assertEqual(self.call('applications')[0], 401)
        self.assertEqual(self.call('login', dict(email='student@example.test', password='TestPass123!'))[0], 403)
        csrf = self.sign_in()
        status, _, data = self.call('applications', query={'user_id': 8})
        self.assertEqual(status, 200)
        self.assertEqual(len(data['applications']), 4)
        self.assertTrue(all(int(row['user_id']) == 7 for row in data['applications']))
        for action in ('messages', 'requirements', 'document'):
            self.assertEqual(self.call(action, query={'user_id': 8, 'scholarship_id': 3, 'file': 0})[0], 403)
        self.assertEqual(self.call('logout', {}, headers={'X-CSRF-Token': csrf})[0], 200)
        self.assertIsNone(self.call('session')[2]['user'])

    def test_registration_redirect_type_never_grants_admin_access(self):
        csrf = self.session()
        status, _, data = self.call('register', dict(name='New Admin', email='new-admin@example.test', password='TestPass123!', account_type='admin', role='admin'), headers={'X-CSRF-Token': csrf})
        self.assertEqual(status, 200, data)
        self.assertEqual(data['login_type'], 'admin')
        self.assertEqual(self.rows("SELECT role FROM users WHERE email='new-admin@example.test'")[0]['role'], 'user')
        self.assertEqual(self.call('login', dict(email='new-admin@example.test', password='TestPass123!', account_type='admin'), headers={'X-CSRF-Token': csrf})[0], 422)

    def test_admin_review_actions_documents_and_messages(self):
        csrf = self.sign_in('staff')
        self.assertEqual(len(self.call('applications')[2]['applications']), 5)
        status, _, data = self.call('requirements', query={'user_id': 7, 'scholarship_id': 3})
        self.assertEqual(status, 200, data)
        self.assertEqual([file['available'] for file in data['files']], [True, True, False])
        status, headers, output = self.request('api.php?action=document&user_id=7&scholarship_id=3&file=0')
        self.assertEqual(status, 200)
        self.assertEqual(headers['Content-Type'], 'application/pdf')
        self.assertEqual(output, PDF)
        fields = dict(user_id=7, scholarship_id=3, operation='Approved')
        self.assertEqual(self.call('application_action', fields, headers={'X-CSRF-Token': csrf})[0], 200)
        self.assertEqual(self.rows('SELECT status FROM applications WHERE user_id=7 AND scholarship_id=3')[0]['status'], 'Approved')
        self.assertEqual(self.call('application_action', fields, headers={'X-CSRF-Token': csrf})[0], 409)
        self.assertEqual(self.call('messages')[0], 200)
        sender = self.new_client()
        sender_csrf = self.sign_in('user', sender)
        self.assertEqual(self.call('contact', dict(name='Student', email='student@example.test', message='Question'), headers={'X-CSRF-Token': sender_csrf}, client=sender)[0], 200)
        message_id = self.call('messages')[2]['messages'][0]['id']
        self.assertEqual(self.call('delete_message', {'id': message_id}, headers={'X-CSRF-Token': csrf})[0], 200)
        self.assertFalse(self.rows('SELECT * FROM contact_message'))

    def test_api_submissions_use_the_same_upload_validation(self):
        csrf = self.sign_in()
        fields = dict(scholarship_id=10, name='Student', email='student@example.test', course='BSCS', gwa='1.75', year='2nd Year')
        body, headers = self.multipart(fields, [('requirement.pdf', PDF)])
        headers['X-CSRF-Token'] = csrf
        status, _, output = self.request('api.php?action=apply', body, headers)
        self.assertEqual(status, 200, output)
        self.assertTrue(json.loads(output)['success'])
        self.assertEqual(len(self.rows('SELECT * FROM applications WHERE user_id=7 AND scholarship_id=10')), 1)
        body, headers = self.multipart(dict(fields, scholarship_id=11), [('bad.pdf', b'not a PDF')])
        headers['X-CSRF-Token'] = csrf
        self.assertEqual(self.request('api.php?action=apply', body, headers)[0], 422)
        self.assertFalse(self.rows('SELECT * FROM applications WHERE scholarship_id=11'))
        self.assertEqual(self.call('contact', dict(name='Student', email='student@example.test', message='Hello office'), headers={'X-CSRF-Token': csrf})[0], 200)
        self.assertEqual(len(self.rows('SELECT * FROM contact_message')), 1)

    def test_native_bearer_sessions_are_revoked_at_logout(self):
        headers = {'X-Portal-Native': '1'}
        status, _, data = self.call('login', dict(email='admin@example.test', password='TestPass123!', account_type='admin'), headers=headers)
        self.assertEqual(status, 200, data)
        self.assertEqual(len(data['token']), 64)
        self.assertIsNone(self.call('session', headers={'X-Portal-Native': '1'})[2]['user'])
        native = self.new_client()
        headers['Authorization'] = 'Bearer ' + data['token']
        self.assertEqual(self.call('messages', headers=headers, client=native)[0], 200)
        self.assertEqual(self.call('logout', {}, headers=headers, client=native)[0], 200)
        self.assertEqual(self.call('messages', headers=headers, client=native)[0], 401)

    def test_cors_rejects_untrusted_origins(self):
        status, headers, _ = self.call('session', headers={'Origin': 'http://127.0.0.1:8081'})
        self.assertEqual(status, 200)
        self.assertEqual(headers['Access-Control-Allow-Origin'], 'http://127.0.0.1:8081')
        self.assertEqual(headers['Access-Control-Allow-Credentials'], 'true')
        self.assertEqual(self.call('session', headers={'Origin': 'https://untrusted.example'})[0], 403)
