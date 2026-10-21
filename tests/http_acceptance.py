#!/usr/bin/env python3
"""HTTP acceptance checks against the disposable integration site; no real accounts/data."""
import html
import http.cookiejar
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = 'http://127.0.0.1:8080'

class Client:
    def __init__(self, username, password="DisposableTest!2026"):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        _, page = self.request('/login/index.php')
        token = re.search(r'name="logintoken" value="([^"]+)"', page)
        assert token, 'Login CSRF token missing'
        _, page = self.request('/login/index.php', {
            'username': username, 'password': password, 'logintoken': token.group(1),
        })
        assert 'loginerrors' not in page, 'Test login failed'

    def request(self, path, data=None):
        body = urllib.parse.urlencode(data).encode() if data is not None else None
        try:
            with self.opener.open(BASE + path, body, timeout=90) as response:
                return response.status, response.read().decode()
        except urllib.error.HTTPError as error:
            return error.code, error.read().decode()

def passed(name):
    print('PASS ' + name, flush=True)

first = Client('testlearner1')
status, dashboard = first.request('/local/skyscholar/index.php')
assert status == 200 and 'My practice progress' in dashboard and 'Using practice exams' in dashboard
passed('learner HTTP login and progress dashboard')
link = re.search(r'/local/skyscholar/review.php\?attempt=(\d+)', html.unescape(dashboard))
assert link, 'Completed attempt missing from dashboard'
attempt = link.group(1)
status, review = first.request('/local/skyscholar/review.php?attempt=' + attempt)
assert status == 200 and 'Report a question issue' in review
passed('own completed attempt review')
status, form = first.request('/local/skyscholar/report.php?attempt=' + attempt + '&slot=1')
assert status == 200 and 'What needs correcting?' in form
token = re.search(r'name="sesskey"[^>]*value="([^"]+)"', form)
assert token, 'Report CSRF token missing'
status, rejected = first.request('/local/skyscholar/report.php?attempt=' + attempt + '&slot=1', {
    'attempt': attempt, 'slot': '1', 'message': 'Missing CSRF should never persist', 'submitbutton': 'Submit report',
})
assert 'Your question report has been recorded.' not in rejected
passed('question-report POST rejects missing CSRF')
status, submitted = first.request('/local/skyscholar/report.php?attempt=' + attempt + '&slot=1', {
    'attempt': attempt, 'slot': '1', 'sesskey': token.group(1), 'message': 'HTTP functional test issue',
    '_qf__local_skyscholar_report_form': '1', 'submitbutton': 'Submit report',
})
assert status == 200 and 'Your question report has been recorded.' in submitted
passed('CSRF-protected question-report submission')

second = Client('testlearner2')
status, denied = second.request('/local/skyscholar/review.php?attempt=' + attempt)
assert 'Report a question issue' not in denied and ('permission' in denied.lower() or status == 403)
passed('another learner cannot review another person’s attempt')
status, denied = second.request('/local/skyscholar/report.php?attempt=' + attempt + '&slot=1')
assert 'What needs correcting?' not in denied and ('permission' in denied.lower() or status == 403)
passed('another learner cannot report against another person’s attempt')
status, denied = second.request('/local/skyscholar/admin.php')
assert 'Open question reports' not in denied
passed('learner cannot access staff report queue')
status, denied = first.request('/local/skyscholar/report.php?attempt=' + attempt + '&slot=9999')
assert 'What needs correcting?' not in denied
passed('invalid question slot is rejected')
status, denied = first.request('/local/skyscholarcontent/admin.php')
assert 'id="ssc-start"' not in denied
status, denied = first.request('/local/skyscholarcontent/run.php', {'course': 'draft-introduction-to-drone-technology'})
assert '"success":true' not in denied
passed('learner cannot open or POST to the course content importer')
admin = Client('admin', 'DisposableTestAdmin!2026')
status, screen = admin.request('/local/skyscholarcontent/admin.php')
assert status == 200 and 'id="ssc-start"' in screen
sesskey = re.search(r'"sesskey"\s*:\s*"([^" ]+)"', screen)
assert sesskey, 'Admin session token missing'
status, rejected = admin.request('/local/skyscholarcontent/run.php', {'course': 'draft-introduction-to-drone-technology'})
assert '"success":true' not in rejected
status, rejected = admin.request('/local/skyscholarcontent/run.php?sesskey=' + sesskey.group(1))
assert status == 405
status, repeated = admin.request('/local/skyscholarcontent/run.php', {'course': 'draft-introduction-to-drone-technology', 'sesskey': sesskey.group(1)})
import json
result = json.loads(repeated)
assert status == 200 and result['success'] is True
assert result['result']['courses'] + result['result']['pages'] + result['result']['questions'] == 0
status, rejected = admin.request('/local/skyscholarcontent/run.php', {'course': 'draft-unknown', 'sesskey': sesskey.group(1)})
assert status == 400 and json.loads(rejected)['success'] is False
passed('admin importer screen, missing-CSRF denial, GET denial, safe POST retry and unknown-key refusal')
print('ALL HTTP ACCEPTANCE CHECKS PASSED', flush=True)
