#!/usr/bin/env python3
"""Verify integrity and the safe upload layout of a release archive."""
import hashlib
import json
from pathlib import PurePosixPath
import sys
import zipfile

with zipfile.ZipFile(sys.argv[1]) as archive:
    assert archive.testzip() is None, 'Corrupt archive member'
    names = set(archive.namelist())
    for required in ('application/index.php', 'application/admin/cli/install.php',
                     'application/lib/setup.php', 'application/local/skyscholar/version.php',
                     'application/local/skyscholar/cli/manage.php',
                     'application/local/skyscholar/content/demo.json',
                     'application/.htaccess', 'START-HERE.md', 'LICENSE', 'SHA256-MANIFEST.json'):
        assert required in names, required
    for name in names:
        path = PurePosixPath(name)
        assert not path.is_absolute() and '..' not in path.parts, name
        assert '.git' not in path.parts and 'moodledata' not in path.parts, name
        assert path.name not in ('config.php', '.env'), name
        assert not name.endswith(('.log', '.mbz')), name
    manifest = json.loads(archive.read('SHA256-MANIFEST.json'))
    assert set(manifest) == names - {'SHA256-MANIFEST.json'}
    for name, digest in manifest.items():
        assert hashlib.sha256(archive.read(name)).hexdigest() == digest, name
    print(f'PASS {len(manifest)} file checksums, complete Moodle/plugin layout, no site config or runtime data')
