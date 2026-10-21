# SkyScholar self-study content edition 2.0

## Install or update

1. Upload `SkyScholar-Course-Content-Installer-v2.0.0.zip` under **Site administration
   → Plugins → Install plugins** and complete Notifications. It updates the same
   `local_skyscholarcontent` component used by edition 1; no uninstall is needed.
2. Open **Site administration → Courses → SkyScholar course content**.
3. Select **Import all draft course content** and keep the tab open for the 15
   sequential requests. Repeating it is safe for content with intact import IDs.
4. Review courses in **Manage courses and categories**. The radiotelephony course's
   **Radiotelephony practice lab** Page links to the interactive lab.

If the upload limit or filesystem prevents ZIP installation, extract and upload the
whole `skyscholarcontent` folder into `public_html/skyscholar/local/`, replacing
that plugin's files, then complete Notifications. Do not replace Moodle core,
the theme, site config or `local/skyscholar`.

## What is authored

15 foundational self-study curricula: the original 14 subjects plus radiotelephony.
There are 64 taught units, 128 knowledge checks with answers, 64 worked examples,
64 desk exercises with model answers, 15 final projects and 76 original illustrations.
Four units form each original course; radio has eight units grouped into four topics.

Each unit has a taught explanation, original concept illustration, worked example,
exercise/model, checks/answers and further-reading/verification directions. Final
projects have five evidence criteria and an explicit human-marking rubric. These
are beginning-to-end introductory self-study sequences. They are not complete
approved practical training, a verified complete licensing syllabus, a full official
exam bank or a competence endorsement. Technical/regulatory instructor review is
still pending. Assigned current-source reading and external assessments remain
part of the learning plan where relevant.

The importer produces 130 native Page activities, including 35 preserved starter
resources from edition 1, plus 15 native Assignments configured for online-text
portfolio submissions and human marking. The 35 previously authored multiple-choice
questions retain native draft status. The 128 new checks are in lesson Pages with
answer guides; they are not falsely labelled a reviewed multiple-choice bank or
silently turned into scored quizzes.

New curricula and assessment activities remain hidden. Existing CSV course IDs are
reused; staff-edited descriptions, Pages, Assignments, questions, attempts and grades
are preserved. An untouched generated edition-1 description may be refreshed when
it exactly matches the known previous text. Old starter resources stay available
for staff; the edition-2 study guide and unit Pages define the expanded sequence.

New courses go under hidden **SkyScholar — Draft content**, grouped by planned
category. Existing courses retain their category. Visible/non-Topics course identity
conflicts stop preflight rather than silently rewriting or hiding the course.

## Radiotelephony lab

27 fictional scenarios cover phonetics, number/reference handling, initial contact,
readback, clarification, similar callsigns, reports and emergency-information planning.
Station/identity tokens are for training, not claims about registered aircraft or
current operating information. The lab has no radio connection or live frequency.

- Selected-element text feedback and conflicting-phrase warnings; no pass/qualification score.
- A model response and limitations for every scenario.
- Optional browser speech synthesis, explicitly not an authoritative pronunciation model.
- User-initiated microphone record/playback, with an automatic 60-second stop.
- Temporary browser-session audio objects only. No upload, transcription or recording database.
- Clear/leave actions stop microphone tracks and discard/revoke local playback objects.
- Random phonetic groups and written-token spelling.

The lab uses native Moodle login, course enrolment and the linked Page's visibility
and availability. It is not exposed merely by guessing a course ID. While the course
or lab Page is hidden it is for authorised staff. After actual review and explicit
native visibility/enrolment decisions, it can be made available like other course
activities. This installer never publishes or invents a reviewer/approval record.

## Integrity, retry and records

Each course imports in a transaction under an exclusive content lock. Only an
admin with `moodle/site:config`, a valid session key and a POST can mutate content.
GET/installation/upgrade only install code or display a preview. Requests select
bundled course IDs; there is no arbitrary path/content upload endpoint.

Stable native IDs identify courses, Pages, Assignments and bank entries. Existing
owned items are skipped, preserving edits. Do not rename import ID fields; visible
titles can be edited independently. Unmanaged manually created Pages/XML questions
remain separate, and no guessed merge or deletion is attempted. Interrupted imports
can be retried. Identity damage or a deletion in progress requires reconciliation.

Illustrations are stored in each Page's native `mod_page/content` file area and use
`@@PLUGINFILE@@` references, supporting Moodle's normal media access and backup
workflow. The bundle verifies filenames and hashes before mutation. New project
Assignments create native grade items, but no scores are fabricated or existing
learner grades/attempts reset. Normal core event logs record imports.

The plugin has no separate personal-data tables. Native course/submission/grade,
creator and audit records follow Moodle core privacy and retention mechanisms.

## Validation and limits

Source validator checks all 15 sequences, 64 lesson bodies, examples, exercises,
answer guides, 128 checks, final projects, illustrations and 27 complete scenarios.
PHP/JS syntax and payload hashes are checked. Automated integration exercises native
Page bodies, all 76 protected file areas, 15 online-text Assignments, draft bank
versions, CSV reuse, repeat imports/edit preservation, existing grades/attempts,
concurrent processes, admin/CSRF/GET restrictions and radio course/enrolment access.
Client tests check scenario models, normalization/conflict warnings and recording
clear/stop/page-leave lifecycle using simulated browser objects. Do not claim a CI
run passed until it has completed successfully.

Live Xneelo upload and device/browser microphone/voice acceptance remain to be
checked after installation. Automated software checks do not substitute for a
competent technical and instructional content review. The plugin remains beta.

Source code and original content: GPL-3.0-or-later. Third-party reference documents
are linked for study, not redistributed or relicensed. Original artwork source is
under `authoring/illustrations` with the renderer in `scripts/render_course_art.py`.
Uninstalling the plugin does not remove native courses or learner work; manage any
removal through Moodle's normal backed-up course administration process.
