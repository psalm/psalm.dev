<!--
  title: Psalm 7.15: full PHP 8.6 support, SQL-aware security analysis and weak crypto detection
  date: 2026-11-21
  author: Daniil Gentili
-->

[Psalm 7](https://psalm.dev/articles/psalm-7) shipped on November 2nd. Since then, Psalm has had **25 releases in 19 days**, from 7.0.1 to 7.15.0: more than 900 pull requests by 27 contributors.

**New: the Psalm [newsletter](https://psalm.dev/newsletter).** Get posts like this one, and the rest of Psalm's news, by email:

<!-- newsletter -->

Here are the biggest changes:

- **full PHP 8.6 support**: every deprecation from PHP 8.0 to 8.6 reported whatever PHP version you analyse, typed PHP 8.6 APIs, native symbols checked against the analysed PHP version, and the purity of the new builtins;
- **SQL parsing in security analysis**: Psalm parses your SQL queries and follows tainted values through the database, from the `INSERT` to every `SELECT` that reads them back, so it finds stored XSS;
- **a new security analysis tutorial**, from your first report to a clean run;
- **new security checks**: weak hashes and ciphers, seeded random numbers, insecure cookies, XXE, open redirects and session flows, all with CWE identifiers;
- **escaping checked against its context**: `real_escape_string()` outside quotes, or `htmlspecialchars()` in an unquoted attribute or a `href`, is reported;
- **fewer security false positives**: values validated with an anchored regex or an allowlist are trusted;
- **named argument contracts**: `@optional-arguments-by-name`, `@arguments-by-name` and `@stable-parameter-names`;
- **`markAllNonInternalCodeAsApi`** for libraries, and **`--min-type-coverage`** for CI;
- **faster, leaner runs**: up to 55% less CPU, a gigabyte less peak memory on big projects, and much faster warm-cache runs;
- **hundreds of type inference fixes**.

## Full PHP 8.6 support

Psalm 7.14 completes PHP 8.6 support ([#14413](https://github.com/vimeo/psalm/pull/14413)).

### Every deprecation from PHP 8.0 to 8.6

Psalm reports what PHP 8.0 to 8.6 deprecate, whatever PHP version you analyse, so you can fix it before you upgrade:

```php
<?php
function load(): string {
    try {
        return (string) file_get_contents('config.json');
    } finally {
        return ''; // DeprecatedSyntax: A return in a finally block is deprecated since PHP 8.6
    }
}

function describe(mixed $value, object $object): string {
    $float = is_double($value);     // DeprecatedFunction: deprecated since PHP 8.6, use is_float() instead
    $id = spl_object_hash($object); // DeprecatedFunction: deprecated since PHP 8.6, use spl_object_id() instead
    $year = strftime('%Y');         // DeprecatedFunction: deprecated since PHP 8.1, use date() or IntlDateFormatter::format() instead
    return $id . chr(300) . ($float ? 'f' : ''); // DeprecatedFunction: Passing an integer outside of 0..255 to chr() is deprecated since PHP 8.5
}

final class Session {
    public function name(): string {
        return get_class(); // DeprecatedFunction: deprecated since PHP 8.3, use self::class or static::class instead
    }

    public function __wakeup(): void {} // DeprecatedSyntax: deprecated since PHP 8.5, use __unserialize() instead
}

function open(stdClass $options = null): void {} // DeprecatedSyntax: declare the type nullable

function is(): void {} // ReservedWord: is becomes a keyword in PHP 8.6

$n = (integer) '1';    // DeprecatedSyntax: use (int) instead
echo "Hello ${n}";     // DeprecatedSyntax: use "{$n}" instead
$id = '';
$id++;                 // StringIncrement: Incrementing an empty string is deprecated since PHP 8.3
ini_set('assert.active', '1'); // DeprecatedFunction: use zend.assertions instead
```

Each message names the replacement. Psalm checks:

- **builtins:** more than 150 functions, methods, constants and properties (the PDO driver-specific ones included), plus ini settings and mbstring encodings;
- **arguments:** `get_class()` without arguments, `trigger_error()` with `E_USER_ERROR`, `chr()` outside 0..255, objects passed to `array_walk()` or `http_build_query()`, the default escape of `str_getcsv()`, the third argument of `define()`, and more;
- **syntax:** `return` in `finally`, generator constructors, values returned from destructors, implicitly nullable parameters, optional parameters before required ones, `${var}` interpolation, backticks, `(integer)` and the other cast aliases, `case 1;`, `0 ** -1`;
- **magic methods and interfaces:** `__sleep()`, `__wakeup()`, a nullable `__debugInfo()`, and `Serializable` without `__serialize()`;
- **names that become keywords:** `let`, `is` and `_` as names of classes, functions, constants and aliases;
- **string increments and decrements** PHP 8.3 deprecates ([`StringIncrement`](https://psalm.dev/docs/running_psalm/issues/StringIncrement/)).

User-defined global constants marked `#[\Deprecated]` are reported as `DeprecatedConstant` too. See [`DeprecatedSyntax`](https://psalm.dev/docs/running_psalm/issues/DeprecatedSyntax/) for the full list.

### New APIs, fully typed

```php
<?php
use Io\Poll\{Context, Event};

$poll = new Context();
$watcher = $poll->add(new StreamPollHandle(STDIN), [Event::Read], 42); // Io\Poll\Watcher<StreamPollHandle, 42>
$data = $watcher->getData();          // 42
$errors = stream_last_errors();       // list<StreamError>

$versions = ['8.4', '8.5', '8.6'];
$supported = array_filter($versions, version_compare(?, '8.6', '>=')); // array<int<0, 2>, '8.4'|'8.5'|'8.6'>

$factorial = function (int $n): int {
    $self = Closure::getCurrent();
    return $n <= 1 ? 1 : $n * $self($n - 1); // no issue: $self is the closure itself
};
```

- The new poll API keeps the types of its handles and data.
- Partial application works on functions with several signatures, such as `version_compare()`.
- `Closure::getCurrent()` has the signature of the closure it is called in ([#14467](https://github.com/vimeo/psalm/pull/14467)).

### Native symbols follow the analysed PHP version

Functions, classes, enums and constants newer than the analysed PHP version are reported, with the polyfill that ships them ([#14478](https://github.com/vimeo/psalm/pull/14478)):

```php
<?php // phpVersion="8.3"
$first = array_find([1, 2], fn(int $x): bool => $x > 1); // UndefinedFunction: introduced in PHP 8.4; define a polyfill (symfony/polyfill-php84 ships some of the symbols of that version), or raise the analysed PHP version
echo RoundingMode::HalfEven->name;                      // UndefinedClass: introduced in PHP 8.4
function parse(Uri\Rfc3986\Uri $uri): void {}           // UndefinedClass: introduced in PHP 8.5
echo PHP_BUILD_DATE;                                    // UndefinedConstant: introduced in PHP 8.5
```

Psalm's own polyfills no longer define symbols for the analysed project, and a project's polyfill of a native function no longer replaces the native signature.

### Purity of the new builtins

The functions PHP 8.1 to 8.6 add declare what they do, and so do the builtins that use randomness, the server, the process or the system ([#14465](https://github.com/vimeo/psalm/pull/14465)):

```php
<?php
/** @psalm-pure */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT); // ImpureFunctionCall: password_hash requires io
}

/** @psalm-pure */
function body(): array {
    return request_parse_body(); // ImpureFunctionCall: request_parse_body requires io
}

/** @psalm-pure */
function host(string $url): ?string {
    return Uri\Rfc3986\Uri::parse($url)?->getHost(); // no issue
}
```

### Also

- Property hook bodies are analysed, and methods called only from a hook are no longer reported as unused ([#13911](https://github.com/vimeo/psalm/pull/13911)).
- References to properties are analysed instead of reported as `UnsupportedPropertyReferenceUsage` ([#13955](https://github.com/vimeo/psalm/pull/13955)).
- `#[\Override]` is allowed on class constants and enum cases ([#14038](https://github.com/vimeo/psalm/pull/14038)).
- A typed class constant can be overridden with another value of its type, and an override that widens or drops the native type is reported ([#14485](https://github.com/vimeo/psalm/pull/14485)).

## Security analysis

### A new tutorial

The [security analysis docs](https://psalm.dev/docs/security_analysis/) are now a tutorial, from your first report to a clean run ([#14228](https://github.com/vimeo/psalm/pull/14228)):

- [reading a report](https://psalm.dev/docs/security_analysis/#reading-a-report): follow a taint trace from source to sink;
- [fixing reports](https://psalm.dev/docs/security_analysis/#fixing-reports): escape for the sink, validate the type or a regex, write allowlist helpers and your own escaping functions;
- [reports through shared code](https://psalm.dev/docs/security_analysis/#reports-through-shared-code): keep unrelated calls of a function or instances of a class apart with `@psalm-taint-specialize`;
- [command-line code](https://psalm.dev/docs/security_analysis/#command-line-code): keep terminal output out of HTML reports;
- [dealing with remaining reports](https://psalm.dev/docs/security_analysis/#dealing-with-remaining-reports): suppressions, ignored files and baselines;
- [what Psalm can't see](https://psalm.dev/docs/security_analysis/#what-psalm-cant-see): custom sources, sinks and flows, and debugging the taint graph;
- reference tables of [every source and taint kind](https://psalm.dev/docs/security_analysis/#built-in-sources-and-sinks), and how [security issues appear in reports](https://psalm.dev/docs/security_analysis/#security-issues-in-reports): CWE identifiers, SARIF and GitHub code scanning.

Each example marks what Psalm reports on each line.

### SQL parsing: taints flow through the database

Psalm reads the SQL of the queries you run with `PDO`, `mysqli`, `pgsql` and `SQLite3` ([#14159](https://github.com/vimeo/psalm/pull/14159)). What an `INSERT`, `REPLACE` or `UPDATE` stores in a column taints every `SELECT` of that column, wherever it runs:

```php
<?php
function saveComment(PDO $pdo): void {
    $pdo->prepare('INSERT INTO comments (author, body) VALUES (?, ?)')
        ->execute(['admin', $_POST['body'] ?? '']);
}

function showComment(PDO $pdo): void {
    $row = $pdo->query('SELECT author, body FROM comments')->fetch();
    echo (string) $row['author']; // no issue
    echo (string) $row['body'];   // TaintedHtml: stored XSS
}
```

`SELECT *` reads every column of the table. If Psalm can't tell the table or the column, the query stores in, or reads from, every column it can be. See [Databases](https://psalm.dev/docs/security_analysis/taint_flow/#databases).

### Weak cryptography, cookies and XXE

Some vulnerabilities don't need user input. Psalm now reports them too ([#14160](https://github.com/vimeo/psalm/pull/14160)):

```php
<?php
function legacy(string $password, string $data, string $key, int $userId): void {
    $hash = md5($password);                                               // WeakHash
    openssl_encrypt($data, 'aes-256-ecb', $key);                          // WeakCipher
    mt_srand($userId);                                                    // WeakRandom
    setcookie('session', $hash);                                          // InsecureCookie
    simplexml_load_string($data, SimpleXMLElement::class, LIBXML_NOENT);  // XmlExternalEntity
}
```

Every security issue now carries its [CWE](https://cwe.mitre.org/) identifiers: in console output (`Detected tainted SQL (CWE-89)`), in the JSON report, and as SARIF rule tags. SARIF reports use the issue type as the rule id, and give taint rules a `security-severity` score, so GitHub code scanning sorts them by severity ([#14156](https://github.com/vimeo/psalm/pull/14156), [#12809](https://github.com/vimeo/psalm/pull/12809)).

### Escaping in the wrong place

An escaping function only protects the position it was written for. Psalm now checks where an escaped value lands ([#14400](https://github.com/vimeo/psalm/pull/14400)):

```php
<?php
function search(mysqli $db): void {
    $id = (string) ($_GET['id'] ?? '');
    $db->query("SELECT * FROM users WHERE id = '" . $db->real_escape_string($id) . "'"); // no issue
    $db->query('SELECT * FROM users WHERE id = ' . $db->real_escape_string($id));        // TaintedSql: `1 OR 1=1` needs no quotes

    $url = (string) ($_GET['url'] ?? '');
    echo '<img alt="' . htmlspecialchars($url) . '">'; // no issue
    echo '<img src=' . htmlspecialchars($url) . '>';   // TaintedHtmlAttribute: `x onerror=alert(1)` adds an attribute
    echo '<a href="' . htmlspecialchars($url) . '">';  // TaintedUrlScheme: `javascript:alert(1)` runs on click
}
```

### Open redirects and sessions

A `Location` header now reports [`TaintedRedirect`](https://psalm.dev/docs/running_psalm/issues/TaintedRedirect/). A value stored in `$_SESSION` flows to every read of the same key, in any function ([#14158](https://github.com/vimeo/psalm/pull/14158)):

```php
<?php
function login(): void {
    $_SESSION['name'] = (string) ($_POST['name'] ?? '');
    header('Location: ' . (string) ($_GET['next'] ?? '/')); // TaintedRedirect
}

function greet(): void {
    exec('greet ' . (string) $_SESSION['name']); // TaintedShell
}
```

### Fewer false positives

Psalm removes the taints a value can't carry once it matches an anchored pattern, or is one of a list of literal words ([#14161](https://github.com/vimeo/psalm/pull/14161)):

```php
<?php
function run(): void {
    $id = (string) ($_GET['id'] ?? '');
    if (!preg_match('/^[a-z0-9_]+$/D', $id)) {
        exit;
    }
    echo $id; // no issue

    $command = (string) ($_GET['command'] ?? '');
    if (!in_array($command, ['ls', 'date'], true)) {
        exit;
    }
    exec($command); // no issue
}
```

A pattern that isn't anchored, or that allows the characters a taint needs (a space for a shell argument, a dot for a path), keeps the taints.

Also:

- an outgoing request is trusted when the URL's host is checked against a list of hosts ([#14170](https://github.com/vimeo/psalm/pull/14170));
- `json_encode()` without `JSON_PRETTY_PRINT` removes the `header` taint ([#12799](https://github.com/vimeo/psalm/pull/12799));
- `@psalm-taint-sink none $param` says a parameter has no sinks ([#14230](https://github.com/vimeo/psalm/pull/14230));
- plugins can record what a validator checks with `Codebase::addTaintValidation()`, so a value is trusted once the validator says it passed ([#14176](https://github.com/vimeo/psalm/pull/14176)).

### Command-line code

What `@psalm-cli-only` code prints goes to a terminal, so it isn't reported as `TaintedHtml`. Psalm now reports [`CliOnlyCall`](https://psalm.dev/docs/running_psalm/issues/CliOnlyCall/) when code that may run in a web request calls it ([#13611](https://github.com/vimeo/psalm/pull/13611)):

```php
<?php
/** @psalm-cli-only */
final class ImportCommand {
    public function run(): void {
        echo 'Importing ' . ($_SERVER['argv'][1] ?? '') . "\n"; // no issue: printed to a terminal
    }
}

function handleRequest(): void {
    (new ImportCommand())->run(); // CliOnlyCall
}
```

A guard function with `@psalm-assert 'cli' PHP_SAPI` marks the code after it as command-line code ([#13633](https://github.com/vimeo/psalm/pull/13633)). [`@psalm-cli-only-instances`](https://psalm.dev/docs/security_analysis/annotations/#psalm-cli-only-instances) does the same for the instance methods of a class only instantiated from the command line ([#13635](https://github.com/vimeo/psalm/pull/13635)). Plugins can mark a file's top-level code as CLI-only ([#13643](https://github.com/vimeo/psalm/pull/13643)).

### More sources and sinks

- Everything a PSR-7 message holds is a taint source, and so is a method implementing or overriding a source ([#14211](https://github.com/vimeo/psalm/pull/14211)). `UploadedFileInterface::moveTo()` reports a path the client chooses ([#14227](https://github.com/vimeo/psalm/pull/14227)).
- What symfony/yaml parses with `PARSE_OBJECT` is an unserialize sink ([#14172](https://github.com/vimeo/psalm/pull/14172)).
- Dynamic properties of a tainted object are tainted, and what a `preg_replace_callback()` callback returns is tracked ([#14162](https://github.com/vimeo/psalm/pull/14162), [#14067](https://github.com/vimeo/psalm/pull/14067)).

## Named argument contracts

PHP 8 made parameter names part of every function's API. Three new annotations, contributed by [@nicolas-grekas](https://github.com/nicolas-grekas), say which part of a signature callers can rely on ([#12389](https://github.com/vimeo/psalm/pull/12389)). Symfony documents them in its backward compatibility promise:

```php
<?php
/** @optional-arguments-by-name */
function connect(string $host, int $port = 5432, bool $persistent = false): void {}

connect('localhost', port: 5433); // no issue
connect('localhost', 5433);       // PositionalArgumentNotAllowed

/** @arguments-by-name */
function move(int $x, int $y): void {}

move(x: 1, y: 2); // no issue
move(1, 2);       // PositionalArgumentNotAllowed

interface Mailer {
    /** @stable-parameter-names */
    public function send(string $to, string $subject): void;
}

final class SmtpMailer implements Mailer {
    public function send(string $recipient, string $subject): void {} // ParamNameMismatch
}
```

- `@optional-arguments-by-name`: optional parameters can be added, moved or removed without breaking callers;
- `@arguments-by-name`: no parameter order is promised;
- `@stable-parameter-names`: both names and order are stable, and overrides can't rename parameters.

The tags also work on classes, interfaces, traits and enums, and overriding methods inherit them. See [the annotation docs](https://psalm.dev/docs/annotating_code/supported_annotations/#optional-arguments-by-name).

## For libraries: `markAllNonInternalCodeAsApi`

A library is used by code Psalm doesn't see, so unused-code detection reports most of its public API. With `markAllNonInternalCodeAsApi="true"`, every class that isn't `@internal` is treated as `@api` ([#13939](https://github.com/vimeo/psalm/pull/13939)):

```php
<?php
namespace Lib;

class Client {                         // no UnusedClass
    public function send(): void {     // no issue
        $this->encode();
    }
    private function encode(): void {} // no issue: used by an API method
    private function unused(): void {} // UnusedMethod
}

/** @internal */
final class Helper {}                  // UnusedClass
```

Whatever the setting, the code a package doesn't ship (classes only `autoload-dev` loads, files `.gitattributes` marks `export-ignore`) is internal. There's also a new guide on [how library authors help Psalm understand their package](https://psalm.dev/docs/annotating_code/library_authors/) ([#13773](https://github.com/vimeo/psalm/pull/13773)).

## Type inference

Most of these 900 pull requests fix type inference: narrowing, loops, references, templates, assertions and the stubs of builtin functions. Some highlights:

```php
<?php
/** @return iterable<int, string> */
function lines(): iterable {
    yield 'a';
    return 1; // HiddenGeneratorReturn: callers can't read it through iterable
}

/**
 * @template T
 * @param T $value
 * @return list<T>
 */
function wrap(mixed $value): array {
    return [$value];
}

$wrap = wrap(...);
$ints = $wrap(42); // list<42>: templates of first-class callables are inferred

/** @param int<0, 1000> $flags */
function masks(int $flags): void {
    $low = $flags & 0xFF; // int<0, 255>
    $big = $flags << 2;   // int<0, 4000>
}

function title(string $s): void {
    if (strlen($s) > 0) {
        // $s is non-empty-string
    }
}

$len = StrLen('abc'); // WrongCaseFunction
```

Also:

- [`HiddenGeneratorReturn`](https://psalm.dev/docs/running_psalm/issues/HiddenGeneratorReturn/) for a value a generator returns through a type without `Generator` ([#13485](https://github.com/vimeo/psalm/pull/13485));
- `@psalm-assert array-key-exists $array[$key]` ([#12634](https://github.com/vimeo/psalm/pull/12634));
- Psalm picks between the `@method` overloads of a magic method by their arguments ([#13352](https://github.com/vimeo/psalm/pull/13352));
- a `@psalm-suppress` docblock in front of an argument suppresses the issues of that argument ([#13288](https://github.com/vimeo/psalm/pull/13288));
- the result of a pure function called with literals is remembered ([#13422](https://github.com/vimeo/psalm/pull/13422));
- a value used as an array offset that may not be a valid key, such as `false`, is reported ([#12690](https://github.com/vimeo/psalm/pull/12690), [#12812](https://github.com/vimeo/psalm/pull/12812));
- trailing commas in array shapes and generic types ([#13228](https://github.com/vimeo/psalm/pull/13228), [#13208](https://github.com/vimeo/psalm/pull/13208));
- a protobuf extension stub for `Message`, `Timestamp` and `Duration` ([#12617](https://github.com/vimeo/psalm/pull/12617)).

## Performance

- Peak memory is 22 to 25% lower: 4.6 to 3.6 GB on Laravel ([#12573](https://github.com/vimeo/psalm/pull/12573)).
- Psalm no longer parses the same vendor files again and again: 10 to 55% less CPU on projects extending vendor classes ([#13234](https://github.com/vimeo/psalm/pull/13234)).
- Psalm analyses a file's namespace-level statements once, not once per subclass that follows into it: 33% less CPU on MediaWiki ([#13592](https://github.com/vimeo/psalm/pull/13592)). An inherited constructor is analysed once per subclass, for 5 to 9% less CPU ([#13240](https://github.com/vimeo/psalm/pull/13240)).
- Forked taint runs are about 40% faster, and taint graph nodes are stored as compact rows: worker results shrink from 600 to 170 MB, and the main process peaks 1.1 GB lower ([#13074](https://github.com/vimeo/psalm/pull/13074), [#13080](https://github.com/vimeo/psalm/pull/13080), [#13536](https://github.com/vimeo/psalm/pull/13536)).
- Cold runs of a big app use 18% less peak memory and 7% less wall time ([#13593](https://github.com/vimeo/psalm/pull/13593)).
- Warm-cache runs scan files without forking (14% faster), store one file per cache entry, and compress the consolidated cache with zstd ([#13251](https://github.com/vimeo/psalm/pull/13251), [#13249](https://github.com/vimeo/psalm/pull/13249), [#13309](https://github.com/vimeo/psalm/pull/13309)).

Thanks to [@jarstelfox](https://github.com/jarstelfox) and [@alies-dev](https://github.com/alies-dev) for most of this work!

## CLI, plugins and tooling

- `--min-type-coverage=95` (or the `minTypeCoverage` config attribute) fails the run when type coverage is below the given percentage ([#14075](https://github.com/vimeo/psalm/pull/14075)).
- The [Doctrine plugin](https://github.com/psalm/psalm-plugin-doctrine) is bundled, and is enabled when your `composer.json` requires Doctrine ([#14165](https://github.com/vimeo/psalm/pull/14165)). The sabre/event plugin is no longer bundled ([#13478](https://github.com/vimeo/psalm/pull/13478)).
- Psalm lists the enabled plugins when it starts ([#14510](https://github.com/vimeo/psalm/pull/14510)).
- Plugins can declare the issues Psalter can fix ([#13515](https://github.com/vimeo/psalm/pull/13515)), and report issues in files Psalm doesn't analyse with `Codebase::addReportableFiles()` ([#13725](https://github.com/vimeo/psalm/pull/13725)).
- `psalter --dry-run` exits with code 2 when it would change files, so you can use it in CI ([#13666](https://github.com/vimeo/psalm/pull/13666)). Psalter's output is easier to read ([#13656](https://github.com/vimeo/psalm/pull/13656)).
- The language server applies the baseline, as the CLI does ([#13775](https://github.com/vimeo/psalm/pull/13775)).
- Psalm honours `FORCE_COLOR` ([#13665](https://github.com/vimeo/psalm/pull/13665)), and reports GitHub Actions pull requests and other CI services to [Shepherd](https://psalm.dev/docs/running_psalm/shepherd/) ([#14472](https://github.com/vimeo/psalm/pull/14472)).

## Upgrading

```bash
composer require --dev vimeo/psalm:^7.15 -W
```

New security checks and better inference can report new issues. Fix them, or add them to your [baseline](https://psalm.dev/docs/running_psalm/dealing_with_code_issues/#using-a-baseline-file) with `--set-baseline`.

## Thanks

Thanks to everyone who contributed since 7.0.0, and especially to [@jirkace](https://github.com/jirkace) (more than 500 pull requests!), [@orklah](https://github.com/orklah) and [@alies-dev](https://github.com/alies-dev). Thanks as well to [@shu-chen-dot-ca](https://github.com/shu-chen-dot-ca), [@MohannadElemary](https://github.com/MohannadElemary), [@jarstelfox](https://github.com/jarstelfox), [@roxblnfk](https://github.com/roxblnfk), [@azjezz](https://github.com/azjezz), [@nicolas-grekas](https://github.com/nicolas-grekas) and the 17 people who made their first contribution to Psalm.

Subscribe to the [newsletter](https://psalm.dev/newsletter) below to get the next post by email, and join us on [Discord](https://discord.gg/RwH8rEpxt9)!

**Full Changelog**: [7.0.0...7.15.0](https://github.com/vimeo/psalm/compare/7.0.0...7.15.0)

<div class="discuss"><a href="https://news.ycombinator.com/item?id=50592935">Comment on Hacker News</a></div>
