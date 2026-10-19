from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import unittest
import urllib.error
import urllib.request
from support import NoRedirect


class BrowserRouterTest(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        temporary = tempfile.TemporaryDirectory(prefix='portal-router-')
        cls.addClassCleanup(temporary.cleanup)
        cls.root = Path(temporary.name)
        (cls.root / 'scripts').mkdir()
        shutil.copy2(Path(__file__).resolve().parents[1] / 'scripts/browser-router.php', cls.root / 'scripts/browser-router.php')
        (cls.root / 'www/uploads').mkdir(parents=True)
        (cls.root / 'www/uploads/private.pdf').write_text('private document')
        (cls.root / 'www/api.php').write_text('<?php echo "api-response";')
        (cls.root / 'www/index.php').write_text('<?php echo "legacy-login";')
        (cls.root / 'mobile/dist/_expo').mkdir(parents=True)
        (cls.root / 'mobile/dist/index.html').write_text('<html>React Native portal</html>')
        (cls.root / 'mobile/dist/_expo/app.js').write_text('console.log("portal");')
        (cls.root / 'mobile/dist/secret.php').write_text('secret')
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        cls.url = f'http://127.0.0.1:{port}/'
        cls.server = subprocess.Popen(['php', '-S', f'127.0.0.1:{port}', '-t', str(cls.root / 'www'), str(cls.root / 'scripts/browser-router.php')], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        cls.addClassCleanup(cls.stop_server)
        for _ in range(100):
            try:
                urllib.request.urlopen(cls.url, timeout=1).close()
                break
            except OSError:
                time.sleep(0.05)
        else:
            raise RuntimeError('Router test server did not start')

    @classmethod
    def stop_server(cls):
        cls.server.terminate()
        cls.server.wait(timeout=5)

    def get(self, path):
        try:
            response = urllib.request.build_opener(NoRedirect()).open(self.url + path, timeout=5)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            return response.status, response.headers, response.read()

    def test_exported_frontend_assets_and_php_api(self):
        self.assertEqual(self.get('')[2], b'<html>React Native portal</html>')
        status, headers, body = self.get('_expo/app.js')
        self.assertEqual(status, 200)
        self.assertEqual(headers['Content-Type'], 'application/javascript')
        self.assertIn(b'console.log', body)
        self.assertEqual(self.get('api.php')[2], b'api-response')
        self.assertEqual(self.get('scholarships')[2], b'<html>React Native portal</html>')

    def test_existing_page_links_open_the_corresponding_native_screen(self):
        for path, target in [('admin_login.php', '/admin-login'), ('index.php?form=register', '/register'),
                             ('scholarships.php', '/scholarships'), ('apply.php?scholarship_id=3', '/apply?scholarship=3'),
                             ('application_requirements.php?user_id=7&scholarship_id=3', '/requirements?scholarship=3&user=7')]:
            status, headers, _ = self.get(path)
            self.assertEqual(status, 303)
            self.assertEqual(headers['Location'], target)

    def test_private_uploads_and_unrecognized_export_file_types_are_not_served(self):
        self.assertEqual(self.get('uploads/private.pdf')[0], 403)
        self.assertEqual(self.get('secret.php')[0], 404)
