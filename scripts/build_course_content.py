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


def legacy_payload():
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


def payload():
    from validate_curricula import validate_curricula
    curricula, radio = validate_curricula()
    data = legacy_payload()
    references = json.loads((ROOT / 'authoring/references-v2.json').read_text())
    radio_course = curricula['radiotelephony-practice']
    data['schema'] = 2
    data['status'] = 'authored-self-study-review-pending'
    data['radio'] = radio
    data['assets'] = {p.name: hashlib.sha256(p.read_bytes()).hexdigest()
                      for p in sorted((PLUGIN / 'content/illustrations').glob('*.png'))}
    data['courses'].append({'id': 'draft-radiotelephony-practice', 'name': 'Radiotelephony: Communication and Practice',
        'category': 'Drone Licensing & Regulatory Training', 'status': 'draft',
        'modules': ['Station skills, phonetics and numbers', 'Initial contact and structured messages',
                    'Readback, clarification and message discipline', 'Urgency rehearsal and integrated practice'],
        'summary': '', 'lesson': '', 'blueprint': ''})
    notice = '<p><strong>Authored self-study edition. Technical and instructor review pending. This is not approved practical training or a licensing qualification.</strong></p>'
    special = {('drone-flight-fundamentals',1):'thrust-components.png',
               ('drone-mapping-and-surveying',2):'camera-sampling.png',
               ('drone-mapping-and-surveying',3):'control-checkpoints.png',
               ('commercial-drone-operations',3):'markup-margin.png',
               ('introduction-to-drone-technology',3):'battery-energy.png',
               ('drone-flight-fundamentals',2):'aircraft-axes.png',
               ('aerial-photography-and-videography',1):'storyboard-sequence.png',
               ('aerial-photography-and-videography',2):'exposure-choices.png',
               ('drone-mapping-and-surveying',1):'crs-transform.png',
               ('weather-and-airspace-management',2):'wind-frames.png',
               ('specialized-applications',2):'thermal-interpretation.png',
               ('radiotelephony-practice',2):'phonetic-alphabet.png'}
    def para(text): return '<p>' + html.escape(text) + '</p>'
    for course in data['courses']:
        slug=course['id'][6:]; full=curricula[slug]
        course['previoussummary']=course['summary']
        course['summary']=notice+para('A complete foundational self-study sequence with '+str(len(full['units']))+' taught units, worked examples, original diagrams, exercises, answer guides, knowledge checks and a final project. See the course study guide for scope and required external review.')
        pages=[]
        guide=notice+'<h2>How to work through this course</h2>'+para('Read the units in order, inspect the concept illustrations, work each exercise before revealing the model answer and explain the knowledge checks in your own words. Complete the final project and use the five criteria for self-review before submitting it for human assessment. Reading a page or matching a checklist is not proof of practical competence.')
        guide+='<h3>Sequence</h3><ol>'+''.join('<li>'+html.escape(u['title'])+'</li>' for u in full['units'])+'</ol>'
        guide+='<h3>Reference register</h3>'+para(references['review_status'])+'<ul>'+''.join('<li><a href="'+html.escape(r['url'],quote=True)+'">'+html.escape(r['title'])+'</a> — '+html.escape(r['use'])+'</li>' for r in references['references'])+'</ul>'
        pages.append(dict(key='v2-study-guide',name='Self-study guide, scope and references',html=guide,section=0,assets=[]))
        for i,u in enumerate(full['units'],1):
            asset=f'{slug}-{i:02}.png';assets=[asset]
            caption='Concept sequence: '+' → '.join(u['diagram'])+'. Read with the lesson; not an operational procedure.'
            body=notice+'<h2>'+html.escape(u['title'])+'</h2>'+markdown(u['lesson'])
            body+='<figure><img src="@@PLUGINFILE@@/'+asset+'" alt="'+html.escape(caption,quote=True)+'" style="max-width:100%;height:auto" width="1600" height="900"><figcaption>'+html.escape(caption)+'</figcaption></figure>'
            if (slug,i) in special:
                diagram=special[(slug,i)];assets.append(diagram)
                body+='<figure><img src="@@PLUGINFILE@@/'+diagram+'" alt="'+html.escape({'battery-energy.png':'Fictional nominal energy: 14.8 volts times 6 amp-hours equals 88.8 watt-hours. Safe usable duration depends on the aircraft, load, conditions and approved reserve.', 'aircraft-axes.png':'Top view distinguishes longitudinal roll, lateral pitch and vertical yaw axes; attitude is not ground track.', 'storyboard-sequence.png':'Six original schematic frames progress from place and relationships to activity and detail. Not captured imagery or a flight plan.', 'exposure-choices.png':'Shutter time, adjustable aperture and ISO have different motion, optical and noise tradeoffs; inspect actual output.', 'crs-transform.png':'Assigning a CRS defines interpretation; transforming computes coordinate values with a valid operation.', 'wind-frames.png':'Simplified one-dimensional example: 6 metres per second air-relative speed minus 2 metres per second headwind gives 4 metres per second groundspeed.', 'thermal-interpretation.png':'A surface supplies emitted and reflected radiation to a sensor/model; display colours alone do not diagnose a fault.', 'phonetic-alphabet.png':'Complete English ICAO-style phonetic alphabet from Alfa through Zulu, including Juliett and X-ray.', 'thrust-components.png':'Simplified tilted thrust resolves into vertical support and horizontal force. T cos(theta) equals weight in vertical equilibrium.','camera-sampling.png':'Flat-ground camera geometry: GSD is approximately local height times pixel pitch divided by focal length. Sampling is not an accuracy claim.','control-checkpoints.png':'Control points fit a model; separately marked withheld checkpoints test it independently. Layout is illustrative only.','markup-margin.png':'Fictional cost R4000, selling price R5000, contribution R1000: markup 25 percent, margin 20 percent.'}[diagram],quote=True)+'" style="max-width:100%;height:auto" width="1600" height="900"></figure>'
            body+='<h3>Worked example</h3>'+para(u['example'])+'<h3>Your exercise</h3>'+para(u['task'])
            body+='<details><summary>Model answer and reasoning</summary>'+para(u['model_answer'])+'</details><h3>Knowledge checks</h3>'
            for check in u['checks']:body+='<details><summary>'+html.escape(check['prompt'])+'</summary>'+para(check['answer'])+'</details>'
            body+='<h3>Further reading / verification</h3><ul>'+''.join('<li>'+html.escape(r)+'</li>' for r in u['reading'])+'</ul>'
            section=(i+1)//2 if slug=='radiotelephony-practice' else i
            pages.append(dict(key=f'v2-unit-{i:02}',name=u['title'],html=body,section=section,assets=assets))
        capstone=notice+'<h2>Final project</h2>'+para(full['capstone'])+'<h3>Assessment criteria</h3><ol>'+''.join('<li>'+html.escape(r)+'</li>' for r in full['rubric'])+'</ol>'+para('For each criterion, an instructor can award 0–20 points using the evidence below. A total is a course-work mark only; it does not grant an operational privilege, licence or practical competence finding.')
        capstone+='<ul><li>0–4: missing or materially misleading evidence.</li><li>5–9: partial explanation; major gaps remain.</li><li>10–14: relevant work with limitations/gaps to resolve.</li><li>15–17: clear, traceable reasoning; minor corrections needed.</li><li>18–20: complete evidence for this desk-based criterion, with assumptions and limitations handled accurately.</li></ul>'
        capstone+=para('Self-review before submission: identify the evidence for each criterion, record unresolved questions and revise any unsupported claim. Do not invent a reviewer, approval or real-world outcome. The Assignment activity accepts an online-text portfolio for human marking after an instructor makes it available.')
        pages.append(dict(key='v2-final-project',name='Final project and assessment rubric',html=capstone,section=4,assets=[]))
        course['assignment']=dict(name='Final project — submit for human review',html=capstone)
        if slug=='radiotelephony-practice':
            lab=notice+para('Practise fictional exchanges, phonetic groups and written readback elements. Optional browser speech and temporary local microphone playback are available; there is no automatic speech grading or radio transmission.')+'<p><a href="[[RADIOLAB]]">Open radiotelephony practice lab</a></p>'
            pages.append(dict(key='radio-lab-v2',name='Radiotelephony practice lab',html=lab,section=4,assets=[]))
            course['lesson']=pages[1]['html'];course['blueprint']=guide
        course['pages']=pages
    return data


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path, default=ROOT / 'dist/SkyScholar-Course-Content-Installer-v2.0.0.zip')
    args = parser.parse_args()
    data = (json.dumps(payload(), ensure_ascii=False, indent=2) + '\n').encode('utf-8')
    (PLUGIN / 'content').mkdir(exist_ok=True)
    (PLUGIN / 'content/pack.json').write_bytes(data)
    (PLUGIN / 'content/pack.sha256').write_text(hashlib.sha256(data).hexdigest() + '\n')
    for module in ('importer', 'radio'):
        source = (PLUGIN / ('amd/src/' + module + '.js')).read_text()
        (PLUGIN / ('amd/build/' + module + '.min.js')).write_text(source.replace('define([],', 'define("local_skyscholarcontent/' + module + '", [],', 1))
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with zipfile.ZipFile(args.output, 'w', zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
        for path in sorted(PLUGIN.rglob('*')):
            if path.is_file():
                archive.write(path, 'skyscholarcontent/' + path.relative_to(PLUGIN).as_posix())
    print(json.dumps({'archive': str(args.output.resolve()), 'bytes': args.output.stat().st_size,
                      'courses': 15, 'page_activities': 130, 'assignments': 15, 'draft_questions': 35, 'illustrations': 76, 'radio_scenarios': 27}, indent=2))


if __name__ == '__main__':
    main()
