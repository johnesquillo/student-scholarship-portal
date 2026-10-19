"""Check the real MySQL driver against a local server that never sends a handshake."""
import os
from pathlib import Path
import shutil
import socket
import subprocess
import tempfile
import time
import unittest


class DatabaseConnectionTest(unittest.TestCase):
    def test_unresponsive_database_fails_without_execution_timeout(self):
        with tempfile.TemporaryDirectory(prefix='portal-db-timeout-') as directory, socket.socket() as listener:
            root = Path(directory) / 'www'
            root.mkdir()
            source = Path(__file__).resolve().parents[1] / 'www/db.php'
            shutil.copy2(source, root / 'db.php')
            listener.bind(('127.0.0.1', 0))
            listener.listen(1)
            environment = dict(os.environ, DB_HOST='127.0.0.1', DB_PORT=str(listener.getsockname()[1]),
                               DB_USER='fixture', DB_PASSWORD='fixture', DB_NAME='fixture')
            started = time.monotonic()
            result = subprocess.run(['php', '-d', 'max_execution_time=12', str(root / 'db.php')],
                                    env=environment, capture_output=True, text=True, timeout=10)
            elapsed = time.monotonic() - started
            self.assertEqual(result.returncode, 1, result.stdout + result.stderr)
            self.assertIn('Database unavailable. Check config/local.php', result.stderr)
            self.assertNotIn('Maximum execution time', result.stderr)
            self.assertNotIn('Fatal error', result.stderr)
            self.assertLess(elapsed, 9)
