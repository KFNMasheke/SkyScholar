import importlib.util
import json
from pathlib import Path
import shutil
import tempfile
import unittest
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location('review_content', ROOT / 'scripts/review_content.py')
review = importlib.util.module_from_spec(spec)
spec.loader.exec_module(review)


class AuthoringTests(unittest.TestCase):
    def test_catalogue_subjects_and_review_xml(self):
        catalogue, bank, counts = review.validate()
        self.assertEqual(len(catalogue), 14)
        self.assertEqual(len(bank['questions']), 35)
        self.assertTrue(all(count == 5 for count in counts.values()))
        with tempfile.TemporaryDirectory() as temp:
            output = Path(temp) / 'review.xml'
            review.export_review(bank, output)
            questions = ET.parse(output).findall('question')
            self.assertEqual(len(questions), 35)
            for question in questions:
                self.assertIn('DRAFT / DO NOT PUBLISH', question.findtext('name/text'))
                self.assertEqual(sum(int(a.get('fraction')) for a in question.findall('answer')), 100)

    def test_no_silent_approval_or_invalid_answer(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            shutil.copytree(ROOT / 'authoring', root / 'authoring')
            target = root / 'moodle/local/skyscholar/content'
            target.mkdir(parents=True)
            shutil.copyfile(ROOT / 'moodle/local/skyscholar/content/tracks.json', target / 'tracks.json')
            path = root / 'authoring/za-foundations.draft.json'
            original = json.loads(path.read_text())
            for key, value in (('status', 'approved'), ('reviewer', 'invented reviewer'), ('correct', 7)):
                data = json.loads(json.dumps(original))
                data['questions'][0][key] = value
                path.write_text(json.dumps(data))
                with self.assertRaises(ValueError):
                    review.validate(root)
