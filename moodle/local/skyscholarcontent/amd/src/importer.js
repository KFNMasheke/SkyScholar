// SPDX-License-Identifier: GPL-3.0-or-later
/* Sequential POSTs keep each course within a shared-host request budget. */
define([], function() {
    'use strict';
    return {
        init: function(url, sesskey, courses, strings) {
            var button = document.getElementById('ssc-start');
            if (!button) { return; }
            var status = document.getElementById('ssc-status');
            var results = document.getElementById('ssc-results');
            var running = false;
            button.disabled = false;
            button.addEventListener('click', async function() {
                if (running) { return; }
                running = true;
                button.disabled = true;
                status.textContent = strings.running;
                results.textContent = '';
                try {
                    for (var i = 0; i < courses.length; i++) {
                        status.textContent = strings.running + ' (' + (i + 1) + '/' + courses.length + ')';
                        var body = new URLSearchParams({sesskey: sesskey, course: courses[i]});
                        var response = await fetch(url, {method: 'POST', credentials: 'same-origin',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: body.toString()});
                        var payload;
                        try { payload = await response.json(); }
                        catch (error) { throw new Error('The server returned an unexpected response. Refresh this page to sign in again, then retry.'); }
                        if (!response.ok || !payload.success) {
                            throw new Error(payload.message || 'Import failed. Refresh to check your session, then retry.');
                        }
                        var row = document.createElement('li');
                        var item = payload.result;
                        row.textContent = item.name + ': ' + item.courses + ' courses, ' + item.pages
                            + ' pages, ' + item.questions + ' draft questions, ' + (item.assignments || 0) + ' projects added.';
                        results.appendChild(row);
                    }
                    status.textContent = strings.complete;
                } catch (error) {
                    status.textContent = strings.failed + ' ' + error.message;
                    button.textContent = strings.retry;
                    button.disabled = false;
                } finally { running = false; }
            });
        }
    };
});
