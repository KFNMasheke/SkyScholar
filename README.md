# SkyScholar

Free aviation practice exams and ground-school revision by UAV Visuals.

**Practice exams, repeat attempts, explanations and learner progress remain free.** No subscriptions, payment gateway or paid question unlocks are included.

## Current release

Version 0.1.0 is the Moodle system foundation, with a deployable application package and a clearly labelled platform demonstration bank. The South African remote-pilot course structure is created hidden. **No instructor-reviewed aviation question bank is claimed or supplied in this release.** Content review and live-host acceptance are required before a public aviation-preparation launch.

## Implemented

- Native Moodle accounts, password resets, role permissions, sessions and course enrolment.
- Free self-enrolment with unlimited quiz attempts.
- Subject-level untimed practice with immediate feedback and online timed mocks with deferred feedback.
- Random selection from approved native question categories and shuffled answer options.
- Learner attempt history, scores, lowest activity averages and native answer/explanation review.
- Question issue reports restricted to the learner's own completed, reviewable attempts; deduplication, submission limits and staff resolution.
- Idempotent course provisioning and transactional question imports with version history, source references and review metadata.
- Draft/approved content separation and minimum-bank-size publication checks.
- Moodle privacy API export/deletion support for reports.
- CLI hosting preflight, Xneelo deployment runbook, release builder, package checksums and tests.

## Architecture

Moodle core is pinned in `moodle.lock.json` and kept outside this source repository. The release builder combines that exact upstream commit with `moodle/local/skyscholar`. Moodle core is not modified.

The initial deployment targets Moodle 4.5.15 LTS, PHP 8.2/8.3 and MariaDB 10.6.7+. Moodle 4.5 receives security maintenance until 4 October 2027; general bug-fix support has ended. Upgrade planning is tracked in the operations guide. This baseline avoids the newer database and document-root requirements of Moodle 5.3 on Xneelo's standard shared stack.

The platform uses native Moodle question and quiz APIs. There is no separate grading service, custom authentication system or per-learner quiz creation. Learner results are never stored in this public repository.

## Installation and operation

Read [Xneelo deployment](docs/XNEELO-DEPLOYMENT.md), [content authoring](docs/CONTENT.md), [operations](docs/OPERATIONS.md) and [verification](docs/VERIFICATION.md).

After installing Moodle with the plugin copied to `local/skyscholar`:

```bash
php local/skyscholar/cli/manage.php --action=configure
php local/skyscholar/cli/manage.php --action=import --file=/absolute/path/local/skyscholar/content/demo.json
php local/skyscholar/cli/manage.php --action=publish --track=demo
php local/skyscholar/cli/manage.php --action=readiness --track=za-remote
```

`configure` is an explicit fresh-install bootstrap. It disables public registration pending SMTP/anti-abuse/privacy checks. Do not rerun it casually on a live customised site. Imports and publication are repeatable and do not reset attempts.

## Build the Xneelo ZIP

```bash
git clone --depth 1 --branch v4.5.15 https://github.com/moodle/moodle.git /tmp/skyscholar-core
python3 scripts/build_package.py --core=/tmp/skyscholar-core
```

The ZIP contains the complete Moodle runtime (including bundled third-party libraries), SkyScholar plugin, operator tools, installation guide and a SHA-256 file manifest. It contains no credentials, database dump, learner data or populated configuration. Python, Node, Composer and Docker are not required on the production host.

## Hosting caveat

Moodle recommends one-minute cron. Xneelo's standard control-panel scheduler documents a two-hour minimum. Obtain an explicitly supported frequent scheduler or suitable hosting arrangement before launch; do not work around provider limits with a long-running loop. See the deployment runbook.

## Content and scope

Course authoring has started separately from Moodle setup. See [authoring workspace](authoring/README.md) for 14 core course blueprints and opening lessons, seven subject lessons and 35 original draft questions. These are incomplete, unreviewed drafts. They do not constitute an approved bank or a recovered complete 36-course catalogue. Validate locally with `python scripts/review_content.py`.

Questions must be original or explicitly licensed for redistribution. Do not scrape commercial question banks, reconstruct recalled live examination questions or claim regulator endorsement. Each aviation question needs a syllabus objective, reference and instructor review before publication. Study targets/durations in the initial configuration are not official examination specifications.

Future work: instructor-reviewed aviation banks, a custom theme, saved cross-attempt revision sets, a dedicated offline study experience and additional jurisdictions. The bundled default practice mode uses immediate feedback and is online; Moodle offline quizzes require a separately configured untimed deferred-feedback activity.

## Licence

SkyScholar code and the bundled demonstration content are GPL-3.0-or-later. Moodle and its bundled dependencies retain their own copyright and licence notices. A public repository and free access do not imply ownership of third-party course material. See [LICENSE](LICENSE) and [CONTRIBUTING.md](CONTRIBUTING.md).
