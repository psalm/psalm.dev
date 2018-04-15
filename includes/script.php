<script>
var expandCode = function() {
    document.querySelector('body').classList.add('code_expanded');
    return false;
};

var shrinkCode = function() {
    document.querySelector('body').classList.remove('code_expanded');
    return false;
};

var getLink = function() {
    fetch('/add_code.php', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({code: editor.getValue()})
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

var latestFetch = 0;

var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
    lineNumbers: true,
    matchBrackets: true,
    mode: "text/x-php",
    indentUnit: 2,
    theme: 'elegant',
    lint: {
        getAnnotations: function (code, callback, options, cm) {
            latestFetch++;
            fetchKey = latestFetch;
            fetch('/check.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: serializeJSON({code: code})
            })
            .then(function (response) {
                return response.json();
            })
            .then(function (response) {
                if (latestFetch != fetchKey) {
                    return;
                }
                
                if ('results' in response) {
                    if (response.results.length === 0) {
                        callback([]);
                    }
                    else {
                        callback(response.results.map(function (issue) {
                            return {
                                severity: issue.severity === 'error' ? 'error' : 'warning',
                                message: issue.message,
                                from: cm.posFromIndex(issue.from),
                                to: cm.posFromIndex(issue.to)
                            };
                        }));
                    }  
                }
                else {
                    callback({
                       message: response.error.message,
                       severity: 'error',
                       from: CodeMirror.Pos(error.line - 1, start),
                       to: CodeMirror.Pos(error.line - 1, end)
                    });
                }
            })
            .catch (function (error) {
                console.log('Request failed', error);
            });
        },
        async: true,
    }
});

//editor.focus();
editor.setCursor(editor.lineCount(), 0);

</script>