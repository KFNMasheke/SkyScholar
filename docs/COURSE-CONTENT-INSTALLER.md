# SkyScholar draft course content installer

## Install once, import through Moodle

1. Download `SkyScholar-Course-Content-Installer-v1.0.0.zip`.
2. Log in as a Moodle site administrator. Back up the site before installing an extension.
3. Open **Site administration → Plugins → Install plugins**, upload the ZIP and complete Notifications.
4. Open **Site administration → Courses → SkyScholar course content**.
   Direct path on this installation: `/skyscholar/local/skyscholarcontent/admin.php`.
5. Review the 14 targets, then select **Import all draft course content** once.
   Keep the page open while its 14 sequential course requests complete.
6. Open **Manage courses and categories**. Review the hidden course starters and
   their hidden native Page activities. In the SACAA course, open **More → Question
   bank** to review the seven staff categories and 35 draft questions.

If plugin upload cannot write to disk, extract the `skyscholarcontent` folder and
upload it into `public_html/skyscholar/local/`, then open Notifications. Do not
replace Moodle core, the theme or `local/skyscholar`. No FTP credentials are
included. This is a separate, standalone plugin; the older practice-engine plugin
is not required and its global configure command is never run.

## What happens

The importer creates or reuses the exact 14 draft courses from the earlier CSV,
creates four topic sections per course, adds 14 original opening lessons with desk
exercises/marking guidance, adds 14 course-plan Pages and adds seven subject-note
Pages to the SACAA starter. Empty section names get their planned module titles;
existing staff-written names are kept. New Pages remain hidden.

35 original questions, answers, option feedback, explanations, objectives and source
references enter seven course-context question-bank categories using Moodle's
question API, explicitly as draft versions. No quiz slots, attempts, grades,
certificates, enrolments, payment settings or site registration rules are changed.
This imports ALL currently authored material, not unwritten later lessons or a
complete reviewed exam bank. Later modules are headings, not completed lessons.

New courses are grouped by their planned categories under hidden **SkyScholar —
Draft content**. Earlier CSV courses retain their existing category, summary and
settings. Only matching stable IDs AND shortnames qualify for reuse. A visible or
non-Topics course causes preflight to stop; the importer does not silently hide or
reconfigure it. The earlier CSV is not required for a fresh import.

## Retry and ownership

Each course runs in a transaction under an exclusive import lock. Closing the page
stops future requests; a request already received may finish. Retry runs safely:
existing native IDs are reused and existing Pages/questions are left untouched.
No content is changed by installation, upgrade, GET requests or opening the screen.
Only logged-in users with `moodle/site:config`, a valid session key and a POST can
start course mutations. Requests select only bundled course keys; they cannot
upload arbitrary paths, HTML, questions or approval flags.

Stable course IDs, course-module IDs and question-bank-entry IDs act as the content
registry. Duplicate/conflicting identities stop instead of guessing. Manually
imported XML questions outside the importer's `ssc-draft-*` categories remain
separate and are not deleted or merged. Staff-added activities without these IDs
are also retained. If you delete/rename import IDs, stop and reconcile before a
retry; removing identifiers defeats duplicate detection.

This version is additive. It does not overwrite a lesson you edited or update an
existing question version from a later package. Native Page editing and native
question-bank review remain the authoring workflow. Do not rename the stable ID
fields to change visible course titles; edit the titles themselves instead.

Course import events are recorded through Moodle's normal event/log API. This
plugin has no separate personal-data tables. Content and creator/audit records use
Moodle core storage, capabilities, backup and retention mechanisms.

## Validation and release limits

The build validates the original authoring catalogue and draft bank, escapes the
plain-text Markdown subset, verifies package hashes and creates a standard plugin
ZIP. PHP/JS syntax and payload validation run locally. Native integration tests
cover earlier CSV reuse, actual Page bodies, native draft question versions,
repeat imports, edit preservation, published-course refusal and unauthorized
access. They are included in CI; do not claim they passed unless their run has
completed successfully. Run `tests/course_content_integration.php` only against
the guarded disposable Moodle database.

Live Xneelo installation and browser acceptance must still be checked after upload.
The plugin is beta pending those checks. Content accuracy review remains separate
from successful software import. No reviewed course or regulator approval is claimed.

## Removal and recovery

Disable access to this admin page by removing the plugin code only after following
Moodle's normal uninstall process. Uninstalling the plugin does not delete courses,
Pages or bank questions. Manage those using native Moodle tools after backing up.
Do not delete learner courses or attempts as an upgrade procedure.

Source: GPL-3.0-or-later. Original authored draft content is distributed under the
same project licence; reference documents retain their owners' rights.
