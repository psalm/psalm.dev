/**
 * Turns the PHP code blocks of blog articles and docs pages into editors that /check analyses, showing
 * Psalm's issues and inferred types. A block becomes an editor when it nears the viewport, so a long docs
 * page doesn't run Psalm on every example as it loads. Each editor can open its code in the playground, and
 * blocks starting with "<?php // fix" get a button that applies Psalm's fixes. Needs codemirror.min.js.
 */
(function () {
    'use strict';

    if (!window.CodeMirror || !window.fetch) {
        return;
    }

    // Docs examples are written for Psalm's own defaults, as vimeo/psalm's DocumentationTest checks them:
    // errorLevel 1, without unused code detection or the playground's stricter checks. A first line like
    // <?php // findUnusedCode="true" changes them. Blog examples use the playground's defaults.
    const DOCS = {
        level: 1,
        settings: {
            unused_variables: false,
            unused_methods: false,
            ensure_array_offsets_exist: false,
            check_throws: false,
        },
    };
    const BLOG = {level: null, settings: {}};

    // /check fills in the default settings
    function check(code, options, fix) {
        const settings = fix ? Object.assign({}, options.settings, {unused_methods: true}) : options.settings;
        const body = new URLSearchParams({code: code, settings: JSON.stringify(settings)});
        if (options.level) {
            body.set('level', String(options.level));
        }
        if (fix) {
            body.set('fix', 'true');
        }

        return fetch('/check', {
            method: 'POST',
            headers: {'Accept': 'application/json'},
            body: body,
        }).then(function (response) {
            return response.json();
        });
    }

    // A prerendered page must not run Psalm until the user actually opens it
    function whenVisible(callback) {
        if (document.prerendering) {
            document.addEventListener('prerenderingchange', callback, {once: true});
        } else {
            callback();
        }
    }

    function annotations(response, cm) {
        if ('error' in response) {
            return [{
                message: response.error.message,
                severity: 'error',
                from: cm.posFromIndex(response.error.from ?? 0),
                to: cm.posFromIndex(response.error.to ?? cm.getLine(0).length),
            }];
        }

        return response.results.map(function (issue) {
            return {
                severity: issue.severity === 'error' ? 'error' : 'warning',
                message: issue.message,
                from: cm.posFromIndex(issue.from),
                to: cm.posFromIndex(issue.to),
            };
        }).concat(response.type_map.map(function (type_data) {
            return {
                severity: 'type',
                message: type_data.type,
                from: cm.posFromIndex(type_data.from),
                to: cm.posFromIndex(type_data.to),
            };
        }));
    }

    function makeEditor(block) {
        const text = block.textContent.replace(/\n$/, '');
        const options = block.parentNode.classList.contains('codehilite') ? DOCS : BLOG;
        const container = document.createElement('div');
        const textarea = document.createElement('textarea');
        textarea.value = text;
        container.className = 'cm_inline_container';
        container.appendChild(textarea);

        const bar = document.createElement('div');
        bar.className = 'cm_inline_bar';
        container.appendChild(bar);
        const button = function (label, title) {
            const element = document.createElement('button');
            element.type = 'button';
            element.innerHTML = label;
            element.title = title;
            bar.appendChild(element);
            return element;
        };

        // Only the response to the latest request of each editor is shown
        let latest = 0;
        const run = function (cm, fix) {
            const request = ++latest;
            return check(cm.getValue(), options, fix).then(function (response) {
                return request === latest ? response : null;
            });
        };

        // The icon and label of the homepage playground's Expand button
        const play_button = button(
            '<svg width="15" height="14" viewBox="0 0 15 14" fill="currentColor" aria-hidden="true" focusable="false">'
                + '<path d="M0 5h1.5v6.4L12.9 0l1.4 1.2L2.8 12.5H9V14H0z" fill-rule="evenodd"/></svg>Expand',
            'Open the code in the playground'
        );

        let fix_button = null;
        let reset_button = null;
        if (text.indexOf('<?php // fix') === 0) {
            fix_button = button('Fix code', 'Let Psalm rewrite the code');
            fix_button.className = 'cm_inline_fix';
            reset_button = button('Reset', 'Restore the original code');
        }

        // Docs blocks are a <pre> in a div.codehilite, which has its own styles
        const replaced = options === DOCS ? block.parentNode : block;
        replaced.parentNode.replaceChild(container, replaced);

        const cm = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            matchBrackets: true,
            mode: 'text/x-php',
            indentUnit: 2,
            theme: 'elegant',
            viewportMargin: Infinity,
            lint: {
                async: true,
                getAnnotations: function (code, callback, lint_options, cm) {
                    whenVisible(function () {
                        run(cm, false).then(function (response) {
                            if (response) {
                                callback(annotations(response, cm));
                            }
                        }).catch(function (error) {
                            console.log('Request failed', error);
                        });
                    });
                },
            },
        });

        // A form post opens the code in /play in a new tab, however long it is
        play_button.addEventListener('click', function () {
            const form = document.createElement('form');
            form.method = 'post';
            // ?level= is how the playground keeps its level in the URL
            form.action = '/play' + (options.level ? '?level=' + options.level : '');
            form.target = '_blank';
            const fields = {code: cm.getValue(), settings: JSON.stringify(options.settings)};
            Object.keys(fields).forEach(function (name) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = name;
                input.value = fields[name];
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
            form.remove();
        });

        if (fix_button) {
            fix_button.addEventListener('click', function () {
                run(cm, true).then(function (response) {
                    if (response && response.fixed_contents) {
                        cm.setValue(response.fixed_contents);
                    } else if (response && 'error' in response) {
                        // Re-lint so the error is highlighted in the editor
                        cm.performLint();
                    }
                }).catch(function (error) {
                    console.log('Request failed', error);
                });
            });

            reset_button.addEventListener('click', function () {
                cm.setValue(text);
            });
        }
    }

    const blocks = Array.prototype.filter.call(
        document.querySelectorAll('pre > code.language-php, .codehilite > pre'),
        function (element) {
            return element.textContent.indexOf('<?php') === 0;
        }
    ).map(function (element) {
        return element.tagName === 'PRE' ? element : element.parentNode;
    });

    if (!('IntersectionObserver' in window)) {
        blocks.forEach(makeEditor);
        return;
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                observer.unobserve(entry.target);
                makeEditor(entry.target);
            }
        });
    }, {rootMargin: '400px 0px'});

    blocks.forEach(function (block) {
        observer.observe(block);
    });
})();
