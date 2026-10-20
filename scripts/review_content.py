#!/usr/bin/env python3
"""Validate original drafts and export a STAFF REVIEW ONLY question pack.

Does not connect to Moodle, approve questions or publish courses.
"""
import argparse
import json
from pathlib import Path
import re
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
CATEGORIES = {
    'Drone Licensing & Regulatory Training', 'Drone Mapping, Surveying & GIS',
    'Industry Applications & Case Studies', 'Drone Data Processing & Analysis',
    'Drone Business & Entrepreneurship', 'SkyScholar Industry Playbooks',
    'Technical Tools & Software Training', 'Certification & Career Development',
}


def validate(root=ROOT):
    authoring = root / 'authoring'
    catalogue = json.loads((authoring / 'catalogue.json').read_text())
    if not isinstance(catalogue, list) or not catalogue:
        raise ValueError('Catalogue must be a non-empty flat array')
    ids = set()
    for course in catalogue:
        identity = course['id']
        if identity in ids or not re.fullmatch(r'draft-[a-z0-9-]+', identity):
            raise ValueError('Duplicate or invalid course authoring ID')
        ids.add(identity)
        if (course['category'] not in CATEGORIES or course['status'] != 'draft'
                or course.get('legacy_id') is not None or len(course['objectives']) < 3
                or len(course['modules']) < 4):
            raise ValueError('Invalid draft course metadata')
        for key in ('blueprint', 'lesson'):
            path = (authoring / course[key]).resolve()
            if not path.is_relative_to(authoring.resolve()) or not path.is_file():
                raise ValueError('Missing or unsafe course content path')
            if 'DRAFT' not in path.read_text() or len(path.read_text().split()) < 100:
                raise ValueError('Missing draft label or substantive course content')
    tracks = json.loads((root / 'moodle/local/skyscholar/content/tracks.json').read_text())
    bank = json.loads((authoring / 'za-foundations.draft.json').read_text())
    if bank.get('track') != 'za-remote' or not isinstance(bank.get('questions'), list):
        raise ValueError('Unexpected draft track')
    if not 1 <= len(bank['questions']) <= 1000:
        raise ValueError('Invalid draft bank size')
    ids, counts = set(), dict.fromkeys(tracks['za-remote']['topics'], 0)
    for question in bank['questions']:
        for key in ('id', 'topic', 'objective', 'stem', 'explanation', 'source', 'status', 'reviewer', 'review_date'):
            if not isinstance(question.get(key), str) or len(question[key].encode()) > 10000:
                raise ValueError('Missing or oversized question field: ' + key)
        identity = question['id']
        if identity in ids or not re.fullmatch(r'[a-z0-9][a-z0-9._-]{0,79}', identity):
            raise ValueError('Duplicate or invalid question ID')
        ids.add(identity)
        if question['topic'] not in counts:
            raise ValueError('Unknown subject')
        counts[question['topic']] += 1
        if (question['status'] != 'draft' or question['reviewer'] or question['review_date']):
            raise ValueError('Authoring pack must remain explicitly unreviewed drafts')
        options, feedback = question.get('options'), question.get('feedback')
        if (not isinstance(options, list) or not isinstance(feedback, list)
                or not 2 <= len(options) <= 6 or len(options) != len(feedback)
                or any(not isinstance(value, str) or not value.strip() or len(value.encode()) > 10000
                       for value in options + feedback)):
            raise ValueError('Invalid answer options or feedback')
        if len(set(options)) != len(options) or type(question.get('correct')) is not int:
            raise ValueError('Invalid answer key')
        if not 0 <= question['correct'] < len(options):
            raise ValueError('Answer index out of range')
        for key in ('objective', 'stem', 'explanation', 'source'):
            if not question[key].strip():
                raise ValueError('Empty question content')
        if not (authoring / 'subjects' / (question['topic'] + '.md')).is_file():
            raise ValueError('Missing subject lesson')
    if not all(counts.values()):
        raise ValueError('Every provisional subject needs original draft items')
    return catalogue, bank, counts


def text_node(parent, name, value, formatted=False):
    node = ET.SubElement(parent, name, {'format': 'plain_text'} if formatted else {})
    ET.SubElement(node, 'text').text = value


def export_review(bank, output):
    quiz = ET.Element('quiz')
    for item in bank['questions']:
        question = ET.SubElement(quiz, 'question', {'type': 'multichoice'})
        text_node(question, 'name', 'DRAFT / DO NOT PUBLISH / ' + item['id'])
        text_node(question, 'questiontext', 'STAFF REVIEW ONLY. Not approved aviation training.\n\n' + item['stem'], True)
        text_node(question, 'generalfeedback', item['explanation'] + '\n\n' + item['source'], True)
        for name, value in (('defaultgrade', '1'), ('penalty', '0.3333333'),
                            ('hidden', '0'), ('single', 'true'), ('shuffleanswers', 'true'),
                            ('answernumbering', 'abc')):
            ET.SubElement(question, name).text = value
        for index, option in enumerate(item['options']):
            answer = ET.SubElement(question, 'answer', {'fraction': '100' if index == item['correct'] else '0', 'format': 'plain_text'})
            ET.SubElement(answer, 'text').text = option
            text_node(answer, 'feedback', item['feedback'][index], True)
        tags = ET.SubElement(question, 'tags')
        for label in ('DRAFT-DO-NOT-PUBLISH', item['topic']):
            text_node(tags, 'tag', label)
    output.parent.mkdir(parents=True, exist_ok=True)
    ET.indent(quiz)
    ET.ElementTree(quiz).write(output, encoding='utf-8', xml_declaration=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=ROOT / 'dist/authoring-review.xml')
    args = parser.parse_args()
    catalogue, bank, counts = validate()
    export_review(bank, args.output)
    print(json.dumps({'draft_courses': len(catalogue), 'draft_questions': len(bank['questions']),
                      'subjects': counts, 'approved_questions': 0, 'published_courses': 0,
                      'review_only_xml': str(args.output)}, indent=2))


if __name__ == '__main__':
    main()
