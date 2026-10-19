"""Serve synthetic accounts in a temporary copy; never connect to the real database."""
from pathlib import Path
import os
import signal
import sys
import threading

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / 'tests'))
from support import PortalCase

os.environ['PORTAL_TEST_PORT'] = '8099'
PortalCase.setUpClass()
fixture = PortalCase(methodName='runTest')
fixture.setUp()
(PortalCase.root / 'test_reset.php').write_text("""<?php
copy(dirname(__DIR__) . '/baseline.sqlite', dirname(__DIR__) . '/test.sqlite');
header('Content-Type: application/json');
echo '{"success":true}';
""")
stopped = threading.Event()
signal.signal(signal.SIGTERM, lambda *_: stopped.set())
signal.signal(signal.SIGINT, lambda *_: stopped.set())
print('Fixture API ready at ' + PortalCase.url, flush=True)
try:
    stopped.wait()
finally:
    PortalCase.doClassCleanups()
