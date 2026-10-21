#!/usr/bin/env python3
"""Quality/completeness gates for the authored self-study edition, not instructor approval."""
import json
from pathlib import Path
import re
ROOT=Path(__file__).resolve().parents[1]

def validate_curricula():
    catalogue=json.loads((ROOT/'authoring/catalogue.json').read_text())
    expected={x['id'][6:] for x in catalogue}|{'radiotelephony-practice'}
    courses={}
    for path in (ROOT/'authoring/full-courses').glob('*.json'):
        data=json.loads(path.read_text());slug=data['slug']
        if slug in courses or slug not in expected or data['status']!='authored-review-pending':raise ValueError('Invalid course identity/status')
        if len(data['units']) != (8 if slug=='radiotelephony-practice' else 4):raise ValueError('Incomplete sequence')
        for i,u in enumerate(data['units'],1):
            for field,minimum in [('lesson',120),('example',30),('task',15),('model_answer',30)]:
                if len(u[field].split())<minimum:raise ValueError(f'{slug} {i} {field}: too little teaching content')
                if re.search(r'\b(?:TODO|TBD|Lorem ipsum)\b',u[field],re.I):raise ValueError('Placeholder content')
            if len(u['checks'])<2 or any(not c['prompt'] or not c['answer'] for c in u['checks']):raise ValueError('Missing knowledge checks/answers')
            if len(u['diagram'])!=4 or not u['reading']:raise ValueError('Missing illustration or reading')
            image=ROOT/f'moodle/local/skyscholarcontent/content/illustrations/{slug}-{i:02}.png'
            if not image.is_file() or image.read_bytes()[:8]!=b'\x89PNG\r\n\x1a\n':raise ValueError('Missing rendered illustration')
        if len(data['rubric'])<5 or len(data['capstone'].split())<20:raise ValueError('Missing final project/criteria')
        courses[slug]=data
    if set(courses)!=expected:raise ValueError('Missing or extra courses')
    radio=json.loads((ROOT/'authoring/radiotelephony/scenarios.json').read_text())
    if len(radio['scenarios'])<24 or len({x['id'] for x in radio['scenarios']})!=len(radio['scenarios']):raise ValueError('Incomplete radio lab')
    for s in radio['scenarios']:
        if not s['model'] or not s['checks'] or not s['notes']:raise ValueError('Incomplete radio feedback')
        if any(not c['any'] or not c['label'] for c in s['checks']):raise ValueError('Missing radio checklist')
    return courses, radio

if __name__=='__main__':
    c,r=validate_curricula()
    print(json.dumps({'courses':len(c),'taught_units':sum(len(x['units']) for x in c.values()),'knowledge_checks':sum(len(u['checks']) for x in c.values() for u in x['units']),'projects':len(c),'illustrations':76,'radio_scenarios':len(r['scenarios']),'status':'authored-review-pending'},indent=2))
