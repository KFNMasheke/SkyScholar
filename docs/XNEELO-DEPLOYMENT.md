# SkyScholar: Xneelo installation

This is an application package, not a database backup. It installs a new Moodle site. Do not upload it over an existing website, Moodle installation or live data without a separately planned migration and backup.

## 1. Confirm the host before upload

- Use a dedicated domain/subdomain and an empty document root. The domain choice is not hardcoded.
- PHP 8.2 or 8.3, 64-bit; select the same version for web requests and CLI.
- MariaDB 10.6.7+ (Xneelo currently documents 10.11).
- `max_input_vars >= 5000`, at least 256 MB PHP memory, and the extensions checked by `operator/scripts/preflight.php`.
- HTTPS certificate, SMTP mailbox and sufficient disk/database quota.
- Enable SSH with Xneelo support where necessary. The runbook's provisioning/import steps use CLI; a web-installer-only upload is not a completed SkyScholar setup.
- **Cron launch gate:** Moodle recommends every minute. Xneelo's published control-panel cron limit is once every two hours. Obtain an approved frequent scheduler/hosting arrangement from Xneelo. A two-hour task alone is inadequate for prompt timed-attempt finalisation, email and background work. This package does not bypass the restriction.

References checked 19 October 2026:

- https://xneelo.co.za/help-centre/products-and-services/software/
- https://xneelo.co.za/help-centre/control-panel/manage-your-cronjobs-via-the-xneelo-control-panel/
- https://xneelo.co.za/help-centre/control-panel/enable-ssh-via-the-xneelo-control-panel/
- https://moodledev.io/general/releases/4.5
- https://docs.moodle.org/en/Cron

## 2. Extract locally, upload by SFTP

Upload the **contents** of `application/` into the empty document root. Keep `operator/`, this guide, ZIP archives and checksum manifests outside the document root. Do not expose a ZIP installer or database dump to the web.

Create a private data directory, e.g. `/usr/home/FTP_USER/skyscholar-data`, outside `/usr/www/users/FTP_USER` and any other public document root. Use owner/group permissions appropriate to the hosting account, typically 0770/0750, never 0777. Moodle must be able to write there. Ask Xneelo to confirm the actual paths and permissions.

Create a new MariaDB database and least-privilege database user through Xneelo's database manager. Use `utf8mb4` / `utf8mb4_unicode_ci`. A dedicated database/user avoids cross-site access.

Place the `operator/` directory in your private home directory. Before installation:

```bash
/usr/bin/php-wrapper /private/operator/scripts/preflight.php --moodle=/actual/document/root --data=/actual/private/skyscholar-data
```

Use actual paths, not these placeholders. The preflight does not override Moodle's own environment checks.

## 3. Install Moodle

Recommended: run the official interactive CLI installer as the hosting user:

```bash
cd /actual/document/root
/usr/bin/php-wrapper admin/cli/install.php
```

Enter the HTTPS site URL, private dataroot, MariaDB credentials, site name `SkyScholar`, administrator credentials and support email. The interactive installer avoids passwords in command arguments/history. Choose MariaDB as the database type. Accept the upstream GPL licence when prompted.

Alternatively use the normal Moodle web installer over HTTPS, then use SSH for the remaining steps. Remove any installer-generated configuration download from your local downloads afterwards.

For an existing Moodle installation, first copy only the SkyScholar plugin, run `admin/cli/upgrade.php`, and review configuration changes separately. Do not run the fresh-install bootstrap over a customised live site.

Add these settings to the generated `config.php` **before** its final `require_once` line:

```php
$CFG->cookiesecure = true;
$CFG->cookiehttponly = true;
$CFG->disableupdateautodeploy = true;
$CFG->preventexecpath = true;
$CFG->debug = 0;
$CFG->debugdisplay = 0;
$CFG->cronclionly = true;
```

Keep `config.php` readable only by the hosting account/PHP service (typically 0640). Never commit it. Keep code directories read-only to the PHP service where Xneelo's account model permits. Test the included Apache rules; if a directive is disallowed and returns 500, ask support for the equivalent host-level control rather than broadly disabling protections.

## 4. Provision SkyScholar

```bash
/usr/bin/php-wrapper local/skyscholar/cli/manage.php --action=configure
/usr/bin/php-wrapper local/skyscholar/cli/manage.php --action=import --file=/actual/document/root/local/skyscholar/content/demo.json
/usr/bin/php-wrapper local/skyscholar/cli/manage.php --action=publish --track=demo
/usr/bin/php-wrapper local/skyscholar/cli/manage.php --action=readiness --track=za-remote
```

This creates the course category, seven draft South African remote-pilot subject areas, 14 hidden aviation quizzes and two demonstration quizzes. The demonstration is explicitly about using the platform, not aviation knowledge. All attempts are free/unlimited. No payment plugin is configured.

In Site administration, configure outgoing SMTP email and send Moodle's email test. Verify confirmation and password-reset delivery with a test learner. Configure CAPTCHA/registration abuse controls and your privacy notice before setting **Self registration = Email-based self-registration** under authentication settings. Registration is deliberately disabled by the bootstrap until these checks pass.

## 5. Cron, security and learner acceptance

Have the approved scheduler invoke `admin/cli/cron.php` every minute. The PHP command is normally `/usr/bin/php-wrapper`, but confirm it uses PHP 8.2/8.3. Do not enable web cron or share a cron secret in a URL.

Run the preflight again after installation. Visit **Site administration → Plugins → Local plugins → SkyScholar readiness**. A warning remains until cron has run recently.

Before enabling public enrolment:

1. Configure administrator MFA in Moodle's bundled MFA tool, require TOTP for privileged roles and test recovery with a second administrator.
2. Run Moodle's security report; verify HTTPS, config-file access denial and private data-directory protection.
3. Complete every learner acceptance check in `docs/VERIFICATION.md`, including an abandoned timed attempt and another learner's access denial.
4. Set privacy/contact details, retention and backup schedules; complete a restore test.
5. Keep aviation courses hidden until the reviewed bank and syllabus coverage meet your release criteria. `publish --track=za-remote` refuses an undersized bank, but cannot independently verify the truth of a reviewer declaration or syllabus coverage.

Your live hosting/database credentials and chosen domain are necessarily supplied during installation; none is embedded in the package. The package has not been uploaded to your Xneelo account.

## 6. Deploy updated releases safely

Back up code, database and dataroot together. Test on staging, enter maintenance mode, deploy the new clean code plus the preserved site-specific config, run `admin/cli/upgrade.php`, purge caches, complete smoke tests, then exit maintenance mode. Do not overwrite `config.php` or dataroot. Do not downgrade the database to match older code; restore the complete consistent backup for rollback.
