# Repair for the v0.1.0 deployment archive

The v0.1.0 builder excluded all files named `config.php`, including eight
required upstream application files, and 73 tracked files in the CAS vendor
library. The missing cache class prevents the installer from continuing.
This was a packaging defect, not an FTP permissions or database-password issue.

The v0.1.1 builder excludes only the site-level `application/config.php` and
retains upstream nested configuration classes, theme settings and vendor code.
The archive check requires these runtime files and still rejects site secrets.

For an existing v0.1.0 upload, use `SkyScholar-v0.1.1-Repair.zip`:

1. Back up the existing application folder.
2. Extract the repair ZIP on your computer.
3. Upload the contents of its `skyscholar` folder into the existing
   `public_html/skyscholar` directory, merging the subdirectories.
4. Open https://uavvisuals.co.za/skyscholar/ again.

The repair contains only 81 upstream files from the pinned Moodle v4.5.15
commit. It contains no site-level `config.php`, `.htaccess`, database changes or
private data. Preserve the existing site-level config and private data folder.
Do not apply it to a different Moodle release.

For a fresh installation use the rebuilt v0.1.1 full archive.
