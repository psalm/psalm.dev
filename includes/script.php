<?php require_once dirname(__DIR__) . '/src/Settings.php' ?>
<?php require_once dirname(__DIR__) . '/src/OnlineChecker.php' ?>
<script>
// the playground is always expanded now; kept as no-ops for old bookmarklets and inline handlers
var expandCode = function() {
    return false;
};

var shrinkCode = function() {
    return false;
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

    editor.performLint();
};

var toggleSetting = function(key) {
    if (key in settings) {
        settings[key] = !settings[key];
    } else {
        settings[key] = true;
    }

    editor.performLint();

    return false;
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

    var settingsLines = [];

    Object.keys(settingsText).forEach(function (key) {
        var checked = key in settings && settings[key];
        var clickHandler = 'javascript:toggleSetting(\'' + key + '\')';
        var input = '<input id="' + key + '" type="checkbox" onclick="' + clickHandler + '"' + (checked ? ' checked' : '') + '>';

        settingsLines.push(
            '<div>' + input + ' <label for="' + key + '">' + settingsText[key] + '</label></div>'
        );
    });
    document.getElementById('settings_panel').innerHTML = settingsLines.join('\n');
};

var getLink = async function() {
    const response = await fetch('/add_code', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({code: editor.getValue(), settings: JSON.stringify(settings), php: phpVersion})
    });

    const url = await response.text();

    if (!response.ok || url.indexOf('/r/') === -1) {
        // add_code.php answers with short plain-text messages; anything else (HTML, a PHP error, nothing) is not shown
        const isPlainMessage = url.length > 0 && url.length < 300 && url.indexOf('<') === -1;
        alert(isPlainMessage
            ? url
            : 'Could not create a link: the server returned HTTP ' + response.status + '. Please try again later');
        return false;
    }

    // the PHP version is stored with the snippet, so the link needs no query params
    const scheme = window.location.protocol;
    const fullUrl = scheme + '//' + url;

    try {
        await navigator.clipboard.writeText(fullUrl);
    } catch(error) {
        console.warn('Could not copy URL to clipboard', error);
    }

    window.location.assign(fullUrl);
    return false;
};

var serializeJSON = function(data) {
    return Object.keys(data).map(function (keyName) {
        return encodeURIComponent(keyName) + '=' + encodeURIComponent(data[keyName])
    }).join('&');
}

var latestFetch = 0;
var fetchKey = 0;

// issues from the latest successful run, in Psalm's order; read by the issue navigation
var lastIssues = [];

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
var setRunState = function(state, label) {
    var element = document.getElementById('pg_run_state');
    element.dataset.state = state;
    element.textContent = label;
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
    versionLink.textContent = response.version;
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

    if (response.php_version) {
        element.appendChild(document.createTextNode(' on PHP ' + response.php_version));
    }
};

var renderPlainOutput = function(text) {
    var element = document.createElement('div');
    element.className = 'pg_plain';
    element.textContent = text;
    outputElement().textContent = '';
    outputElement().appendChild(element);
};

var renderReference = function(reference) {
    var html = '<div class="pg_ref">' + escapeHtml(reference.label);

    if (!('snippet' in reference)) {
        return html + '</div>';
    }

    var snippet = reference.snippet;
    var selection_start = reference.from - reference.snippet_from;
    var selection_end = reference.to - reference.snippet_from;

    return html + ' - ' + escapeHtml(reference.line_from) + ':' + escapeHtml(reference.column_from)
        + '<span class="pg_ref_snippet">'
        + escapeHtml(snippet.substring(0, selection_start))
        + '<mark>' + escapeHtml(snippet.substring(selection_start, selection_end)) + '</mark>'
        + escapeHtml(snippet.substring(selection_end))
        + '</span></div>';
};

var renderIssue = function(issue, index) {
    var isError = issue.severity === 'error';
    var html = '<div class="pg_issue ' + (isError ? 'pg_issue_error' : 'pg_issue_info') + '" data-issue="' + index + '">'
        + '<div class="pg_issue_head">'
        + '<span class="pg_sev">' + (isError ? 'ERROR' : 'INFO') + '</span>'
        + '<a href="' + escapeHtml(issue.link) + '" target="_blank">' + escapeHtml(issue.type) + '</a>'
        + '<button type="button" class="pg_loc" data-issue="' + index + '" title="Jump to this issue">'
        + escapeHtml(issue.line_from) + ':' + escapeHtml(issue.column_from) + '</button>'
        + '</div>'
        + '<div class="pg_issue_msg">' + escapeHtml(issue.message) + '</div>';

    if (issue.other_references) {
        html += '<div class="pg_refs">' + issue.other_references.map(renderReference).join('') + '</div>';
    }

    if (issue.taint_trace) {
        html += '<div class="pg_refs pg_taint_trace">' + issue.taint_trace.map(renderReference).join('') + '</div>';
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

/**
 * Moves the editor cursor to an issue from the latest run and highlights it in the output.
 */
var jumpToIssue = function(index) {
    var issue = lastIssues[index];

    if (!issue) {
        return;
    }

    editor.focus();
    editor.setSelection(issue.from, issue.to);
    editor.scrollIntoView({from: issue.from, to: issue.to}, 60);

    var output = outputElement();
    var current = output.querySelector('.pg_issue.current');

    if (current) {
        current.classList.remove('current');
    }

    var element = output.querySelector('.pg_issue[data-issue="' + index + '"]');

    if (element) {
        element.classList.add('current');
        element.scrollIntoView({block: 'nearest'});
    }
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

    var startedAt = performance.now();
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

            setIssueCount(response.results.length);
            setRunState('done', 'done in ' + Math.round(performance.now() - startedAt) + ' ms');

            if (response.results.length === 0) {
                outputElement().innerHTML = '<div class="pg_empty">No issues!</div>';
            } else {
                outputElement().innerHTML = response.results.map(renderIssue).join('');
            }

            setFixableCount(response, cm);

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
            setIssueCount(null);
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
    }
});

redrawSettings();

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
