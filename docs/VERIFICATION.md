# Verification and launch acceptance

## Automated checks

Local validation on 19 October 2026 used Moodle 4.5.15, PHP 8.3.6 and MariaDB 10.11.14. The native Moodle installation completed successfully. All 12 standalone validation checks, 26 database integration checks and 8 HTTP acceptance checks passed, together with syntax checks for all 18 custom PHP files. Release ZIP integrity and manifest checks are run after packaging. PHP 8.2 and the actual Xneelo account have not been exercised locally.

The source includes:

- `tests/validator_test.php`: valid content, duplicate IDs, unknown tracks/topics, invalid answer indices, unreviewed approvals, invalid/future review dates, empty feedback, oversized text, duplicate options and explicitly unreviewed drafts.
- PHP syntax checking for all custom PHP files.
- `tests/moodle_integration.php`: a real, isolated Moodle/MariaDB installation using native APIs. Covers repeat provisioning, 16 quiz shells, draft visibility, repeat imports, question versions, publication gates, publication idempotency, automatic course hiding, unlimited attempts, random question delivery, persisted grading/gradebook, abandoned-attempt expiry, ownership, staff capability denial, report persistence and privacy discovery/erasure.
- `tests/http_acceptance.py`: real HTTP login, learner dashboard, own-attempt review, report form/CSRF submission, cross-user review/report denial, staff queue denial and invalid-slot rejection against the disposable test site.
- `tests/package_test.py`: ZIP integrity, required Moodle/plugin files, SHA-256 manifest verification, path safety and absence of site configuration/runtime data.

The integration script refuses any database other than `skyscholar_test` on a site whose URL starts `http://127.0.0.1`. It deletes its own test courses and uses synthetic users only. Never change its guard to run on a real site.

GitHub Actions defines the same installation and acceptance flow on PHP 8.3 / MariaDB 10.11. A committed workflow is not itself evidence of a successful hosted run; inspect the repository's Actions result after pushing.

## Live-host acceptance (required after upload)

The package is not a deployment to Xneelo. These host-dependent checks remain with the installed site:

1. Run the CLI preflight and Moodle environment/security reports on the actual account.
2. Confirm public denial of `config.php`, backup files and private dataroot. Verify HTTP redirects to HTTPS and secure cookies.
3. Send actual SMTP confirmation/password-reset messages to a test mailbox. Complete a reset, verify old reset-link rejection and verify session logout.
4. Configure and test administrator MFA, recovery and least-privilege manager/editor roles.
5. Confirm free enrolment, practice feedback, timed mock feedback after submission, retakes and persistence after logout/login on desktop and mobile browsers.
6. Abandon a timed attempt, let the approved scheduler finalise it, and check cron/task logs and task recovery after an interruption.
7. Verify another learner cannot access the first learner's review, question report or staff queue by changing IDs.
8. Submit a report, retry the same report, check staff resolution and privacy export/erasure.
9. Restore a full-site backup to isolated staging and compare question versions, enrolments, reports and scores.
10. Perform an accessibility/visual pass, expected-concurrency load testing and a launch security review. Custom design is intentionally deferred.
11. Validate every aviation subject against the current syllabus with an instructor and sign off the question bank. Demo questions are not aviation content.

No claim of live SMTP verification, Xneelo account compatibility testing, browser visual QA, load capacity, penetration testing or instructor approval is made by this release.
