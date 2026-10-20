#!/usr/bin/env python3
"""Build an upload-ready package from a verified upstream Moodle checkout; never packages local runtime data."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import zipfile

REPO = Path(__file__).resolve().parents[1]

def build(core: Path, output: Path):
    lock = json.loads((REPO / 'moodle.lock.json').read_text())
    commit = subprocess.check_output(['git', '-C', str(core), 'rev-parse', 'HEAD'], text=True).strip()
    if commit != lock['commit']:
        raise SystemExit('Upstream checkout does not match moodle.lock.json')
    if subprocess.check_output(['git', '-C', str(core), 'status', '--porcelain'], text=True).strip():
        raise SystemExit('Upstream checkout is modified; use a clean checkout')
    output.parent.mkdir(parents=True, exist_ok=True)
    entries = {}
    tracked = subprocess.check_output(['git', '-C', str(core), 'ls-files', '-z']).decode().split('\0')
    excluded_parts = {'tests', '.github', '.git', 'node_modules', 'fixtures'}
    for relative in tracked:
        if not relative:
            continue
        path = Path(relative)
        # Only the site-level config contains deployment secrets. Nested config.php
        # files and tracked vendor libraries are required upstream application code.
        if relative == 'config.php' or excluded_parts.intersection(path.parts) or path.name in {
                '.gitignore', '.gitattributes', 'phpunit.xml.dist', 'behat.yml.dist',
                'package-lock.json', 'npm-shrinkwrap.json'}:
            continue
        entries['application/' + relative] = core / path
    for path in sorted((REPO / 'moodle/local/skyscholar').rglob('*')):
        if path.is_file() and 'tests' not in path.parts:
            entries['application/local/skyscholar/' + path.relative_to(REPO / 'moodle/local/skyscholar').as_posix()] = path
    entries['application/.htaccess'] = REPO / 'deployment/apache.htaccess'
    for folder in ('deployment', 'docs', 'scripts'):
        for path in sorted((REPO / folder).rglob('*')):
            if path.is_file() and '__pycache__' not in path.parts:
                prefix = '' if folder == 'docs' else 'operator/'
                entries[prefix + path.relative_to(REPO).as_posix()] = path
    for name in ('README.md', 'LICENSE', 'moodle.lock.json', 'SECURITY.md'):
        entries[name] = REPO / name
    entries['START-HERE.md'] = REPO / 'docs/XNEELO-DEPLOYMENT.md'
    manifest = {}
    with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
        for name, path in sorted(entries.items()):
            data = path.read_bytes()
            manifest[name] = hashlib.sha256(data).hexdigest()
            info = zipfile.ZipInfo(name, (2026, 10, 19, 0, 0, 0))
            info.compress_type = zipfile.ZIP_DEFLATED
            info.external_attr = 0o100644 << 16
            archive.writestr(info, data)
        archive.writestr('SHA256-MANIFEST.json', json.dumps(manifest, indent=2) + '\n')
    digest = hashlib.sha256(output.read_bytes()).hexdigest()
    output.with_suffix(output.suffix + '.sha256').write_text(digest + '  ' + output.name + '\n')
    print(json.dumps({'archive': str(output.resolve()), 'bytes': output.stat().st_size,
                      'files': len(entries), 'sha256': digest}, indent=2))

if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--core', required=True, type=Path)
    parser.add_argument('--output', type=Path, default=REPO / 'dist/SkyScholar-Xneelo-v0.1.0.zip')
    args = parser.parse_args()
    build(args.core.resolve(), args.output.resolve())
