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

    // the shrunk widget has no Settings button
    if (!expanded && !document.getElementById('settings_panel').hasAttribute('hidden')) {
        toggleSettings();
    }

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

/** Keeps a choice in the URL (?php=, ?level=) so reloading preserves it; the base value needs no param. */
var setQueryParam = function(name, value, baseValue) {
    const params = new URLSearchParams(window.location.search);

    if (value === baseValue) {
        params.delete(name);
    } else {
        params.set(name, String(value));
    }

    const query = params.toString();
    window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash);
    loadedPage = window.location.pathname + window.location.search;
};

var setPhpVersion = function(version) {
    phpVersion = version;
    setQueryParam('php', version, basePhpVersion);
    renderPhpVersionLabels();
    editor.performLint();
};

var defaultErrorLevel = <?= json_encode(\PsalmDotOrg\OnlineChecker::DEFAULT_ERROR_LEVEL) ?>;
// a saved snippet opens with the level it was saved with; ?level= overrides it
var baseErrorLevel = (typeof snippetErrorLevel !== 'undefined' && snippetErrorLevel) || defaultErrorLevel;
var maxErrorLevel = <?= json_encode(\PsalmDotOrg\OnlineChecker::MAX_ERROR_LEVEL) ?>;
var errorLevel = (function () {
    // same range OnlineChecker::parseErrorLevel() accepts
    var fromUrl = Number(new URLSearchParams(window.location.search).get('level'));
    return Number.isInteger(fromUrl) && fromUrl >= 1 && fromUrl <= maxErrorLevel ? fromUrl : baseErrorLevel;
})();

var isErrorLevelChanged = function() {
    return errorLevel !== defaultErrorLevel;
};

/** Updates the level and keeps it in the URL (like ?php=), without re-running the analysis. */
var applyErrorLevel = function(level) {
    errorLevel = level;
    setQueryParam('level', level, baseErrorLevel);
};

var setErrorLevel = function(level) {
    applyErrorLevel(level);
    updateSettingsSummary();
    editor.performLint();
};

var renderPhpVersionLabels = function() {
    var text = 'PHP ' + phpVersion;
    var title = 'Analyze code as PHP ' + phpVersion + (phpVersion === defaultPhpVersion ? ', the default' : '');

    document.getElementById('php_version_label').textContent = text;
    document.getElementById('php_version').title = title;
};

var isSettingChanged = function(key) {
    return !!settings[key] !== !!defaultSettings[key];
};

/**
 * Updates the Settings button label ("Settings · N changed") and the per-setting markers,
 * without rebuilding the drawer, so keyboard focus stays where it is.
 */
var updateSettingsSummary = function() {
    var changedCount = Object.keys(settingsText).filter(isSettingChanged).length + (isErrorLevelChanged() ? 1 : 0);

    document.getElementById('settings').textContent = changedCount
        ? 'Settings \u00B7 ' + changedCount + ' changed'
        : 'Settings';

    var levelSelect = document.getElementById('error_level');

    if (levelSelect) {
        levelSelect.value = String(errorLevel);
        levelSelect.closest('.pg_check').classList.toggle('changed', isErrorLevelChanged());
    }

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

    applyErrorLevel(defaultErrorLevel);
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

    var reset = document.createElement('button');
    reset.type = 'button';
    reset.textContent = 'Reset';
    reset.title = 'Restore the default settings';
    reset.addEventListener('click', resetSettings);
    head.appendChild(reset);
    panel.appendChild(head);
    panel.appendChild(buildErrorLevelGroup());

    groups.forEach(function (group) {
        var fieldset = settingsGroup(group[0]);

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
                row.appendChild(docsLink(settingsDocs[key], 'Configuration docs for: ' + text.textContent));
            }

            fieldset.appendChild(row);
        });

        panel.appendChild(fieldset);
    });
};

/** A "docs" link for a settings row; it sits next to the label, so clicking it never toggles the control. */
var docsLink = function(href, ariaLabel) {
    var docs = document.createElement('a');
    docs.className = 'pg_doc_link';
    docs.href = href;
    docs.target = '_blank';
    docs.rel = 'noopener';
    docs.textContent = 'docs';
    docs.setAttribute('aria-label', ariaLabel);
    return docs;
};

var settingsGroup = function(title) {
    var fieldset = document.createElement('fieldset');
    fieldset.className = 'pg_group';

    var legend = document.createElement('legend');
    legend.textContent = title;
    fieldset.appendChild(legend);
    return fieldset;
};

var errorLevelHints = {1: 'strictest'};
errorLevelHints[maxErrorLevel] = 'most lenient';

var buildErrorLevelGroup = function() {
    var fieldset = settingsGroup('Reporting');

    var label = document.createElement('label');
    label.className = 'pg_check pg_select';
    label.htmlFor = 'error_level';

    var text = document.createElement('span');
    text.textContent = 'Error level';

    var select = document.createElement('select');
    select.id = 'error_level';
    select.title = 'Issues below this level are reported as info instead of errors';

    for (var level = 1; level <= maxErrorLevel; level++) {
        var option = document.createElement('option');
        var hints = [errorLevelHints[level], level === defaultErrorLevel ? 'default' : null].filter(Boolean);
        option.value = String(level);
        option.textContent = level + (hints.length ? ' (' + hints.join(', ') + ')' : '');
        option.selected = level === errorLevel;
        select.appendChild(option);
    }

    select.addEventListener('change', function () {
        setErrorLevel(Number(select.value));
    });

    label.appendChild(text);
    label.appendChild(select);

    var row = document.createElement('div');
    row.className = 'pg_check_row';
    row.appendChild(label);

    row.appendChild(docsLink(errorLevelsDocs, 'Docs for error levels'));

    fieldset.appendChild(row);
    return fieldset;
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

/** Shows a state on every Share button (the toolbar one and, on the homepage, the one under the shrunk widget). */
var setShareLabel = function(text, state) {
    clearTimeout(shareLabelTimer);

    document.querySelectorAll('[data-share]').forEach(function (button) {
        button.querySelector('.pg_share_label').textContent = text;

        if (state) {
            button.dataset.state = state;
        } else {
            delete button.dataset.state;
        }
    });

    // results go back to plain "Share" after a moment; "Saving…" stays until the result replaces it
    if (state && state !== 'busy') {
        shareLabelTimer = setTimeout(function () {
            setShareLabel('Share', null);
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
            body: serializeJSON({code: editor.getValue(), settings: JSON.stringify(settings), php: phpVersion, level: errorLevel})
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

        // the snippet now carries the chosen version and level, so the URL no longer needs ?php= or ?level=
        basePhpVersion = phpVersion;
        baseErrorLevel = errorLevel;
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

// id of the latest /check request; responses to older ones are dropped
var latestFetch = 0;

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
    document.querySelector('.playground .pg_output').classList.toggle('pg_is_running', running);
};

var setIssueCount = function(count) {
    document.getElementById('pg_issue_count').textContent = count === null ? '' : String(count);
};

// "Psalm 7.0.0+2 (ca4690d)": release, compare view and commit links; label/url when no tag was resolved
var renderPsalmVersion = function(response) {
    // one inline wrapper: as direct children of the flex status item, the text nodes would lose their spaces
    var element = document.createElement('span');
    var item = document.getElementById('pg_psalm_version');
    var link = function (href, text, title) {
        var a = document.createElement('a');
        a.href = href;
        a.target = '_blank';
        a.rel = 'noopener';
        a.textContent = text;
        a.title = title;
        return a;
    };

    element.textContent = 'Psalm ';

    if (response.version_tag) {
        element.appendChild(link(response.version_tag_url, response.version_tag, 'Release notes for ' + response.version_tag));

        if (response.version_compare_url) {
            element.appendChild(document.createTextNode('+'));
            element.appendChild(link(
                response.version_compare_url,
                String(response.version_ahead),
                response.version_ahead + ' commits after ' + response.version_tag
            ));
        }
    } else {
        element.appendChild(link(response.version_url, response.version_label, 'This Psalm build'));
    }

    if (response.version_commit) {
        element.appendChild(document.createTextNode(' ('));
        element.appendChild(link(response.version_commit_url, response.version_commit, 'The commit psalm.dev runs'));
        element.appendChild(document.createTextNode(')'));
    }

    item.textContent = '';
    item.appendChild(element);
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
        // the step numbers sit outside the text: make room for "10." and up so they don't cross the border
        + '<ol class="pg_steps" style="--pg-step-digits: ' + String(references.length).length + '">'
        + references.map(renderReference).join('') + '</ol>'
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

var renderIssue = function(issue, index, fixable) {
    var level = describeErrorLevel(issue.error_level);
    var isError = issue.severity === 'error';
    var html = '<div class="pg_issue ' + (isError ? 'pg_issue_error' : 'pg_issue_info') + '" data-issue="' + index + '">'
        + '<div class="pg_issue_head">'
        + '<span class="pg_sev">' + (isError ? 'ERROR' : 'INFO') + '</span>'
        + '<a href="' + escapeHtml(issue.link) + '" target="_blank" rel="noopener" title="Open the ' + escapeHtml(issue.type) + ' docs">' + escapeHtml(issue.type) + '</a>'
        + (level ? '<a class="pg_level" href="' + errorLevelsDocs + '#' + level.anchor + '" target="_blank" rel="noopener" title="'
            + escapeHtml(level.title) + '">' + escapeHtml(level.label) + '</a>' : '')
        + (fixable ? '<span class="pg_fixable_badge" title="The Fix button in the toolbar rewrites the code to fix issues like this one">auto-fixable</span>' : '')
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

        return '<div class="pg_type_row" data-type="' + index + '">'
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

var savedOutputTab = function() {
    try {
        return localStorage.getItem(outputTabStorageKey) === 'types' ? 'types' : 'issues';
    } catch (e) {
        return 'issues';
    }
};

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

// by position, errors before info on the same spot, then by type; Array.prototype.sort is stable for the rest
var sortIssues = function(results) {
    var rank = function (issue) { return issue.severity === 'error' ? 0 : 1; };

    return results.slice().sort(function (a, b) {
        return a.line_from - b.line_from
            || a.column_from - b.column_from
            || rank(a) - rank(b)
            || (a.type < b.type ? -1 : a.type > b.type ? 1 : 0);
    });
};

/**
 * F8 / Shift+F8: the next (direction 1) or previous (-1) issue in list order (already sorted by position),
 * wrapping around. With no issue selected, F8 starts at the first issue at or after the cursor and
 * Shift+F8 at the last one before it.
 */
var jumpToAdjacentIssue = function(direction) {
    var count = lastIssues.length;

    if (!count) {
        return;
    }

    var cursor = editor.getCursor('from');
    var target;

    if (currentIssue !== null && CodeMirror.cmpPos(cursor, lastIssues[currentIssue].from) === 0) {
        // still on the issue we jumped to: step through the list, so issues sharing a position are all visited
        target = (currentIssue + direction + count) % count;
    } else if (direction > 0) {
        target = lastIssues.findIndex(function (issue) { return CodeMirror.cmpPos(issue.from, cursor) >= 0; });
        target = target === -1 ? 0 : target;
    } else {
        target = count - 1 - lastIssues.slice().reverse().findIndex(function (issue) { return CodeMirror.cmpPos(issue.from, cursor) < 0; });
        target = target === count ? count - 1 : target;
    }

    jumpToIssue(target);
};

// Psalm's "Fixing code" docs; each supported issue type has a section there (#invalidreturntype)
var fixingDocs = '/docs/manipulating_code/fixing/';

var fixingDocsLink = function(type) {
    return fixingDocs + '#' + String(type).toLowerCase();
};

var plural = function(count, word) {
    return count + ' ' + word + (count === 1 ? '' : 's');
};

// fixable issue counts per type from the latest run (Psalm counts them per type, not per issue)
var lastFixableTypes = {};

var fixableTotal = function() {
    return Object.values(lastFixableTypes).reduce(function (sum, count) { return sum + count; }, 0);
};

// "Fix N issues" shows while the latest run found fixable issues and no fix is being previewed or compared
var updateFixButton = function() {
    var button = document.getElementById('fixer');
    var total = fixableTotal();

    button.hidden = total === 0 || fixComparison !== null || pendingFix !== null;
    button.textContent = 'Fix ' + plural(total, 'issue');
};

var renderFixableSummary = function() {
    var total = fixableTotal();

    updateFixButton();

    if (total) {
        outputElement().insertAdjacentHTML(
            'beforeend',
            '<div class="pg_fixable">Psalm detected <a href="' + fixingDocs + '" target="_blank" rel="noopener">'
                + plural(total, 'fixable issue') + '</a></div>'
        );
    }
};

/** POSTs the code to /check with the current settings, PHP version and level; extra fields override them. */
var postCheck = function(code, extra) {
    return fetch('/check', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON(Object.assign({
            code: code,
            settings: JSON.stringify(settings),
            php: phpVersion,
            level: errorLevel
        }, extra))
    }).then(function (response) {
        return response.json();
    });
};

var fetchAnnotations = function (code, callback, options, cm) {
    // A prerendered page must not run Psalm until the user actually opens it
    if (document.prerendering) {
        document.addEventListener('prerenderingchange', function () {
            fetchAnnotations(code, callback, options, cm);
        }, {once: true});
        return;
    }

    var fetchKey = ++latestFetch;

    setRunState('busy', 'analyzing…');

    postCheck(code, {})
    .then(function (response) {
        // a newer run (or a fix) started meanwhile
        if (fetchKey !== latestFetch) {
            return;
        }

        if ('results' in response) {
            // Psalm reports issues in analysis order; the list, F8, markers and bug reports all use this order
            response.results = sortIssues(response.results);
            renderPsalmVersion(response);

            lastIssues = response.results.map(function (issue) {
                return {
                    severity: issue.severity,
                    from: cm.posFromIndex(issue.from),
                    to: cm.posFromIndex(issue.to)
                };
            });

            currentIssue = null;
            lastFixableTypes = response.fixable_errors || {};
            setIssueCount(response.results.length);
            setRunState('done', '');

            if (response.results.length === 0) {
                outputElement().innerHTML = /^\s*(<\?php)?\s*$/i.test(code)
                    ? '<div class="pg_empty">Write some PHP to analyze</div>'
                    : '<div class="pg_empty">No issues!</div>';
            } else {
                // per-type counts only, so every issue of a fixable type gets the badge
                outputElement().innerHTML = response.results.map(function (issue, index) {
                    return renderIssue(issue, index, lastFixableTypes[issue.type] > 0);
                }).join('');
            }

            renderFixableSummary();
            renderTypes(response.type_map, code, cm);
            setNavigation(response);

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
            setNavigation(null);
            lastFixableTypes = {};
            updateFixButton();

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
        if (fetchKey === latestFetch) {
            setRunState('error', 'request failed');
        }
        console.log('Request failed', error);
    });
};

/**
 * After an applied fix: the code before and after it, so the toolbar can switch between them.
 * Any edit of either version ends the comparison.
 */
var fixComparison = null;

var renderFixComparison = function() {
    var button = document.getElementById('fix_compare');
    button.hidden = fixComparison === null;

    if (fixComparison !== null) {
        button.textContent = fixComparison.showingFixed ? 'Show original' : 'Show fixed';
    }

    updateFixButton();
};

/**
 * Line diff of two snippets: [{op: ' ' | '-' | '+', text, oldLine, newLine}].
 * Equal leading and trailing lines are matched directly; only the middle goes through a longest common
 * subsequence, which for a fix is a few lines. Past ~4M cells the middle falls back to "removed, then added".
 */
var diffLines = function(before, after) {
    var a = before.split('\n');
    var b = after.split('\n');
    var head = 0;

    while (head < a.length && head < b.length && a[head] === b[head]) {
        head++;
    }

    var tail = 0;

    while (tail < a.length - head && tail < b.length - head && a[a.length - 1 - tail] === b[b.length - 1 - tail]) {
        tail++;
    }

    var n = a.length - tail;
    var m = b.length - tail;
    var rows = [];
    var same = function (x, y) { rows.push({op: ' ', text: a[x], oldLine: x + 1, newLine: y + 1}); };
    var del = function (x) { rows.push({op: '-', text: a[x], oldLine: x + 1, newLine: null}); };
    var add = function (y) { rows.push({op: '+', text: b[y], oldLine: null, newLine: y + 1}); };

    for (var k = 0; k < head; k++) {
        same(k, k);
    }

    if ((n - head) * (m - head) > 4000000) {
        for (var d = head; d < n; d++) { del(d); }
        for (var e = head; e < m; e++) { add(e); }
    } else {
        // lcs[i - head][j - head]: common lines of a[i..n) and b[j..m); only the middle is allocated
        var lcs = [];
        var at = function (i, j) { return lcs[i - head][j - head]; };

        for (var i = n; i >= head; i--) {
            var row = lcs[i - head] = new Uint32Array(m - head + 1);

            for (var j = m - 1; i < n && j >= head; j--) {
                row[j - head] = a[i] === b[j] ? at(i + 1, j + 1) + 1 : Math.max(at(i + 1, j), row[j + 1 - head]);
            }
        }

        var x = head;
        var y = head;

        while (x < n || y < m) {
            if (x < n && y < m && a[x] === b[y]) {
                same(x++, y++);
            } else if (x < n && (y === m || at(x + 1, y) >= at(x, y + 1))) {
                // removals before additions, as in unified diffs
                del(x++);
            } else {
                add(y++);
            }
        }
    }

    for (var t = 0; t < tail; t++) {
        same(n + t, m + t);
    }

    return rows;
};

// unchanged lines kept around each change; longer unchanged runs fold into one "N unchanged lines" row
var diffContext = 3;

/**
 * PHP syntax tokens per line ([[text, style], ...] for each line), highlighted as one document
 * so comments and strings that span lines keep their colours.
 */
var highlightLines = function(code) {
    var lines = [[]];

    CodeMirror.runMode(code, 'application/x-httpd-php', function (text, style) {
        if (text === '\n') {
            lines.push([]);
        } else {
            lines[lines.length - 1].push([text, style || '']);
        }
    });

    return lines;
};

/** The part of each line that differs from its counterpart: [start, end) after the common prefix and suffix. */
var changedRange = function(line, counterpart) {
    var start = 0;
    var max = Math.min(line.length, counterpart.length);

    while (start < max && line[start] === counterpart[start]) {
        start++;
    }

    var end = 0;

    while (end < max - start && line[line.length - 1 - end] === counterpart[counterpart.length - 1 - end]) {
        end++;
    }

    // nothing in common: the whole line is the change, already shown by the row colour
    return start === 0 && end === 0 ? null : [start, line.length - end];
};

/** Syntax-highlighted HTML for one line, with the changed range (if any) wrapped in a darker mark. */
var renderDiffCode = function(tokens, range) {
    var html = '';
    var offset = 0;

    tokens.forEach(function (token) {
        var text = token[0];
        var classes = token[1] ? token[1].split(' ').map(function (style) { return 'cm-' + style; }).join(' ') : '';
        var tokenEnd = offset + text.length;
        // split the token at the range edges so the mark can wrap exactly the changed characters
        var cuts = [offset, tokenEnd];

        if (range) {
            [range[0], range[1]].forEach(function (cut) {
                if (cut > offset && cut < tokenEnd) {
                    cuts.push(cut);
                }
            });
        }

        cuts.sort(function (a, b) { return a - b; });

        for (var i = 0; i < cuts.length - 1; i++) {
            var piece = escapeHtml(text.slice(cuts[i] - offset, cuts[i + 1] - offset));
            var marked = range && cuts[i] >= range[0] && cuts[i + 1] <= range[1] && range[1] > range[0];
            var span = classes ? '<span class="' + classes + '">' + piece + '</span>' : piece;
            html += marked ? '<mark class="pg_diff_word">' + span + '</mark>' : span;
        }

        offset = tokenEnd;
    });

    return html === '' ? '&nbsp;' : html;
};


var renderDiff = function(rows, original, fixed) {
    var oldTokens = highlightLines(original);
    var newTokens = highlightLines(fixed);
    var keep = rows.map(function () { return false; });
    var ranges = rows.map(function () { return null; });

    rows.forEach(function (row, index) {
        if (row.op !== ' ') {
            for (var k = Math.max(0, index - diffContext); k <= Math.min(rows.length - 1, index + diffContext); k++) {
                keep[k] = true;
            }
        }
    });

    // pair each run of removed lines with the run of added lines after it, line by line, for word-level marks
    for (var i = 0; i < rows.length; i++) {
        if (rows[i].op !== '-') {
            continue;
        }

        var delStart = i;

        while (i < rows.length && rows[i].op === '-') {
            i++;
        }

        var addStart = i;

        while (i < rows.length && rows[i].op === '+') {
            i++;
        }

        for (var p = 0; p < Math.min(addStart - delStart, i - addStart); p++) {
            var del = rows[delStart + p];
            var add = rows[addStart + p];
            ranges[delStart + p] = changedRange(del.text, add.text);
            ranges[addStart + p] = changedRange(add.text, del.text);
        }

        i--;
    }

    var html = '';
    var skipped = 0;

    var flushSkipped = function() {
        if (skipped) {
            html += '<div class="pg_diff_row pg_diff_skip"><span class="pg_diff_num"></span><span class="pg_diff_num"></span>'
                + '<span class="pg_diff_op"></span><span class="pg_diff_code">'
                + skipped + (skipped === 1 ? ' unchanged line' : ' unchanged lines') + '</span></div>';
            skipped = 0;
        }
    };

    rows.forEach(function (row, index) {
        if (!keep[index]) {
            skipped++;
            return;
        }

        flushSkipped();
        var kind = row.op === '+' ? 'pg_diff_add' : (row.op === '-' ? 'pg_diff_del' : 'pg_diff_same');
        var tokens = row.op === '+' ? newTokens[row.newLine - 1] : oldTokens[row.oldLine - 1];
        html += '<div class="pg_diff_row ' + kind + '">'
            + '<span class="pg_diff_num">' + (row.oldLine || '') + '</span>'
            + '<span class="pg_diff_num">' + (row.newLine || '') + '</span>'
            + '<span class="pg_diff_op" aria-hidden="true">' + (row.op === ' ' ? '' : (row.op === '-' ? '−' : '+')) + '</span>'
            + '<code class="pg_diff_code">' + renderDiffCode(tokens || [[row.text, '']], ranges[index]) + '</code></div>';
    });

    flushSkipped();
    return html;
};

/**
 * "Fix N issues" first shows Psalm's fix as a diff in a modal dialog; the editor changes only on Apply.
 */
var pendingFix = null;

var hideFixPreview = function() {
    var preview = document.getElementById('pg_fix_preview');

    pendingFix = null;

    if (preview.open) {
        preview.close();
    }

    preview.innerHTML = '';
};

var showFixPreview = function(original, fixed) {
    var rows = diffLines(original, fixed);
    var added = rows.filter(function (row) { return row.op === '+'; }).length;
    var removed = rows.filter(function (row) { return row.op === '-'; }).length;
    var preview = document.getElementById('pg_fix_preview');

    pendingFix = {original: original, fixed: fixed};
    preview.innerHTML = '<div class="pg_bar pg_fix_preview_head">'
        + '<h2 id="pg_fix_preview_title">Psalm\'s fix</h2>'
        + '<span class="pg_diff_stat"><span class="pg_diff_stat_add">+' + added + '</span> <span class="pg_diff_stat_del">\u2212' + removed + '</span></span>'
        + '<span class="pg_spacer"></span>'
        + '<a class="pg_fix_docs" href="' + fixingDocs + '" target="_blank" rel="noopener">How fixing works</a>'
        + '</div>'
        + renderFixedTypes()
        + '<div class="pg_diff cm-s-elegant" aria-label="Changes Psalm would make">' + renderDiff(rows, original, fixed) + '</div>'
        + '<div class="pg_bar pg_fix_preview_foot">'
        + '<button type="button" class="pg_fix_cancel">Cancel</button>'
        + '<button type="button" class="pg_primary pg_fix_apply">Apply fix</button>'
        + '</div>';
    preview.showModal();
    preview.querySelector('.pg_fix_apply').focus();
};

/** "Fixes: InvalidReturnType ×1, ..." with each type linked to its section in the fixing docs. */
var renderFixedTypes = function() {
    var types = Object.keys(lastFixableTypes).filter(function (type) { return lastFixableTypes[type] > 0; });

    if (!types.length) {
        return '';
    }

    return '<div class="pg_fix_types">Fixes: ' + types.map(function (type) {
        var count = lastFixableTypes[type];

        return '<a href="' + escapeHtml(fixingDocsLink(type)) + '" target="_blank" rel="noopener" title="How Psalm fixes ' + escapeHtml(type) + '">'
            + escapeHtml(type) + '</a>' + (count > 1 ? ' \u00d7' + count : '');
    }).join(', ') + '</div>';
};

var applyPendingFix = function() {
    if (pendingFix === null) {
        return;
    }

    var fix = pendingFix;
    hideFixPreview();
    fixComparison = {original: fix.original, fixed: fix.fixed, showingFixed: true};
    // setValue triggers a fresh lint run, which updates the run state
    editor.setValue(fix.fixed);
    renderFixComparison();
    editor.focus();
};

var cancelPendingFix = function() {
    if (pendingFix === null) {
        return;
    }

    hideFixPreview();
    setRunState('done', '');
    // the code did not change, so the issues (and the Fix button) from the last run still apply
    updateFixButton();
    editor.focus();
};

var fetchFixedContents = function (code) {
    var fetchKey = ++latestFetch;
    setRunState('busy', 'fixing…');

    postCheck(code, {settings: JSON.stringify({...settings, unused_methods: true}), fix: true})
    .then(function (response) {
        if (fetchKey !== latestFetch) {
            return;
        }

        if ('fixed_contents' in response && response.fixed_contents && response.fixed_contents !== code) {
            setRunState('done', 'fix ready');
            showFixPreview(code, response.fixed_contents);
            updateFixButton();
            return;
        }

        // nothing Psalm could apply after all: drop the offer until the next run
        lastFixableTypes = {};
        updateFixButton();

        if ('error' in response) {
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
    // typed ( [ { and quotes get their closing pair, so the code keeps parsing and signature help works while typing a call
    autoCloseBrackets: true,
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

/*
 * Ctrl+click (Cmd+click on macOS) and signature help, from /check's references, arguments and signatures.
 * Their offsets belong to the code that was analyzed, so any edit drops them until the next result.
 */
var navigation = null;
var linkMark = null;
var signatureWidget = null;
var pointer = null;
var linkModifierDown = false;
var isMac = /mac|iphone|ipad/i.test((navigator.userAgentData && navigator.userAgentData.platform) || navigator.platform);

var isLinkModifier = function(event) {
    return isMac ? event.metaKey : event.ctrlKey;
};

/** The innermost entry covering index; arguments also count the position right after them. */
var innermostAt = function(entries, index, includeEnd) {
    var best = null;

    entries.forEach(function (entry) {
        if (entry.from <= index && (includeEnd ? index <= entry.to : index < entry.to)
            && (!best || entry.to - entry.from < best.to - best.from)
        ) {
            best = entry;
        }
    });

    return best;
};

var setNavigation = function(response) {
    navigation = response && response.references ? {
        references: response.references,
        arguments: response.arguments || [],
        signatures: response.signatures || {}
    } : null;

    updateLinkMark();
    updateSignatureHint();
};

var referenceAt = function(left, top) {
    if (!navigation) {
        return null;
    }

    var pos = editor.coordsChar({left: left, top: top}, 'window');
    var coords = editor.charCoords(pos, 'window');

    // coordsChar snaps to the nearest character: past the end of a line is not on a symbol
    if (left > coords.right + 2 || top < coords.top || top > coords.bottom) {
        return null;
    }

    return innermostAt(navigation.references, editor.indexFromPos(pos), false);
};

// the reference under linkMark; while the pointer stays on it, mousemove leaves the mark alone
var linkedReference = null;

var updateLinkMark = function() {
    var reference = linkModifierDown && pointer ? referenceAt(pointer.left, pointer.top) : null;

    if (reference === linkedReference) {
        return;
    }

    if (linkMark) {
        linkMark.clear();
        linkMark = null;
    }

    linkedReference = reference;

    if (!reference) {
        return;
    }

    linkMark = editor.markText(editor.posFromIndex(reference.from), editor.posFromIndex(reference.to), {
        className: 'pg_ref_link',
        attributes: {title: reference.target ? 'Go to definition' : 'Open in the PHP manual'}
    });
};

var followReference = function(reference) {
    if (reference.target) {
        selectRange(editor.posFromIndex(reference.target.from), editor.posFromIndex(reference.target.to));
    } else if (reference.docs) {
        window.open(reference.docs, '_blank', 'noopener');
    }
};

var hideSignatureHint = function() {
    if (signatureWidget) {
        signatureWidget.remove();
        signatureWidget = null;
    }
};

var updateSignatureHint = function() {
    hideSignatureHint();

    if (!navigation || !editor.hasFocus() || editor.somethingSelected()) {
        return;
    }

    var cursor = editor.getCursor();
    var argument = innermostAt(navigation.arguments, editor.indexFromPos(cursor), true);
    var signature = argument && navigation.signatures[argument.function];

    if (!signature) {
        return;
    }

    var params = signature.params;
    var param = params[argument.index] || null;

    // extra arguments of a variadic function belong to its last parameter
    if (!param && params.length && signature.label.slice(params[params.length - 1][0]).indexOf('...') !== -1) {
        param = params[params.length - 1];
    }

    var widget = document.createElement('div');
    widget.className = 'pg_signature';
    widget.setAttribute('role', 'tooltip');

    var add = function(text, className) {
        var span = document.createElement('span');
        span.textContent = text;

        if (className) {
            span.className = className;
        }

        widget.appendChild(span);
    };

    if (param) {
        add(signature.label.slice(0, param[0]));
        add(signature.label.slice(param[0], param[1]), 'pg_signature_param');
        add(signature.label.slice(param[1]));
    } else {
        add(signature.label);
    }

    widget.title = signature.label;
    signatureWidget = widget;
    editor.addWidget(cursor, widget, false, 'above');
};

editor.on('mousedown', function (cm, event) {
    if (event.button !== 0 || !isLinkModifier(event)) {
        return;
    }

    var reference = referenceAt(event.clientX, event.clientY);

    if (reference) {
        // keep CodeMirror from adding a second cursor
        event.preventDefault();
        followReference(reference);
    }
});

editor.getWrapperElement().addEventListener('mousemove', function (event) {
    pointer = {left: event.clientX, top: event.clientY};
    linkModifierDown = isLinkModifier(event);

    // without the modifier there is no link to draw, so skip the layout reads in referenceAt
    if (linkModifierDown || linkMark) {
        updateLinkMark();
    }
});

editor.getWrapperElement().addEventListener('mouseleave', function () {
    pointer = null;
    updateLinkMark();
});

['keydown', 'keyup'].forEach(function (type) {
    document.addEventListener(type, function (event) {
        var down = isLinkModifier(event);

        if (down !== linkModifierDown) {
            linkModifierDown = down;
            updateLinkMark();
        }
    });
});

editor.on('changes', function () {
    // results (and a fix) still in flight describe the old code; the lint run for the new code replaces them
    latestFetch++;
    navigation = null;
    updateLinkMark();
    hideSignatureHint();
    // the issue list is about to be redrawn for the new code
    clearHoverMark();
});

editor.on('cursorActivity', updateSignatureHint);
editor.on('blur', hideSignatureHint);

document.getElementById('fixer').addEventListener('click', function () {
    fetchFixedContents(editor.getValue());
});

document.getElementById('fix_compare').addEventListener('click', function () {
    if (fixComparison === null) {
        return;
    }

    fixComparison.showingFixed = !fixComparison.showingFixed;
    editor.setValue(fixComparison.showingFixed ? fixComparison.fixed : fixComparison.original);
    renderFixComparison();
});

// only the fix itself and the toggle use setValue; any other change is an edit, which ends the comparison
editor.on('change', function (cm, change) {
    if (change.origin !== 'setValue' && fixComparison !== null) {
        fixComparison = null;
        renderFixComparison();
    }
});

(function () {
    var preview = document.getElementById('pg_fix_preview');

    preview.addEventListener('click', function (event) {
        if (event.target.closest('.pg_fix_apply')) {
            applyPendingFix();
        } else if (event.target.closest('.pg_fix_cancel') || event.target === preview) {
            // the dialog element itself is the backdrop, outside its content
            cancelPendingFix();
        }
    });

    // Esc closes a modal dialog natively; treat it as Cancel
    preview.addEventListener('cancel', function (event) {
        event.preventDefault();
        cancelPendingFix();
    });
})();

/**
 * Hovering an issue (or one of its taint steps) or a Types row highlights its code;
 * the location button still moves the cursor there.
 */
var hoverMark = null;
var hoverSource = null;

var clearHoverMark = function() {
    if (hoverMark) {
        hoverMark.clear();
        hoverMark = null;
    }

    hoverSource = null;
};

var hoverRange = function(target) {
    var step = target.closest('button.pg_step_jump');

    if (step) {
        return {
            source: step,
            from: editor.posFromIndex(Number(step.dataset.from)),
            to: editor.posFromIndex(Number(step.dataset.to))
        };
    }

    var row = target.closest('.pg_type_row');
    var type = row && lastTypes[Number(row.dataset.type)];

    if (type) {
        return {source: row, from: type.from, to: type.to};
    }

    var card = target.closest('.pg_issue');
    var issue = card && lastIssues[Number(card.dataset.issue)];

    return issue ? {source: card, from: issue.from, to: issue.to} : null;
};

var showHoverMark = function(target) {
    var range = hoverRange(target);

    if (!range) {
        clearHoverMark();
        return;
    }

    if (range.source === hoverSource) {
        return;
    }

    clearHoverMark();
    hoverSource = range.source;

    // zero-width ranges (some issues point at a position) still get a visible mark
    var to = CodeMirror.cmpPos(range.from, range.to) === 0
        ? CodeMirror.Pos(range.to.line, range.to.ch + 1)
        : range.to;

    hoverMark = editor.markText(range.from, to, {className: 'pg_hover_mark'});
    // no-op when the range is already visible, so the editor only moves for off-screen issues
    editor.scrollIntoView({from: range.from, to: to}, 40);
};

['psalm_output', 'pg_types'].forEach(function (id) {
    var output = document.getElementById(id);

    output.addEventListener('mouseover', function (event) {
        showHoverMark(event.target);
    });

    output.addEventListener('mouseleave', clearHoverMark);

    // keyboard users get the same highlight while a location or step button has focus
    output.addEventListener('focusin', function (event) {
        showHoverMark(event.target);
    });

    output.addEventListener('focusout', function (event) {
        if (!output.contains(event.relatedTarget)) {
            clearHoverMark();
        }
    });
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

selectOutputTab(savedOutputTab(), false);

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
