# Resolved: undefined popup caused by a parent WordPress firewall

Tracking: https://github.com/KFNMasheke/SkyScholar/issues/5

## Incident and evidence

SkyScholar was installed in `/skyscholar` below an existing WordPress web root.
Navigation intermittently produced a dismissible `undefined` popup. The browser
Console showed HTTP 403 for GET requests to `lib/ajax/service-nologin.php`,
including Font Awesome mappings, templates and translated strings. Successful
POST responses elsewhere did not rule out the failing GET requests.

The parent `.htaccess` contained All In One WP Security's 5G query blacklist:

```apache
RewriteCond %{QUERY_STRING} (\"|%22).*(<|>|%3) [NC,OR]
```

Normal Moodle JSON arguments include encoded quotes (`%22`) and colons (`%3A`).
All three captured failing query strings matched this expression. A real icon
mapping request reproduced HTTP 403; an empty JSON argument-list control
returned HTTP 200 JSON. Core scripts and the public login page also returned
HTTP 200. The rejection occurred at the hosting rule before Moodle could
return its expected JSON response.

## Applied repair

1. Back up the current parent web root `.htaccess`.
2. Locate its `# 5G:[QUERY STRINGS]` block.
3. Insert the following immediately after that block's `RewriteBase /`, before
   the FIRST existing `RewriteCond`. Leave the entire existing condition chain
   and final deny rule immediately below it.

```apache
RewriteCond %{REQUEST_URI} !^/skyscholar/lib/ajax/service-nologin\.php$
```

The same insertion is supplied in
`deployment/wordpress-5g-moodle-query-exception.conf`. It is not a standalone
`.htaccess`. Adapt the directory prefix for a different installation path.
Place it in the parent rule block, not Moodle's `config.php` or child `.htaccess`.

This exempts only that exact endpoint from this particular query blacklist.
Other WordPress paths, other Moodle paths, and all other security rules remain
unchanged. Moodle's own validation and service access controls still apply.
Do not replace the parent file, disable the firewall globally, add a broad
allow rule or change Moodle core to hide the exception.

## Verification and maintenance

After applying the condition and refreshing the browser, the operator confirmed
normal navigation without the popup. A repeat of the previously blocked icon
mapping GET subsequently returned HTTP 200, `application/json`, `error: false`.
The endpoint exception was checked against unrelated WordPress/Moodle routes
and a trailing-path variant; those do not match the exception.

Turn debug messages back to NONE and leave display debugging off after diagnosis.
All In One WP Security may regenerate its managed block: recheck the exception
and Moodle navigation after firewall-setting changes. Retain the separate,
scoped installer-path repair until installation is complete.

Rollback removes only the inserted condition; the original false positive can
then recur. If a future request is rejected by another host/WAF rule, inspect
that response rather than widening this exception without evidence.

No credentials, session values, cookies or raw diagnostics are committed.
Reference: https://httpd.apache.org/docs/2.4/mod/mod_rewrite.html#rewritecond
