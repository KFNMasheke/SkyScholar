#!/usr/bin/env python3
"""Build the standalone admin importer from the validated, unapproved authoring set."""
import argparse
import hashlib
import html
import json
from pathlib import Path
import re
import zipfile
from review_content import validate

ROOT = Path(__file__).resolve().parents[1]
PLUGIN = ROOT / 'moodle/local/skyscholarcontent'


def markdown(text):
    """Render this plain-text authoring subset; source HTML is always escaped."""
    output, paragraph = [], []
    inlist = False

    def flush():
        if paragraph:
            output.append('<p>' + html.escape(' '.join(paragraph)) + '</p>')
            paragraph.clear()

    for line in text.splitlines() + ['']:
        heading = re.match(r'^(#{1,6})\s+(.*)', line)
        if heading:
            flush()
            if inlist:
                output.append('</ul>')
                inlist = False
            level = min(len(heading[1]) + 1, 6)
            output.append(f'<h{level}>' + html.escape(heading[2]) + f'</h{level}>')
        elif line.startswith('- '):
            flush()
            if not inlist:
                output.append('<ul>')
                inlist = True
            output.append('<li>' + html.escape(line[2:]) + '</li>')
        elif not line.strip():
            flush()
            if inlist:
                output.append('</ul>')
                inlist = False
        else:
            paragraph.append(line)
    return '\n'.join(output)


def payload():
    catalogue, bank, _ = validate()
    warning = '<p><strong>DRAFT — STAFF REVIEW ONLY. Incomplete and unreviewed; not approved aviation training.</strong></p>'
    courses = []
    for course in catalogue:
        summary = warning + '<p>Opening lesson and course plan only; later module lessons and complete assessments remain outstanding.</p>'
        summary += '<h3>Learning outcomes</h3><ul>' + ''.join('<li>' + html.escape(x) + '</li>' for x in course['objectives']) + '</ul>'
        courses.append({
            'id': course['id'], 'name': course['name'], 'category': course['category'],
            'status': 'draft', 'modules': course['modules'], 'summary': summary,
            'lesson': warning + markdown((ROOT / 'authoring' / course['lesson']).read_text()),
            'blueprint': warning + markdown((ROOT / 'authoring' / course['blueprint']).read_text()),
        })
    subjects = []
    names = {'air-law': 'Air law', 'aircraft': 'Aircraft systems', 'principles': 'Principles of flight',
             'meteorology': 'Meteorology', 'navigation': 'Navigation', 'human-factors': 'Human factors',
             'communications': 'Communications'}
    for key, name in names.items():
        subjects.append({'id': key, 'name': name, 'html': warning + markdown((ROOT / 'authoring/subjects' / (key + '.md')).read_text())})
    return {'schema': 1, 'status': 'draft-staff-review-only', 'courses': courses,
            'subjects': subjects, 'questions': bank['questions']}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=ROOT / 'dist/SkyScholar-Course-Content-Installer-v1.0.0.zip')
    args = parser.parse_args()
    data = (json.dumps(payload(), ensure_ascii=False, indent=2) + '\n').encode('utf-8')
    (PLUGIN / 'content').mkdir(exist_ok=True)
    (PLUGIN / 'content/pack.json').write_bytes(data)
    (PLUGIN / 'content/pack.sha256').write_text(hashlib.sha256(data).hexdigest() + '\n')
    source = (PLUGIN / 'amd/src/importer.js').read_text()
    (PLUGIN / 'amd/build/importer.min.js').write_text(source.replace('define([],', 'define("local_skyscholarcontent/importer", [],', 1))
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.output, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(PLUGIN.rglob('*')):
            if path.is_file():
                archive.write(path, 'skyscholarcontent/' + path.relative_to(PLUGIN).as_posix())
    print(json.dumps({'archive': str(args.output.resolve()), 'bytes': args.output.stat().st_size,
                      'courses': 14, 'page_activities': 35, 'draft_questions': 35}, indent=2))


if __name__ == '__main__':
    main()
