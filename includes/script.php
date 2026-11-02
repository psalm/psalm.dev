<?php require_once dirname(__DIR__) . '/src/Settings.php' ?>
<?php require_once dirname(__DIR__) . '/src/OnlineChecker.php' ?>
<script>
// homepage only (body.pg_compact): the playground sits next to the intro until expanded; elsewhere a no-op
var setCodeExpanded = function(expanded) {
    var body = document.body;

    if (!body.classList.contains('pg_expandable')) {
        return false;
    }

    var hadFocus = document.activeElement && /^(expander|shrinker)$/.test(document.activeElement.id);

    body.classList.toggle('pg_compact', !expanded);
    editor.refresh();

    if (hadFocus) {
        document.getElementById(expanded ? 'shrinker' : 'expander').focus();
    }

    var playground = document.getElementById('playground');

    if (expanded && playground.getBoundingClientRect().top < 0) {
        playground.scrollIntoView({block: 'start'});
    }

    return false;
};

var expandCode = function() {
    return setCodeExpanded(true);
};

var shrinkCode = function() {
    return setCodeExpanded(false);
};

var toggleSettings = function() {
    var panel = document.getElementById('settings_panel');
    var open = panel.hasAttribute('hidden');

    if (open) {
        panel.removeAttribute('hidden');
    } else {
        panel.setAttribute('hidden', '');
    }

    document.getElementById('settings').setAttribute('aria-expanded', open ? 'true' : 'false');
    // the drawer takes width from the panes, so CodeMirror has to re-measure
    editor.refresh();
    return false;
};

var settingsText = <?php echo json_encode(\PsalmDotOrg\Settings::descriptions()); ?>;
var settingsDocs = <?= json_encode(\PsalmDotOrg\Settings::docsLinks(), JSON_UNESCAPED_SLASHES) ?>;
var defaultSettings = <?php echo json_encode(\PsalmDotOrg\Settings::defaultValues()); ?>;

// drawer sections; a setting missing here still shows up, under "Other"
var settingsGroups = [
    ['Unused code', ['unused_variables', 'unused_methods', 'find_unused_psalm_suppress']],
    ['Inference', ['memoize_properties', 'memoize_method_calls', 'disable_var_parsing', 'use_phpdoc_without_magic_call']],
    ['Checks', ['check_throws', 'restrict_return_types', 'ensure_override_attribute', 'strict_internal_functions', 'ensure_array_offsets_exist']],
];

var defaultPhpVersion = <?= json_encode(\PsalmDotOrg\OnlineChecker::defaultPhpVersion()) ?>;
var phpVersions = <?= json_encode(\PsalmDotOrg\OnlineChecker::PHP_VERSIONS) ?>;
// a saved snippet opens with the version it was saved with; ?php= overrides it
var basePhpVersion = (typeof snippetPhpVersion !== 'undefined' && snippetPhpVersion) || defaultPhpVersion;
var phpVersion = new URLSearchParams(window.location.search).get('php');

// same versions check.php accepts; anything else falls back to the base version
if (!phpVersion || !new RegExp(<?= json_encode(\PsalmDotOrg\OnlineChecker::SUPPORTED_PHP_VERSION_REGEX) ?>).test(phpVersion)) {
    phpVersion = basePhpVersion;
}

var setPhpVersion = function(version) {
    phpVersion = version;

    // keep the choice in the URL, so reloading preserves it
    const params = new URLSearchParams(window.location.search);

    if (version === basePhpVersion) {
        params.delete('php');
    } else {
        params.set('php', version);
    }

    const query = params.toString();
    window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash);
    loadedPage = window.location.pathname + window.location.search;

    renderPhpVersionLabels();
    editor.performLint();
};

var renderPhpVersionLabels = function() {
    var text = 'PHP ' + phpVersion;
    var title = 'Analyse code as PHP ' + phpVersion + (phpVersion === defaultPhpVersion ? ', the default' : '');

    document.getElementById('php_version_label').textContent = text;
    document.getElementById('php_version').title = title;

    var drawerVersion = document.getElementById('pg_drawer_php');

    if (drawerVersion) {
        drawerVersion.textContent = text;
        drawerVersion.title = title;
    }
};

var isSettingChanged = function(key) {
    return !!settings[key] !== !!defaultSettings[key];
};

/**
 * Updates the Settings button label ("Settings · N changed") and the per-setting markers,
 * without rebuilding the drawer, so keyboard focus stays where it is.
 */
var updateSettingsSummary = function() {
    var changed = Object.keys(settingsText).filter(isSettingChanged);

    document.getElementById('settings').textContent = changed.length
        ? 'Settings \u00B7 ' + changed.length + ' changed'
        : 'Settings';

    Object.keys(settingsText).forEach(function (key) {
        var input = document.getElementById(key);

        if (input) {
            input.checked = !!settings[key];
            input.closest('.pg_check').classList.toggle('changed', isSettingChanged(key));
        }
    });
};

var toggleSetting = function(key) {
    if (key in settings) {
        settings[key] = !settings[key];
    } else {
        settings[key] = true;
    }

    updateSettingsSummary();
    editor.performLint();

    return false;
};

var resetSettings = function() {
    Object.keys(defaultSettings).forEach(function (key) {
        settings[key] = defaultSettings[key];
    });

    updateSettingsSummary();
    editor.performLint();
};

var buildSettingsPanel = function() {
    var panel = document.getElementById('settings_panel');
    var grouped = [];
    var groups = settingsGroups.map(function (group) {
        grouped = grouped.concat(group[1]);
        return [group[0], group[1].filter(function (key) { return key in settingsText; })];
    });
    var others = Object.keys(settingsText).filter(function (key) { return grouped.indexOf(key) === -1; });

    if (others.length) {
        groups.push(['Other', others]);
    }

    panel.textContent = '';

    var head = document.createElement('div');
    head.className = 'pg_drawer_head';
    head.appendChild(document.createTextNode('Settings'));

    var headVersion = document.createElement('span');
    headVersion.className = 'pg_drawer_php';
    headVersion.id = 'pg_drawer_php';
    head.appendChild(headVersion);

    var reset = document.createElement('button');
    reset.type = 'button';
    reset.textContent = 'Reset';
    reset.title = 'Restore the default settings';
    reset.addEventListener('click', resetSettings);
    head.appendChild(reset);
    panel.appendChild(head);

    groups.forEach(function (group) {
        var fieldset = document.createElement('fieldset');
        fieldset.className = 'pg_group';

        var legend = document.createElement('legend');
        legend.textContent = group[0];
        fieldset.appendChild(legend);

        group[1].forEach(function (key) {
            var label = document.createElement('label');
            label.className = 'pg_check';

            var input = document.createElement('input');
            input.type = 'checkbox';
            input.id = key;
            input.checked = !!settings[key];
            input.addEventListener('change', function () {
                toggleSetting(key);
            });

            var text = document.createElement('span');
            // descriptions are fixed strings from Settings::AVAILABLE_SETTINGS and may contain <code>
            text.innerHTML = settingsText[key];

            label.appendChild(input);
            label.appendChild(text);

            // the docs link sits next to the label, so clicking it never toggles the checkbox
            var row = document.createElement('div');
            row.className = 'pg_check_row';
            row.appendChild(label);

            if (settingsDocs[key]) {
                var docs = document.createElement('a');
                docs.className = 'pg_doc_link';
                docs.href = settingsDocs[key];
                docs.target = '_blank';
                docs.rel = 'noopener';
                docs.textContent = 'docs';
                docs.setAttribute('aria-label', 'Configuration docs for: ' + text.textContent);
                row.appendChild(docs);
            }

            fieldset.appendChild(row);
        });

        panel.appendChild(fieldset);
    });
};

var renderPhpVersions = function() {
    var select = document.getElementById('php_version');
    var versions = phpVersions.indexOf(phpVersion) === -1 ? phpVersions.concat([phpVersion]) : phpVersions;

    select.textContent = '';

    versions.forEach(function (version) {
        var option = document.createElement('option');
        option.value = version;
        option.textContent = version + (version === defaultPhpVersion ? ' (default)' : '');
        option.selected = version === phpVersion;
        select.appendChild(option);
    });
};

var redrawSettings = function() {
    renderPhpVersions();
    buildSettingsPanel();
    updateSettingsSummary();
    renderPhpVersionLabels();
};

var messageTimer = null;

/**
 * Status bar message area: a short text, optionally followed by a link shown as selectable text.
 * kind is "ok" or "error"; "ok" messages with a timeout disappear on their own.
 */
var showStatusMessage = function(kind, text, link, timeout) {
    var element = document.getElementById('pg_message');

    clearTimeout(messageTimer);
    element.textContent = '';
    element.dataset.kind = kind;
    element.appendChild(document.createTextNode(text));

    if (link) {
        var linkText = document.createElement('span');
        linkText.className = 'pg_message_link';
        linkText.textContent = link;
        element.appendChild(document.createTextNode(' '));
        element.appendChild(linkText);
    }

    if (timeout) {
        messageTimer = setTimeout(function () {
            element.textContent = '';
        }, timeout);
    }
};

var sharing = false;
var shareLabelTimer = null;

var setShareLabel = function(text, state) {
    var button = document.getElementById('getlink');
    var label = button.querySelector('.pg_share_label');

    clearTimeout(shareLabelTimer);
    label.textContent = text;
    button.dataset.state = state;

    if (state !== 'busy') {
        shareLabelTimer = setTimeout(function () {
            label.textContent = 'Share';
            delete button.dataset.state;
        }, 2000);
    }
};

var getLink = async function() {
    if (sharing) {
        return false;
    }

    sharing = true;
    showStatusMessage('ok', 'saving\u2026');
    setShareLabel('Saving\u2026', 'busy');

    try {
        var result = await shareSnippet();
        setShareLabel(
            {copied: 'Copied \u2713', copy_failed: 'Copy failed'}[result] || 'Share failed',
            result === 'copied' ? 'ok' : 'error'
        );
    } catch (error) {
        setShareLabel('Share failed', 'error');
        throw error;
    } finally {
        sharing = false;
    }

    return false;
};

var shareSnippet = async function() {
    let response;
    let url;

    try {
        response = await fetch('/add_code', {
            method: 'POST',
            headers: {
                'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
                'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
            },
            body: serializeJSON({code: editor.getValue(), settings: JSON.stringify(settings), php: phpVersion})
        });
        url = await response.text();
    } catch (error) {
        showStatusMessage('error', 'Could not create a link: the request failed. Please try again later');
        return 'failed';
    }

    if (!response.ok || url.indexOf('/r/') === -1) {
        // add_code.php answers with short plain-text messages; anything else (HTML, a PHP error, nothing) is not shown
        const isPlainMessage = url.length > 0 && url.length < 300 && url.indexOf('<') === -1;
        showStatusMessage('error', isPlainMessage
            ? url.trim()
            : 'Could not create a link: the server returned HTTP ' + response.status + '. Please try again later');
        return 'failed';
    }

    // the PHP version is stored with the snippet, so the link needs no query params; highlighted lines stay linked
    const fullUrl = window.location.protocol + '//' + url.trim() + lineLink.fragment();
    let copied = true;

    try {
        await navigator.clipboard.writeText(fullUrl);
    } catch (error) {
        copied = false;
    }

    // stay on the page, but make the address bar point at the snippet so reloading opens it
    try {
        const target = new URL(fullUrl);

        if (target.origin === window.location.origin && target.pathname !== window.location.pathname) {
            window.history.pushState({snippet: target.pathname}, '', target.pathname + target.hash);
            loadedPage = window.location.pathname + window.location.search;
        }

        // the snippet now carries the chosen version, so the picker no longer needs ?php=
        basePhpVersion = phpVersion;
    } catch (error) {
        // an unexpected URL shape: the link is still shown below
    }

    if (copied) {
        showStatusMessage('ok', 'Link copied', fullUrl, 8000);
        return 'copied';
    }

    showStatusMessage('error', 'Copy failed, link:', fullUrl);
    return 'copy_failed';
};

// the path and query the editor currently shows; entries that only differ in the #L fragment share it
var loadedPage = window.location.pathname + window.location.search;

// pushState only changes the address bar; going back or forward has to load that page for real,
// unless only the fragment changed (hashchange re-applies the line highlight)
window.addEventListener('popstate', function () {
    if (window.location.pathname + window.location.search !== loadedPage) {
        window.location.reload();
    }
});

var serializeJSON = function(data) {
    return Object.keys(data).map(function (keyName) {
        return encodeURIComponent(keyName) + '=' + encodeURIComponent(data[keyName])
    }).join('&');
}

var latestFetch = 0;
var fetchKey = 0;

// issues from the latest successful run, in Psalm's order; read by the issue navigation
var lastIssues = [];
// index into lastIssues of the issue last jumped to, or null
var currentIssue = null;

var escapeHtml = function(snippet) {
    return String(snippet).replace(/[ -香<>\&"']/gim, function(i) {
        return '&#' + i.charCodeAt(0) + ';';
    });
};

var outputElement = function() {
    return document.getElementById('psalm_output');
};

/**
 * Status bar run state: idle | busy | done | parse_error | error.
 */
var runningTimer = null;

var setRunState = function(state, label) {
    var element = document.getElementById('pg_run_state');
    element.dataset.state = state;
    element.textContent = label;

    // dim the output only for runs that take a while, so fast ones don't flicker
    if (state === 'busy') {
        runningTimer = runningTimer || setTimeout(function () { setRunning(true); }, 150);
    } else {
        clearTimeout(runningTimer);
        runningTimer = null;
        setRunning(false);
    }
};

var setRunning = function(running) {
    document.getElementById('pg_running').hidden = !running;
    document.querySelector('.playground .pg_output').classList.toggle('pg_is_running', running);
};

var setIssueCount = function(count) {
    document.getElementById('pg_issue_count').textContent = count === null ? '' : String(count);
};

var renderPsalmVersion = function(response) {
    var element = document.getElementById('pg_psalm_version');
    element.textContent = 'Psalm ';

    var versionLink = document.createElement('a');
    versionLink.href = response.version_url;
    versionLink.target = '_blank';
    versionLink.textContent = response.version_label;
    element.appendChild(versionLink);

    if (response.version_commit) {
        var commitLink = document.createElement('a');
        commitLink.href = response.version_commit_url;
        commitLink.target = '_blank';
        commitLink.textContent = response.version_commit;
        element.appendChild(document.createTextNode(' ('));
        element.appendChild(commitLink);
        element.appendChild(document.createTextNode(')'));
    }
};

var renderPlainOutput = function(text) {
    var element = document.createElement('div');
    element.className = 'pg_plain';
    element.textContent = text;
    outputElement().textContent = '';
    outputElement().appendChild(element);
};

/**
 * One step of a taint trace or one related location: label, line:col and the snippet with the span marked.
 * Steps with a snippet are buttons that select their range in the editor.
 */
var renderReference = function(reference) {
    if (!('snippet' in reference) || typeof reference.from !== 'number') {
        return '<li class="pg_step"><span class="pg_step_label">' + escapeHtml(reference.label) + '</span></li>';
    }

    var snippet = reference.snippet;
    var selection_start = reference.from - reference.snippet_from;
    var selection_end = reference.to - reference.snippet_from;

    return '<li class="pg_step">'
        + '<button type="button" class="pg_step_jump" data-from="' + Number(reference.from) + '" data-to="' + Number(reference.to) + '"'
        + ' title="Select this step in the editor">'
        + '<span class="pg_step_head">'
        + '<span class="pg_step_label">' + escapeHtml(reference.label) + '</span>'
        + '<span class="pg_step_loc">' + escapeHtml(reference.line_from) + ':' + escapeHtml(reference.column_from) + '</span>'
        + '</span>'
        + '<span class="pg_ref_snippet">'
        + escapeHtml(snippet.substring(0, selection_start))
        + '<mark>' + escapeHtml(snippet.substring(selection_start, selection_end)) + '</mark>'
        + escapeHtml(snippet.substring(selection_end))
        + '</span></button></li>';
};

var renderReferences = function(title, references, className) {
    return '<div class="pg_refs ' + className + '">'
        + '<div class="pg_refs_title">' + title + '</div>'
        + '<ol class="pg_steps">' + references.map(renderReference).join('') + '</ol>'
        + '</div>';
};

var errorLevelsDocs = '/docs/running_psalm/error_levels/';

/**
 * Psalm's ERROR_LEVEL for an issue type, as sent by /check: N (1-7) is an error at errorLevel N and stricter,
 * info above it; -1 and 8 are errors at every level; -2 only shows up when its feature is enabled.
 * Returns null for anything else (older snippets, unknown values).
 */
var describeErrorLevel = function(level) {
    if (level === -1 || level === 8) {
        return {label: 'any level', anchor: 'always-treated-as-errors', title: 'An error at every errorLevel (1 to 8)'};
    }

    if (level === -2) {
        return {
            label: 'feature',
            anchor: 'feature-specific-errors',
            title: 'Only reported when its feature (unused code, taint analysis, ...) is enabled; then an error at every errorLevel'
        };
    }

    if (typeof level !== 'number' || level < 1 || level > 7) {
        return null;
    }

    return {
        label: 'level ' + level,
        anchor: level === 1 ? 'errors-that-only-appear-at-level-1' : 'errors-at-level-' + level + '-and-below',
        title: level === 1
            ? 'An error only at errorLevel 1; info at 2 to 8'
            : 'An error at errorLevel ' + level + ' and stricter (1 to ' + level + '); info at ' + (level + 1) + (level + 1 < 8 ? ' to 8' : '')
    };
};

var renderIssue = function(issue, index) {
    var level = describeErrorLevel(issue.error_level);
    var isError = issue.severity === 'error';
    var html = '<div class="pg_issue ' + (isError ? 'pg_issue_error' : 'pg_issue_info') + '" data-issue="' + index + '">'
        + '<div class="pg_issue_head">'
        + '<span class="pg_sev">' + (isError ? 'ERROR' : 'INFO') + '</span>'
        + '<a href="' + escapeHtml(issue.link) + '" target="_blank" rel="noopener" title="Open the ' + escapeHtml(issue.type) + ' docs">' + escapeHtml(issue.type) + '</a>'
        + (level ? '<a class="pg_level" href="' + errorLevelsDocs + '#' + level.anchor + '" target="_blank" rel="noopener" title="'
            + escapeHtml(level.title) + '">' + escapeHtml(level.label) + '</a>' : '')
        + '<button type="button" class="pg_loc" data-issue="' + index + '" title="Jump to this issue">'
        + escapeHtml(issue.line_from) + ':' + escapeHtml(issue.column_from) + '</button>'
        + '</div>'
        + '<div class="pg_issue_msg">' + escapeHtml(issue.message) + '</div>';

    if (issue.other_references && issue.other_references.length) {
        html += renderReferences('Related', issue.other_references, 'pg_other_refs');
    }

    if (issue.taint_trace && issue.taint_trace.length) {
        html += renderReferences('Taint trace', issue.taint_trace, 'pg_taint_trace');
    }

    return html + '</div>';
};

var typeAnnotations = function(response, cm) {
    return response.type_map.map(function (type_data) {
        return {
            severity: 'type',
            message: type_data.type,
            from: cm.posFromIndex(type_data.from),
            to: cm.posFromIndex(type_data.to)
        };
    });
};

var maxTypeRows = 500;
// typed ranges from the latest successful run, as rendered in the Types tab
var lastTypes = [];

/**
 * Psalm reports a type for nearly every expression, often the same range more than once and
 * wrappers (parentheses, casts to the same type) around an identical inner type. Identical ranges
 * are merged, and a range nested inside a wider one with the same type is dropped.
 */
var collectTypes = function(typeMap) {
    var byRange = {};
    var ranges = [];

    typeMap.forEach(function (entry) {
        if (typeof entry.from !== 'number' || typeof entry.to !== 'number' || entry.to <= entry.from) {
            return;
        }

        var key = entry.from + ':' + entry.to;

        if (!(key in byRange)) {
            byRange[key] = {from: entry.from, to: entry.to, types: []};
            ranges.push(byRange[key]);
        }

        if (byRange[key].types.indexOf(entry.type) === -1) {
            byRange[key].types.push(entry.type);
        }
    });

    // source order; wider ranges first, so a nested range comes after the one containing it
    ranges.sort(function (a, b) {
        return a.from - b.from || b.to - a.to;
    });

    var open = [];

    return ranges.filter(function (range) {
        range.type = range.types.join(' | ');

        // ranges still open at this position, i.e. ones that may contain it
        open = open.filter(function (outer) { return outer.to > range.from; });

        var redundant = open.some(function (outer) {
            return outer.to >= range.to && outer.type === range.type;
        });

        open.push(range);
        return !redundant;
    });
};

var codeSnippet = function(code, from, to) {
    var text = code.substring(from, to).replace(/\s+/g, ' ').trim();

    return text.length > 60 ? text.substring(0, 59) + '\u2026' : text;
};

var renderTypes = function(typeMap, code, cm) {
    var panel = document.getElementById('pg_types');
    var types = collectTypes(typeMap || []);
    var shown = types.slice(0, maxTypeRows);

    lastTypes = shown.map(function (range) {
        return {from: cm.posFromIndex(range.from), to: cm.posFromIndex(range.to)};
    });

    document.getElementById('pg_type_count').textContent = String(types.length);

    if (!types.length) {
        panel.innerHTML = '<div class="pg_empty">No types to show</div>';
        return;
    }

    var html = '<div class="pg_type_list">' + shown.map(function (range, index) {
        var pos = lastTypes[index].from;

        return '<div class="pg_type_row">'
            + '<span class="pg_type_loc"><button type="button" class="pg_loc" data-type="' + index + '" title="Select this expression">'
            + (pos.line + 1) + ':' + (pos.ch + 1) + '</button></span>'
            + '<code class="pg_type_code">' + escapeHtml(codeSnippet(code, range.from, range.to)) + '</code>'
            + '<span class="pg_type_type">' + escapeHtml(range.type) + '</span>'
            + '</div>';
    }).join('') + '</div>';

    if (types.length > shown.length) {
        html += '<div class="pg_note">Showing the first ' + shown.length + ' of ' + types.length + ' typed expressions</div>';
    }

    panel.innerHTML = html;
};

var clearTypes = function(message) {
    lastTypes = [];
    document.getElementById('pg_type_count').textContent = '';
    document.getElementById('pg_types').innerHTML = '<div class="pg_empty">' + escapeHtml(message) + '</div>';
};

/**
 * Selects a range in the editor (CodeMirror positions) and scrolls it into view.
 */
var selectRange = function(from, to) {
    editor.focus();
    editor.setSelection(from, to);
    editor.scrollIntoView({from: from, to: to}, 60);
};

var outputTabStorageKey = 'psalm.playground.tab';

var selectOutputTab = function(name, persist) {
    document.querySelectorAll('#pg_tabs [role="tab"]').forEach(function (tab) {
        var active = tab.dataset.tab === name;

        tab.classList.toggle('active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
        tab.tabIndex = active ? 0 : -1;
        document.getElementById(tab.getAttribute('aria-controls')).hidden = !active;
    });

    if (persist) {
        try {
            localStorage.setItem(outputTabStorageKey, name);
        } catch (e) {
            // storage can be unavailable; the tab just is not remembered
        }
    }
};

/**
 * Moves the editor cursor to an issue from the latest run and highlights it in the output.
 */
var jumpToIssue = function(index) {
    var issue = lastIssues[index];

    if (!issue) {
        return;
    }

    currentIssue = index;
    selectOutputTab('issues', false);
    selectRange(issue.from, issue.to);

    var output = outputElement();
    var current = output.querySelector('.pg_issue.current');

    if (current) {
        current.classList.remove('current');
    }

    var element = output.querySelector('.pg_issue[data-issue="' + index + '"]');

    if (element) {
        element.classList.add('current');

        // scroll only the output pane; element.scrollIntoView() would also scroll the page
        if (element.offsetTop < output.scrollTop) {
            output.scrollTop = element.offsetTop;
        } else if (element.offsetTop + element.offsetHeight > output.scrollTop + output.clientHeight) {
            output.scrollTop = element.offsetTop + element.offsetHeight - output.clientHeight;
        }
    }
};

/**
 * F8 / Shift+F8: the next (direction 1) or previous (-1) issue by position in the code, wrapping around.
 */
var jumpToAdjacentIssue = function(direction) {
    if (!lastIssues.length) {
        return;
    }

    var order = lastIssues
        .map(function (issue, index) { return index; })
        .sort(function (a, b) {
            return CodeMirror.cmpPos(lastIssues[a].from, lastIssues[b].from) || a - b;
        });

    var cursor = editor.getCursor('from');
    var position = order.indexOf(currentIssue);
    var target;

    if (currentIssue !== null && CodeMirror.cmpPos(cursor, lastIssues[currentIssue].from) === 0) {
        // still on the issue we jumped to: step through the list, so issues sharing a position are all visited
        target = order[(position + direction + order.length) % order.length];
    } else if (direction > 0) {
        target = order.find(function (index) { return CodeMirror.cmpPos(lastIssues[index].from, cursor) > 0; });
        target = target === undefined ? order[0] : target;
    } else {
        target = order.slice().reverse().find(function (index) { return CodeMirror.cmpPos(lastIssues[index].from, cursor) < 0; });
        target = target === undefined ? order[order.length - 1] : target;
    }

    jumpToIssue(target);
};

var setFixableCount = function(response, cm) {
    var fixButton = document.getElementById('fixer');
    var error_count = 0;

    if ('fixable_errors' in response && response.fixable_errors) {
        for (var type in response.fixable_errors) {
            error_count += response.fixable_errors[type];
        }
    }

    fixButton.hidden = error_count === 0;

    if (error_count) {
        outputElement().insertAdjacentHTML(
            'beforeend',
            '<div class="pg_fixable">Psalm detected ' + error_count
                + ' <a href="/docs/manipulating_code/fixing/" target="_blank">fixable issue(s)</a></div>'
        );
    }
};

var fetchAnnotations = function (code, callback, options, cm) {
    // A prerendered page must not run Psalm until the user actually opens it
    if (document.prerendering) {
        document.addEventListener('prerenderingchange', function () {
            fetchAnnotations(code, callback, options, cm);
        }, {once: true});
        return;
    }

    latestFetch++;
    fetchKey = latestFetch;

    setRunState('busy', 'analysing…');

    var submitData = {
        code: code,
        settings: JSON.stringify(settings),
        php: phpVersion
    };

    fetch('/check', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON(submitData)
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (response) {
        if (latestFetch != fetchKey) {
            return;
        }

        if ('results' in response) {
            renderPsalmVersion(response);

            lastIssues = response.results.map(function (issue) {
                return {
                    severity: issue.severity,
                    from: cm.posFromIndex(issue.from),
                    to: cm.posFromIndex(issue.to)
                };
            });

            currentIssue = null;
            setIssueCount(response.results.length);
            setRunState('done', '');

            if (response.results.length === 0) {
                outputElement().innerHTML = /^\s*(<\?php)?\s*$/i.test(code)
                    ? '<div class="pg_empty">Write some PHP to analyse</div>'
                    : '<div class="pg_empty">No issues!</div>';
            } else {
                outputElement().innerHTML = response.results.map(renderIssue).join('');
            }

            setFixableCount(response, cm);
            renderTypes(response.type_map, code, cm);

            callback(
                response.results.map(function (issue) {
                    return {
                        severity: issue.severity === 'error' ? 'error' : 'warning',
                        message: issue.message,
                        from: cm.posFromIndex(issue.from),
                        to: cm.posFromIndex(issue.to)
                    };
                }).concat(typeAnnotations(response, cm))
            );
        }
        else if ('error' in response) {
            lastIssues = [];
            currentIssue = null;
            setIssueCount(null);
            clearTypes('No types: the analysis did not finish');
            document.getElementById('fixer').hidden = true;

            // check.php answers a few failures (bad PHP version) with a bare string
            if (typeof response.error === 'string') {
                setRunState('error', 'error');
                renderPlainOutput(response.error);
                callback([]);
                return;
            }

            var isParserError = response.error.type === 'parser_error';
            setRunState(isParserError ? 'parse_error' : 'error', isParserError ? 'parse error' : 'error');
            renderPlainOutput(
                (isParserError ? 'Parser' : 'Internal Psalm') + ' error on line ' + response.error.line_from + ' - '
                    + response.error.message
            );

            if (typeof response.error.from !== 'number') {
                callback([]);
                return;
            }

            callback({
               message: response.error.message,
               severity: 'error',
               from: cm.posFromIndex(response.error.from),
               to: cm.posFromIndex(response.error.to),
            });
        }
    })
    .catch (function (error) {
        if (latestFetch == fetchKey) {
            setRunState('error', 'request failed');
        }
        console.log('Request failed', error);
    });
};

var fetchFixedContents = function (code, cm) {
    latestFetch++;
    fetchKey = latestFetch;
    setRunState('busy', 'fixing…');

    fetch('/check', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({
            code: code,
            settings: JSON.stringify({...settings, ...{unused_methods: true}}),
            php: phpVersion,
            fix: true,
        })
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (response) {
        if (latestFetch != fetchKey) {
            return;
        }

        document.getElementById('fixer').hidden = true;

        if ('fixed_contents' in response && response.fixed_contents) {
            // setValue triggers a fresh lint run, which updates the run state
            cm.setValue(response.fixed_contents);
        }
        else if ('error' in response) {
            setRunState('error', 'fix failed');
            renderPlainOutput(typeof response.error === 'string' ? response.error : response.error.message);
        } else {
            setRunState('done', 'nothing to fix');
        }
    })
    .catch (function (error) {
        setRunState('error', 'request failed');
        console.log('Request failed', error);
    });
};

var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
    lineNumbers: true,
    matchBrackets: true,
    lineSeparator: "\n",
    mode: 'application/x-httpd-php',
    inputStyle: 'contenteditable',
    indentWithTabs: false,
    indentUnit: 4,
    theme: 'elegant',
    lint: {
        getAnnotations: fetchAnnotations,
        async: true,
    }
});

document.getElementById('fixer').addEventListener('click', function () {
    fetchFixedContents(editor.getValue(), editor);
});

document.getElementById('psalm_output').addEventListener('click', function (event) {
    var location = event.target.closest('button.pg_loc');

    if (location) {
        jumpToIssue(Number(location.dataset.issue));
        return;
    }

    var step = event.target.closest('button.pg_step_jump');

    if (step) {
        selectRange(editor.posFromIndex(Number(step.dataset.from)), editor.posFromIndex(Number(step.dataset.to)));
    }
});

document.getElementById('pg_types').addEventListener('click', function (event) {
    var location = event.target.closest('button.pg_loc');
    var range = location && lastTypes[Number(location.dataset.type)];

    if (range) {
        selectRange(range.from, range.to);
    }
});

document.getElementById('pg_tabs').addEventListener('click', function (event) {
    var tab = event.target.closest('[role="tab"]');

    if (tab) {
        selectOutputTab(tab.dataset.tab, true);
    }
});

// arrow keys move between tabs, as in other tab lists
document.getElementById('pg_tabs').addEventListener('keydown', function (event) {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
        return;
    }

    var tabs = Array.prototype.slice.call(document.querySelectorAll('#pg_tabs [role="tab"]'));
    var index = tabs.indexOf(document.activeElement);

    if (index === -1) {
        return;
    }

    event.preventDefault();
    var next = tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
    selectOutputTab(next.dataset.tab, true);
    next.focus();
});

(function () {
    var saved = null;

    try {
        saved = localStorage.getItem(outputTabStorageKey);
    } catch (e) {
        saved = null;
    }

    selectOutputTab(saved === 'types' ? 'types' : 'issues', false);
})();

redrawSettings();

/**
 * GitHub-style line links: #L5 or #L5-L12 highlights those editor lines. Clicking a line number
 * selects it (Shift+click extends the range) and keeps the fragment in the address bar.
 * The highlight is tied to CodeMirror line handles, so it follows edits.
 */
var lineLink = (function () {
    var handles = [];
    // the line a Shift+click range extends from (a line handle, so it also follows edits)
    var anchor = null;

    var parse = function (hash) {
        var match = /^#L(\d+)(?:-L(\d+))?$/.exec(hash || '');

        if (!match) {
            return null;
        }

        var from = Number(match[1]);
        var to = match[2] ? Number(match[2]) : from;

        if (to < from) {
            var swap = from;
            from = to;
            to = swap;
        }

        // 1-based in the URL; a start past the end is ignored, the end is clamped to the last line
        if (from < 1 || from > editor.lineCount()) {
            return null;
        }

        return {from: from - 1, to: Math.min(to, editor.lineCount()) - 1};
    };

    var paint = function (add) {
        var method = add ? 'addLineClass' : 'removeLineClass';

        editor.operation(function () {
            handles.forEach(function (handle) {
                editor[method](handle, 'wrap', 'pg_line_hl');
                editor[method](handle, 'background', 'pg_line_hl_bg');
                editor[method](handle, 'gutter', 'pg_line_hl_gutter');
            });
        });
    };

    var range = function () {
        if (!handles.length) {
            return null;
        }

        return {from: editor.getLineNumber(handles[0]), to: editor.getLineNumber(handles[handles.length - 1])};
    };

    var fragment = function () {
        var current = range();

        if (!current) {
            return '';
        }

        return '#L' + (current.from + 1) + (current.to > current.from ? '-L' + (current.to + 1) : '');
    };

    // replaceState: no navigation, no hashchange, no extra history entry
    var writeUrl = function () {
        var url = window.location.pathname + window.location.search + fragment();

        if (url !== window.location.pathname + window.location.search + window.location.hash) {
            window.history.replaceState(window.history.state, '', url);
        }
    };

    var set = function (next) {
        paint(false);
        handles = [];

        if (next) {
            for (var line = next.from; line <= next.to; line++) {
                handles.push(editor.getLineHandle(line));
            }
        }

        paint(true);
    };

    var scrollToStart = function () {
        var current = range();

        if (current) {
            // scroll the editor itself, a third from the top, instead of jumping the page
            var top = editor.heightAtLine(current.from, 'local');
            editor.scrollTo(null, Math.max(0, top - editor.getScrollInfo().clientHeight / 3));
        }
    };

    var applyHash = function (scroll) {
        var next = parse(window.location.hash);

        set(next);
        anchor = next ? handles[0] : null;

        if (scroll) {
            scrollToStart();
        }
    };

    editor.on('gutterClick', function (cm, line, gutter, event) {
        if (gutter !== 'CodeMirror-linenumbers' || event.button !== 0) {
            return;
        }

        var current = range();
        var from = anchor && editor.getLineNumber(anchor);

        if (event.shiftKey && current) {
            from = from === null ? current.from : from;
            set({from: Math.min(from, line), to: Math.max(from, line)});
        } else if (current && current.from === line && current.to === line) {
            set(null);
            anchor = null;
        } else {
            set({from: line, to: line});
            anchor = handles[0];
        }

        writeUrl();
    });

    // follow edits: inserted lines inside the range join it, deleted lines drop out
    editor.on('changes', function () {
        if (!handles.length) {
            return;
        }

        var alive = handles.filter(function (handle) {
            return editor.getLineNumber(handle) !== null;
        });

        if (!alive.length) {
            set(null);
            anchor = null;
        } else {
            set({from: editor.getLineNumber(alive[0]), to: editor.getLineNumber(alive[alive.length - 1])});
        }

        writeUrl();
    });

    window.addEventListener('hashchange', function () {
        applyHash(true);
    });

    applyHash(true);

    return {
        fragment: fragment,
    };
})();

var isMac = /mac|iphone|ipad/i.test((navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform);

document.getElementById('getlink').title = 'Share: save the snippet and copy its link (' + (isMac ? '\u2318S' : 'Ctrl+S') + ')';

var focusEditor = function() {
    editor.focus();
    document.getElementById('playground').scrollIntoView({block: 'nearest'});

    return false;
};

// keyboard shortcuts; capture phase, so they also work while CodeMirror has focus
window.addEventListener('keydown', function (event) {
    var mod = (event.metaKey || event.ctrlKey) && !event.altKey;

    if (mod && event.key === 'Enter') {
        event.preventDefault();
        editor.performLint();
    } else if (mod && !event.shiftKey && (event.key === 's' || event.key === 'S')) {
        event.preventDefault();
        getLink();
    } else if (event.key === 'F8' && !event.metaKey && !event.ctrlKey && !event.altKey) {
        event.preventDefault();
        jumpToAdjacentIssue(event.shiftKey ? -1 : 1);
    }
}, true);

/**
 * Draggable editor/output splitter. The split is a percentage of the width the two panes share,
 * stored as --pg-split on #pg_panes and remembered per browser.
 */
(function () {
    var panes = document.getElementById('pg_panes');
    var splitter = document.getElementById('pg_splitter');
    var editorPane = panes.querySelector('.pg_editor');
    var outputPane = panes.querySelector('.pg_output');
    var storageKey = 'psalm.playground.split';
    var defaultSplit = 60;
    var minSplit = 20;
    var maxSplit = 80;
    var refreshQueued = false;

    var refreshEditor = function () {
        if (refreshQueued) {
            return;
        }

        refreshQueued = true;
        requestAnimationFrame(function () {
            refreshQueued = false;
            editor.refresh();
        });
    };

    var applySplit = function (value, persist) {
        value = Math.min(maxSplit, Math.max(minSplit, value));
        panes.style.setProperty('--pg-split', value.toFixed(1));
        splitter.setAttribute('aria-valuenow', String(Math.round(value)));
        refreshEditor();

        if (persist) {
            try {
                localStorage.setItem(storageKey, value.toFixed(1));
            } catch (e) {
                // storage can be unavailable (private mode, blocked site data); the split just is not remembered
            }
        }
    };

    splitter.setAttribute('aria-valuemin', String(minSplit));
    splitter.setAttribute('aria-valuemax', String(maxSplit));

    var savedSplit = null;

    try {
        savedSplit = parseFloat(localStorage.getItem(storageKey));
    } catch (e) {
        savedSplit = null;
    }

    applySplit(savedSplit > 0 ? savedSplit : defaultSplit, false);

    var splitFromPointer = function (clientX) {
        var left = editorPane.getBoundingClientRect().left;
        var shared = outputPane.getBoundingClientRect().right - left - splitter.offsetWidth;

        return shared > 0 ? (clientX - left - splitter.offsetWidth / 2) / shared * 100 : defaultSplit;
    };

    splitter.addEventListener('pointerdown', function (event) {
        if (event.button !== 0) {
            return;
        }

        event.preventDefault();
        splitter.setPointerCapture(event.pointerId);
        splitter.classList.add('dragging');
        document.body.classList.add('pg_resizing');
    });

    splitter.addEventListener('pointermove', function (event) {
        if (splitter.classList.contains('dragging')) {
            applySplit(splitFromPointer(event.clientX), false);
        }
    });

    var endDrag = function (event) {
        if (!splitter.classList.contains('dragging')) {
            return;
        }

        splitter.classList.remove('dragging');
        document.body.classList.remove('pg_resizing');
        applySplit(splitFromPointer(event.clientX), true);
    };

    splitter.addEventListener('pointerup', endDrag);
    splitter.addEventListener('pointercancel', endDrag);

    splitter.addEventListener('dblclick', function () {
        applySplit(defaultSplit, true);
    });

    splitter.addEventListener('keydown', function (event) {
        var current = parseFloat(panes.style.getPropertyValue('--pg-split')) || defaultSplit;

        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            applySplit(current + (event.key === 'ArrowLeft' ? -2 : 2), true);
        }
    });
})();

</script>
