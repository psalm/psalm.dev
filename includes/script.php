<script>
var expandCode = function() {
    document.querySelector('body').classList.add('code_expanded');
    return false;
};

var shrinkCode = function() {
    document.querySelector('body').classList.remove('code_expanded');
    return false;
};
    
var toggleSettings = function() {
    document.querySelector('#settings_panel').classList.toggle('hidden');
    document.querySelector('#psalm_output').classList.toggle('hidden');
    return false;
};
    
var settingsText = {
    'unused_variables': 'Detect unused variables and parameters',
    'unused_methods': 'Detect unused classes and methods',
    'memoize_properties': 'Memoize property assignments',
    'memoize_method_calls': 'Memoize simple method calls',
    'check_throws': 'Check for <code>@throws</code> docblock',
    'restrict_return_types': 'Force return types to be as tight as possible',
    'allow_phpstorm_generics': 'Allow PHPStorm generic annotations (e.g. Traversable|string[])',
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

var redrawSettings = function() {
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

var getLink = function() {
    fetch('/add_code', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({code: editor.getValue(), settings: JSON.stringify(settings)})
    })
    .then(function (response) {
        return response.text();
    })
    .then(function (response) {
        if (response.indexOf('/r/') === -1) {
            alert(response);
        } else {
            window.location = '//' + response;
        }
    });
    return false;
};

var serializeJSON = function(data) {
    return Object.keys(data).map(function (keyName) {
        return encodeURIComponent(keyName) + '=' + encodeURIComponent(data[keyName])
    }).join('&');
}

var urlParams = new URLSearchParams(window.location.search);

var latestFetch = 0;

let fix_button = null;

var fetchAnnotations = function (code, callback, options, cm) {
    latestFetch++;
    fetchKey = latestFetch;

    var submitData = {
        code: code,
        settings: JSON.stringify(settings)
    };

    if (urlParams.has('php')) {
        submitData.php = urlParams.get('php');
    }

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
            var psalm_version = response.version;
            
            if (psalm_version.indexOf('@')) {
                psalm_version = psalm_version.split('@')[1];
            }

            var psalm_header = 'Psalm output (using commit ' + psalm_version.substring(0, 7) + '): <br><br>'

            if (response.results.length === 0) {
                document.getElementById('psalm_output').innerHTML = psalm_header + 'No issues!';

                callback(
                    response.type_map.map(
                        function (type_data) {
                            return {
                                severity: 'type',
                                message: type_data.type,
                                from: cm.posFromIndex(type_data.from),
                                to: cm.posFromIndex(type_data.to)
                            };
                        }
                    )
                );
            }
            else {
                var text = response.results.map(
                    function (issue) {
                        let message = (issue.severity === 'error' ? 'ERROR' : 'INFO') + ': '
                            + '<a href="' + issue.link + '">' + issue.type + '</a> - ' + issue.line_from + ':'
                            + issue.column_from + ' - ' + issue.message.replace(/[\u00A0-\u9999<>\&]/gim);

                        if (issue.other_references) {
                            message += "<br><br>"
                                + issue.other_references.map(
                                    function (reference) {
                                        return '&nbsp;&nbsp;' + reference.label
                                            + ' - ' + reference.line_from
                                            + ':' + reference.column_from;
                                    }
                                ).join("<br><br>");
                        }

                        return message;
                    }
                );

                document.getElementById('psalm_output').innerHTML = psalm_header + text.join('<br><br>') + '<br>&nbsp;';

                callback(
                    response.results.map(
                        function (issue) {
                            return {
                                severity: issue.severity === 'error' ? 'error' : 'warning',
                                message: issue.message,
                                from: cm.posFromIndex(issue.from),
                                to: cm.posFromIndex(issue.to)
                            };
                        }
                    ).concat(
                        response.type_map.map(
                            function (type_data) {
                                return {
                                    severity: 'type',
                                    message: type_data.type,
                                    from: cm.posFromIndex(type_data.from),
                                    to: cm.posFromIndex(type_data.to)
                                };
                            }
                        )
                    )
                );
            }

            if ('fixable_errors' in response && response.fixable_errors) {
                let error_count = 0;

                for (type in response.fixable_errors) {
                    error_count += response.fixable_errors[type];
                }

                if (error_count) {
                    document.getElementById('psalm_output').innerHTML += '<br>Psalm detected ' + error_count + ' <a href="/docs/manipulating_code/fixing/">fixable issue(s)</a><br>&nbsp;';

                    const textarea = cm.getTextArea()
                    const container = textarea.parentNode;

                    if (!fix_button) {
                        fix_button = document.createElement('button');
                        fix_button.id = "fixer";
                        fix_button.innerHTML = '<svg width="18" height="18" viewBox="0 0 147 149" xmlns="http://www.w3.org/2000/svg"><path d="M67 84V71h14l64 57c0 4-2 8-6 12-3 4-7 6-11 7L67 84z" fill="#000"/><path d="M55 85l2-27 27 1-23-16 14-23-24 12L37 9l-2 27-27-1 23 16-14 23 24-12 14 23z" stroke="#000" stroke-width="5" fill="#FFF"/></g></svg> Fix code';
                        container.querySelector('.button_bar').appendChild(fix_button);

                        fix_button.addEventListener(
                            'click',
                            function() {
                                fetchFixedContents(cm.getValue(), cm);
                            }
                        );
                    }
                } else {
                    fix_button.parentNode.removeChild(fix_button);
                    fix_button = null;
                }
            }
        }
        else if ('error' in response) {
            var error_type = response.error.type === 'parser_error' ? 'Parser' : 'Internal Psalm';
            document.getElementById('psalm_output').innerText = 'Psalm runner output: \n\n'
                + error_type + ' error on line ' + response.error.line_from + ' - '
                + response.error.message;

            console.log(cm.posFromIndex(response.error.to));

            callback({
               message: response.error.message,
               severity: 'error',
               from: cm.posFromIndex(response.error.from),
               to: cm.posFromIndex(response.error.to),
            });
        }
    })
    .catch (function (error) {
        console.log('Request failed', error);
    });
};

var fetchFixedContents = function (code, cm) {
    latestFetch++;
    fetchKey = latestFetch;
    fetch('/check', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({
            code: code,
            settings: JSON.stringify({...settings, ...{unused_methods: true}}),
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

        fix_button.parentNode.removeChild(fix_button);
        fix_button = null;                        

        if ('fixed_contents' in response && response.fixed_contents) {
            cm.setValue(response.fixed_contents);
        }
        else if ('error' in response) {
            callback({
               message: response.error.message,
               severity: 'error',
               from: cm.posFromIndex(response.error.from),
               to: cm.posFromIndex(response.error.to),
            });
        }
    })
    .catch (function (error) {
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

//editor.focus();
//editor.setCursor(editor.lineCount(), 0);
    
redrawSettings();

</script>
