# UAV Visuals · SkyScholar Moodle theme

## Visual direction

Release 1.1.1 adapts the Edly learning-catalogue reference into UAV Visuals’ identity:
a white header, pale botanical hero, bold Manrope headings, forest buttons, gold
accents, soft feature panels and spacious image-led course cards. The supplied
logo, aerial photograph and licensed local fonts remain UAV Visuals assets. No
Edly source code, commercial theme files, illustrations or promotional copy are
bundled.

The site-home hero spans the available screen width, with Boost’s outer gutters
and the 1440px landing-page cap removed. Open drawers still reserve their native
space. The hero uses fluid image heights, flexible grid columns and a single-column
layout on portrait tablets and phones.

Site home adds course search, four learning features, up to six visible course
cards, up to eight visible top-level categories, an about panel, native HTML FAQ
controls and a final invitation to explore the catalogue. Courses and categories
come from Moodle’s permission-aware category API, with hidden items excluded.
Course links lead to native enrolment/access checks. An empty catalogue explains
that courses are being prepared. No sample courses, student counts, ratings,
certification claims or simulated progress are shown. Course search is Moodle’s
GET search form and works without custom JavaScript.

Dashboard and My courses retain a compact welcome panel. The split login page
retains Moodle’s authentication form. Course, administration, quiz and secure exam
layouts retain Boost structure, question feedback colours and timer warnings.
The native main-content region and all configured frontpage activities/blocks
remain below the new landing sections. Administrators can remove duplicate native
course lists in **Site home settings → Front page items**, while retaining news or
activities they want. The show-welcome setting disables the landing page as well
as the dashboard welcome panel.

## Install the theme ZIP on the existing Moodle 4.5 site

1. Keep Boost installed. Back up code, database and private data together.
2. Go to **Site administration → Plugins → Install plugins**. Upload
   `SkyScholar-UAV-Visuals-Theme-v1.1.1.zip` and follow the plugin checks and
   installation screens. The archive contains one `uavvisuals` theme directory.
3. If Moodle cannot write to its theme directory, extract locally and upload the
   **uavvisuals folder** into `public_html/skyscholar/theme/` using konsoleH.
   Verify `theme/uavvisuals/config.php` and `version.php` both exist. Do not
   replace the site-level `skyscholar/config.php` or existing Boost files.
4. Open **Site administration → Notifications** to finish plugin installation.
5. Under **Appearance → Themes → Theme selector**, preview and select
   **UAV Visuals · SkyScholar** for the default device type.
6. First use compiles Boost plus the child SCSS. Allow that request to complete.
   Purge caches under Development if the old colours persist, then hard-refresh.
7. In the theme's Appearance settings, set the welcome title and description or
   switch the welcome panel off. Site-wide custom logos under Appearance → Logos
   take precedence over the bundled UAV Visuals logo.

Do not upload the entire Moodle deployment package to an existing working site
just to install this theme. Do not change the parent WordPress firewall repair.
This release targets Moodle 4.5; Moodle 5.x compatibility is not claimed.

## Acceptance before public launch

- Test signed-out login, password reset, identity providers, MFA and error states
  used on this site. The form remains Moodle’s own output.
- Check dashboard, My courses, homepage search results, category links, course cards, course page, course index and block drawers, search
  and the administrator edit switch at desktop and mobile widths.
- Open a practice quiz and timed mock. Check answer controls, question navigation,
  flags, timer urgency, feedback, review and finish/submit confirmation.
- Test keyboard focus, menu/dialog Escape handling, zoom, long course names,
  small screens and reduced-motion preferences. Native JS remains responsible
  for navigation and accessibility behaviours.
- Confirm fonts and the bundled image load from this Moodle subdirectory, without
  external font requests. No user statistics or invented progress data appear.

## Rollback

Select **Boost** again under Theme selector. If the theme prevents admin access,
back up and edit the site-level Moodle config to temporarily add
`$CFG->theme = 'boost';` before its final `require_once` line, then recover and
purge caches. Remove that temporary override once normal theme selection works.
Do not delete the selected theme directory to force a fallback.

## Validation and limits

Run `tests/theme_compile.php` with `SKYSCHOLAR_TEST_MOODLE` pointing at the pinned
Moodle 4.5 source. It compiles the real Boost SCSS and child styles using Moodle’s
bundled SCSS engine, renders all three Mustache templates with Moodle’s bundled
engine, checks welcome/course/category escaping, empty and populated catalogue states, search controls, FAQ markup and retention of native login-form markup.
Minimal URL/path shims let that check run without a database; it is not a full
Moodle installation or browser integration test. PHP syntax and ZIP contents
are checked separately. The full deployment builder now includes the theme,
including its required nested `config.php`.

A disposable local Moodle installation was attempted, but this environment denied
the database socket. Consequently a complete native login/course/quiz browser
run and live Xneelo activation remain unverified. The theme stays beta pending
those acceptance checks. No production account or hosting configuration was
changed while building it.

## Licensing

New code is GPL-3.0-or-later. Manrope and DM Serif Display are bundled with their
SIL Open Font License notices. UAV Visuals branding and photography are used for
this UAV Visuals deployment; they are not relicensed as third-party trademarks.
