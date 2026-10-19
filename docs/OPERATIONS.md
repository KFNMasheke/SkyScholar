# Operations and sustainability

## Free access

No billing, subscription enforcement, question unlocks or pay-per-attempt logic is included. Donations/sponsorship may cover hosting and review costs without gating practice. Free access does not mean zero operating cost.

## Security and privacy

Moodle owns authentication, password recovery, permissions, question grading, attempts and audit logs. Configure administrator MFA, least-privilege course editors and reviewers, email confirmation, CAPTCHA, account lockouts and SMTP before public sign-up. MFA is bundled in core but is not automatically enforced by this plugin. Only trusted server operators can import/publish content.

SkyScholar pages enforce login, enrolment/module visibility, ownership of completed attempts and Moodle review options. The report form uses Moodle's CSRF protection, plain-text fields, per-user throttling and a database uniqueness constraint. Staff resolution requires a system capability and a CSRF-protected POST. No public question API or cross-user results endpoint is supplied.

Question reports are exported/deleted through Moodle's privacy API. Native Moodle handles quiz/user-data requests through its own providers. Set a site privacy notice, retention policy and support contact before registration. Suggested starting retention: 12 months of inactivity before reviewing account deletion; 90 days for resolved reports, subject to the owner's approved policy. There is no automatic retention task in this release; execute approved erasure through Moodle's privacy tools. Free-text reports should not include personal or sensitive information.

## Backups and restore

Back up the database, dataroot and deployed code/config as one consistent snapshot; encrypt off-host copies and restrict access. A course `.mbz` alone is not a full-site backup. Keep at least daily recovery points and test a restore to an isolated, email-disabled staging environment. Verify accounts, enrolments, question versions, reports and graded attempts after restoring. Never place backup archives or database dumps under the public document root.

## Monitoring

Monitor HTTPS availability, cron age, Moodle scheduled-task failures, SMTP delivery, storage/database quotas, PHP errors and import/publish failures. Do not log passwords, SMTP credentials, session tokens or learner answers in application logs. SkyScholar readiness exposes a cron-age warning and content counts to managers only.

An expired timed attempt is prevented from receiving late answers by Moodle; prompt background finalisation and associated jobs still need reliable cron. Acceptance testing must include an abandoned timer and scheduler outage recovery. Xneelo's two-hour scheduler is a deployment limitation, not a configuration value to conceal.

## Upgrades

Check upstream Moodle security advisories routinely and update `moodle.lock.json` to a reviewed patched tag/commit, then rebuild and test on staging. The build refuses a dirty upstream checkout or wrong commit. Keep the SkyScholar plugin separate from core and preserve dependency licences. The initial LTS is security-maintained only, with security support ending 4 October 2027. Plan migration to a newer supported Moodle branch with compatible PHP/database/document-root support well before then.

## Deferred scope

Custom visual design, curated instructor-reviewed aviation material, an independent offline study mode, personal spaced repetition and multi-jurisdiction expansion are not implemented in this foundation. Moodle core provides its normal mobile support, but the supplied immediate-feedback practice activities are not configured as offline quizzes. No paid mobile plan is required to use the responsive website.
