<!--
  title: Announcing Psalm 7
  date: 2026-11-02
  author: Daniil Gentili
-->

[Cross-posted from Daniil Gentili's blog &raquo;](https://blog.daniil.it/2026/11/02/announcing-psalm-7/).

Announcing the release of Psalm 7!

Featuring a simply enormous number of new features and bug fixes, such as:

- **security analysis on by default**: fully refactored for 10x+ performance and much greater coverage, in the same run as type checking and dead code detection;
- **much deeper security analysis taint tracking**: NoSQL, LLM prompt and URL injection, new sources like `php://input` and uploaded file names, flows through closures, generators, traits and by-reference parameters, and ready-made stubs for Guzzle, PSR-7, Monolog, MongoDB and more.
- **a new purity model** based on Hack's capabilities: declare exactly which side effects a function may have (`io`, `time`, `write-props`...), with purity templates for classes, closures and automatically inferred annotations;
- **graph-based dead code detection**: If nothing reachable uses a class, function, method, constant, variable, etc, Psalm finds it, now properly handling unused cycles as well.
- **Template defaults** (`@template TError of Throwable = RuntimeException`) for classes, functions and methods: `Result<int>` just works, and libraries can add templates without breaking their users.
- **PHP 8.5 and 8.6 support**: Full support for Partial Function Application, the pipe operator, and much more!
- **Daily releases.** Features and fixes reach you the day they land, no more waiting on `dev-master`.
- **Framework plugins in the box.** The PHPUnit, Symfony, Laravel, Mockery and sabre/event plugins ship with Psalm and switch on by themselves. Zero setup.
- **a bigger Psalm maintainer team**: [@alies-dev](https://github.com/alies-dev) has joined the Psalm maintainer team, contributing many new useful features and bugfixes!
- **An official [Discord community!](https://discord.gg/RwH8rEpxt9)**
- **And so, so many other new features and bugfixes!**

Psalm 7 runs on 64-bit PHP 8.2 to 8.6. Upgrading is simple for most projects: see [Upgrading](#upgrading) below.

## Security analysis: faster, always on, in a single run

Taints are now stored as a bitmask, and the taint graph is resolved over compact summaries of each function. This makes security analysis up to **10x faster**. 

**Combined analysis** ([#11384](https://github.com/vimeo/psalm/pull/11384)) runs type checking, security analysis and dead code detection together in one run, so **taint analysis is now on by default** ([#11399](https://github.com/vimeo/psalm/pull/11399)). 

Taint issues are reported with a `SECURITY` severity, and with a rank in SARIF reports ([#12068](https://github.com/vimeo/psalm/pull/12068), [#11383](https://github.com/vimeo/psalm/pull/11383)). The results no longer change between runs or with the thread count ([#11946](https://github.com/vimeo/psalm/pull/11946), [#11952](https://github.com/vimeo/psalm/pull/11952), [#11953](https://github.com/vimeo/psalm/pull/11953)).

## Deeper taint tracking

```php
<?php
$run = function (string $c): void {
    shell_exec($c);
};
$run((string) $_GET['cmd']); // TaintedShell, through the closure

$mongo = new MongoDB\Driver\Manager('mongodb://localhost');
$mongo->executeQuery('db.users', new MongoDB\Driver\Query(['name' => $_GET['name']])); // TaintedNosql

/** @return mixed */
function body() {
    return json_decode((string) file_get_contents('php://input'), true);
}
eval((string) body()); // TaintedEval: php://input is a source
```

**[Try it on psalm.dev »](https://psalm.dev/r/7631291cc5)**

- **New taint kinds:** [`TaintedNosql`](https://psalm.dev/docs/running_psalm/issues/TaintedNosql/) (MongoDB sinks), [`TaintedLlmPrompt`](https://psalm.dev/docs/running_psalm/issues/TaintedLlmPrompt/) (prompt injection), and [`TaintedUrlComponent`](https://psalm.dev/docs/running_psalm/issues/TaintedUrlComponent/) / [`TaintedUrlPath`](https://psalm.dev/docs/running_psalm/issues/TaintedUrlPath/) (query and path injection). curl options and `CURLFile` are reported by what they do: SSRF, header injection, file access, sleep or callables. [Every taint type is now documented](https://psalm.dev/docs/security_analysis/) ([#11944](https://github.com/vimeo/psalm/pull/11944), [#11746](https://github.com/vimeo/psalm/pull/11746), [#12097](https://github.com/vimeo/psalm/pull/12097), [#12099](https://github.com/vimeo/psalm/pull/12099), [#12468](https://github.com/vimeo/psalm/pull/12468)).
- **New sources:** `php://input`, `STDIN`, the client-sent entries of `$_SERVER` and `$_FILES`, `getopt()`, `$argv`, DNS answers, exception messages, and the files, paths and URLs builtins read ([#11945](https://github.com/vimeo/psalm/pull/11945), [#12086](https://github.com/vimeo/psalm/pull/12086), [#12246](https://github.com/vimeo/psalm/pull/12246), [#12245](https://github.com/vimeo/psalm/pull/12245), [#12135](https://github.com/vimeo/psalm/pull/12135), [#12139](https://github.com/vimeo/psalm/pull/12139)).
- **Taint now flows through** closures and callable parameters, generators and iterators, by-reference parameters, `foreach` by reference, global and static variables, trait method bodies, promoted constructor properties, `__toString()`, inherited methods and their overrides, exceptions, and builtin functions and objects ([#12147](https://github.com/vimeo/psalm/pull/12147), [#12096](https://github.com/vimeo/psalm/pull/12096), [#12095](https://github.com/vimeo/psalm/pull/12095), [#12121](https://github.com/vimeo/psalm/pull/12121), [#12243](https://github.com/vimeo/psalm/pull/12243), [#12255](https://github.com/vimeo/psalm/pull/12255), [#12272](https://github.com/vimeo/psalm/pull/12272), [#12091](https://github.com/vimeo/psalm/pull/12091), [#12127](https://github.com/vimeo/psalm/pull/12127), [#12262](https://github.com/vimeo/psalm/pull/12262), [#12084](https://github.com/vimeo/psalm/pull/12084), [#12102](https://github.com/vimeo/psalm/pull/12102)).
- **Fewer false positives:** taints a type can't carry are removed (an `int` has no `html` taint). Array keys are tracked much more precisely. Inferred-pure functions are specialized per call, and `@psalm-cli-only` marks code whose output isn't an HTML response ([#12079](https://github.com/vimeo/psalm/pull/12079), [#12348](https://github.com/vimeo/psalm/pull/12348), [#12150](https://github.com/vimeo/psalm/pull/12150), [#12375](https://github.com/vimeo/psalm/pull/12375), [#12048](https://github.com/vimeo/psalm/pull/12048), [#12459](https://github.com/vimeo/psalm/pull/12459)).
- **Library stubs:** Psalm ships taint and purity stubs for Guzzle, PSR-7, PSR-18, psr/log, Monolog, mongodb/mongodb, amphp (http-client, byte-stream, file, process), phpClickHouse, PhpSpreadsheet, Archive_Tar, php-vips, webklex/php-imap and webmozart/assert. A stub is loaded only when your code uses the library ([`loadLibraryStubs`](https://psalm.dev/docs/running_psalm/configuration/#loadlibrarystubs), on by default) ([#12460](https://github.com/vimeo/psalm/pull/12460)–[#12467](https://github.com/vimeo/psalm/pull/12467), [#12481](https://github.com/vimeo/psalm/pull/12481)–[#12484](https://github.com/vimeo/psalm/pull/12484)).
- **More expressive annotations** ([docs](https://psalm.dev/docs/security_analysis/annotations/)):
  - conditional sinks: `@psalm-taint-sink ($mode is 0 ? null : 'html') $content` ([#12458](https://github.com/vimeo/psalm/pull/12458));
  - sinks on array values only: `@psalm-taint-sink sql $params[*]` ([#12171](https://github.com/vimeo/psalm/pull/12171));
  - sources under one key of the return value: `@psalm-taint-source input return['key']` ([#12455](https://github.com/vimeo/psalm/pull/12455));
  - taint complements: `~(html|has_quotes)` ([#12300](https://github.com/vimeo/psalm/pull/12300));
  - `@psalm-flow` through callables (`($compute()) -> return`), the receiver (`($this) -> return`), `$this` properties, and keyed stores (`($value) -> Store::$data[$key]`) ([#12299](https://github.com/vimeo/psalm/pull/12299), [#12302](https://github.com/vimeo/psalm/pull/12302), [#12456](https://github.com/vimeo/psalm/pull/12456), [#12312](https://github.com/vimeo/psalm/pull/12312), [#12327](https://github.com/vimeo/psalm/pull/12327));
  - `@psalm-taint-property-bag` ([#12486](https://github.com/vimeo/psalm/pull/12486));
  - custom taints declared in `psalm.xml` ([#12330](https://github.com/vimeo/psalm/pull/12330)).
- **Plugin API for taints:** `Codebase::getTaintNode()`, `getTaintPropertyNode()`, `addTaintPath()`, `getReceiverTaintPath()`, and the taint nodes of a call's return value, arguments and receiver ([#12454](https://github.com/vimeo/psalm/pull/12454), [#12414](https://github.com/vimeo/psalm/pull/12414), [#12489](https://github.com/vimeo/psalm/pull/12489), [#12535](https://github.com/vimeo/psalm/pull/12535)).

## A new purity model: capabilities

`@psalm-pure`, `@psalm-mutation-free` and `@psalm-external-mutation-free` are replaced by **capabilities**, modelled on [Hack's contexts and capabilities](https://docs.hhvm.com/hack/contexts-and-capabilities/introduction). A function-like declares exactly which side effects it may have: `read-props`, `write-this-props`, `write-props`, `read-globals`, `write-globals`, `write-refs`, `io` and `time`, with `pure` and `impure` at the two ends ([#11984](https://github.com/vimeo/psalm/pull/11984)):

```php
<?php
/** @psalm-capabilities time */
function today(): string {
    return date('Y-m-d'); // no issue
}

/** @psalm-pure */
function todayWrong(): string {
    return date('Y-m-d'); // ImpureFunctionCall: ... requires time
}
```

**[Try it on psalm.dev »](https://psalm.dev/r/be7eb39420)**

- Psalm **infers purity across the call graph**. Where an annotation could be added, it can suggest one: set `<psalm suggestPurityAnnotations="true">` to get `MissingPureAnnotation` and `MissingImmutableAnnotation` (and their abstract and interface variants), and run `--alter --issues=MissingPureAnnotation,MissingImmutableAnnotation` to add the annotations. The `--alter` command works without the attribute. The suggestions are opt-in.
- Pure and read-only functions are **taint-specialized automatically**, so annotating your code also removes false positives from security analysis.
- Builtins, SPL, the Random, phpredis and MongoDB extensions and more declare what they do.
- `clone`, `__toString()`, `__invoke()`, `ArrayAccess`, destructors, `new $class` and parameter defaults are now checked too.
- `@psalm-pure` can be used on classes, and plugins can declare the capabilities of the methods they handle.

The new [Purity Model](https://psalm.dev/docs/annotating_code/purity_model/) article explains the model in full.

## Purity templates

Many functions are only as pure as what they are given: `array_map()` is pure when its callback is. **Purity templates** are template parameters whose values are capability sets instead of types, like Hack's `[ctx $f]` contexts. A function can now be both pure and higher-order, and each call costs only what its arguments do ([#11984](https://github.com/vimeo/psalm/pull/11984)).

With `Closure[_]`, a function takes its purity from the closure it is given:

```php
<?php
/**
 * @psalm-pure
 * @param Closure[_](int): int $f
 */
function apply(Closure $f, int $x): int {
    return $f($x);
}

/** @psalm-pure */
function double(int $x): int {
    return apply(fn(int $y): int => $y * 2, $x); // no issue: the closure is pure
}

/** @psalm-pure */
function noisy(int $x): int {
    return apply(function (int $y): int { echo $y; return $y; }, $x); // ImpureFunctionCall: The context is pure but function call on apply requires io
}

/** @psalm-pure */
function lengths(array $words): array {
    return array_map(fn(string $w): int => strlen($w), $words); // no issue: builtins taking callbacks work the same way
}
```

On a class, a purity template lets each subclass decide what its methods may do:

```php
<?php
/** @psalm-purity-template P */
abstract class Handler {
    /**
     * @psalm-pure
     * @psalm-purity-from-template P
     */
    abstract public function handle(string $input): string;
}

/** @extends Handler[pure] */
final class Upper extends Handler {
    /** @psalm-pure */
    #[Override]
    public function handle(string $input): string {
        return strtoupper($input);
    }
}

/** @extends Handler[io] */
final class Printer extends Handler {
    /** @psalm-capabilities io */
    #[Override]
    public function handle(string $input): string {
        echo $input;
        return $input;
    }
}

/** @psalm-pure */
function viaUpper(Upper $handler): string {
    return $handler->handle('a'); // no issue: Upper binds P to pure
}

/** @psalm-pure */
function viaPrinter(Printer $handler): string {
    return $handler->handle('a'); // ImpureMethodCall: The context is pure but method Printer::handle requires io
}
```

`Traversable`, `Iterator`, `IteratorAggregate` and `Generator` have a purity template, for what iterating over them does. `iterable` has one too. A generator with a purity annotation binds it to what its body does:

```php
<?php
/**
 * @psalm-pure
 * @param Traversable[pure]<int, int> $xs
 */
function sum(Traversable $xs): int {
    $total = 0;
    foreach ($xs as $x) { // no issue
        $total += $x;
    }
    return $total;
}

/**
 * @psalm-pure
 * @param Traversable<int, int> $xs
 */
function sumAny(Traversable $xs): int {
    $total = 0;
    foreach ($xs as $x) { // ImpureMethodCall: The context is pure but iterating over Traversable<int, int> requires impure
        $total += $x;
    }
    return $total;
}

/**
 * @psalm-pure
 * @return Generator<int, int>
 */
function numbers(): Generator {
    yield 1;
    yield 2;
}

/** @psalm-pure */
function total(): int {
    return sum(numbers()); // no issue: numbers() returns a Generator[pure]
}
```

- **Syntax:** purity arguments go in square brackets before the type arguments, as in Hack: `Closure[io](int): void`, `Handler[pure]`, `Iterator[pure]<int, string>`, `Box[io]<int>`. Named templates are declared with `@psalm-purity-template P` and used with `@psalm-purity-from-template P`.
- **Bounds and defaults** are written as a chain: `@psalm-purity-template write-this-props <= C(write-this-props) <= write-this-props|write-props|io`. A method may use the lower bound unconditionally. Subclasses must stay within the bounds, and get the default when they don't bind the template.
- **Constructors** can bind a class template from their arguments, with a `Closure[P]` parameter or a conditional `@return` on `__construct`. For example, `new ArrayObject([...])` is an `ArrayObject[pure]`, so a pure function may fill an `ArrayObject` it created ([#12116](https://github.com/vimeo/psalm/pull/12116), [#12179](https://github.com/vimeo/psalm/pull/12179), [#12190](https://github.com/vimeo/psalm/pull/12190)).
- Purity templates are checked across **overrides, traits and closures** nested in a templated scope ([#12015](https://github.com/vimeo/psalm/pull/12015), [#12126](https://github.com/vimeo/psalm/pull/12126), [#12027](https://github.com/vimeo/psalm/pull/12027)).
- **Inferred too:** Psalm infers the purity of calls to templated functions, and `--alter` adds `Closure[_]` to the closure parameters a function calls ([#12077](https://github.com/vimeo/psalm/pull/12077), [#12019](https://github.com/vimeo/psalm/pull/12019)).
- **Builtins and SPL** taking callbacks or iterators are declared with purity templates: `array_map`, `usort`, `preg_replace_callback`, `iterator_to_array`, `CallbackFilterIterator`, `Fiber` and more ([#12122](https://github.com/vimeo/psalm/pull/12122)).

See [Purity templates](https://psalm.dev/docs/annotating_code/purity_model/#purity-templates) in the Purity Model article.

## Graph-based dead code detection

Unused code is now found on a graph of references ([#11939](https://github.com/vimeo/psalm/pull/11939)). Code is used only if it can be reached from an entry point, so classes and methods referenced only by other unused code, cycles included, are reported. Exceptions that are created but never thrown are reported too ([#12487](https://github.com/vimeo/psalm/pull/12487)):

```php
<?php
final class Service {
    public function run(int $id): void {
        if ($id < 0) {
            new InvalidArgumentException('negative id'); // UnusedMethodCall: ... never thrown or used
        }
        echo $id;
    }
}

final class Ping {                                     // UnusedClass
    public static function ping(): void { Pong::pong(); }
}

final class Pong {                                     // UnusedClass
    public static function pong(): void { Ping::ping(); }
}

(new Service())->run(1);
```

**[Try it on psalm.dev »](https://psalm.dev/r/31c62c069b)**

`--find-unused-psalm-suppress` now also checks suppressions on classes and on `Tainted*` issues.

## Type system

- **Template defaults:** a template can declare a default type, `@template T = Default` or `@template T of Bound = Default`, on classes, interfaces, traits, functions and methods. Psalm uses it when the type argument is left out: in `@extends`/`@implements`/`@use`, in a generic type with only its first arguments (`Result<int>`), in `new`, and in calls that don't infer the template. Libraries can add a template without breaking every `Foo<A>` their users wrote, and those users no longer get `MissingTemplateParam`. See [Template defaults](https://psalm.dev/docs/annotating_code/templated_annotations/#template-defaults).

  ```php
  <?php
  /**
   * @template TValue
   * @template TError of Throwable = RuntimeException
   */
  final class Result {
      /** @var TError|null */
      public ?Throwable $error = null;
  }

  /** @param Result<int> $result */
  function handle(Result $result): void {
      $error = $result->error; // RuntimeException|null (Psalm 6: MissingTemplateParam, Throwable|null)
  }

  /** @template T */
  interface Option {
      /**
       * @template TResult = T
       * @param (callable(T): TResult)|null $map
       * @return Option<TResult>
       */
      public function map(?callable $map = null): Option;
  }

  /** @param Option<int> $option */
  function check(Option $option): void {
      $same = $option->map(); // Option<int> (Psalm 6: Option<never>)
  }
  ```

  **[Try it on psalm.dev »](https://psalm.dev/r/4e21eb262e)**

- New types and syntax: `decimal-int-string` and `non-decimal-int-string`, global constant wildcards, conditional types on types that depend on a template, `is not` in conditional types, and exact intersections of scalar and conditional types.
- Constructor `@return` types.
- New [`IntMaskComparison`](https://psalm.dev/docs/running_psalm/issues/IntMaskComparison/) issue for int-masks compared by value.

## PHP 8.5 and 8.6

- Psalm runs on PHP 8.6 and has a PHP 8.6 call map.
- The PHP 8.5 pipe operator and PHP 8.6 partial function application are fully typed: Psalm infers the type of every pipeline stage and the signature of every partially applied closure, so a stage that doesn't fit the previous one is reported :

  ```php
  <?php
  function slug(string $title): string {
      return $title
          |> trim(...)
          |> strtolower(...)
          |> str_replace(' ', '-', ?); // no issue: each stage is typed
  }

  function lowercaseWords(string $title): string {
      return $title |> explode(' ', ?) |> strtolower(...); // InvalidArgument: Argument 1 of strtolower expects string, but non-empty-list<string> provided
  }

  function wrongLength(string $title): string {
      return $title |> trim(...) |> strlen(...); // InvalidReturnStatement: 'int<0, max>' does not match 'string'
  }
  ```

  **[Try it on psalm.dev »](https://psalm.dev/r/3c08a570e0)**

## Plugins

- **Versioned plugin API:** Psalm's version number no longer tracks the plugin API. **Breaking changes to the plugin API may ship in any Psalm release, including minor and patch releases.** The [psalm/psalm-plugin-api](https://packagist.org/packages/psalm/psalm-plugin-api) metapackage tracks them instead ([#11739](https://github.com/vimeo/psalm/pull/11739)). Its major version is bumped for every breaking change, and its readme lists the changes. Plugins should require it with a caret range (`^0.4`): Composer will then only install the plugin with a Psalm release whose plugin API it was written for.
- **Bundled plugins:** Psalm requires `psalm/plugin-phpunit`, `plugin-mockery`, `plugin-symfony`, `plugin-laravel` and `plugin-sabre-event`, and enables each one, plus the webmozart/assert plugin, when your `composer.json` uses the matching library ([`loadLibraryPlugins`](https://psalm.dev/docs/running_psalm/configuration/#loadlibraryplugins), on by default) ([#12568](https://github.com/vimeo/psalm/pull/12568), [#12570](https://github.com/vimeo/psalm/pull/12570), [#12571](https://github.com/vimeo/psalm/pull/12571)).
- Plugin issues can have custom documentation URLs, and respect `ERROR_LEVEL` ([#11735](https://github.com/vimeo/psalm/pull/11735), [#11736](https://github.com/vimeo/psalm/pull/11736)).

## CLI and configuration

- `--init-ci` generates a GitHub Actions workflow that runs the official Docker image, pinned to the Psalm version in your `composer.lock`. The docs now cover the [Psalm GitHub Action](https://github.com/psalm/psalm-github-actions) and the [Psalm Security Scan](https://github.com/psalm/psalm-github-security-scan) action, which sends security analysis results to GitHub code scanning.
- The official Docker image is now `ghcr.io/psalm/psalm`.
- The old `compact` output format is renamed `table`, and a new, truly compact `compact` format is added.
- `<unsuppressibleIssues>` makes the issues it lists impossible to suppress.
- Faster startup: less scanning, and single-file scans no longer build the full project file list. Composer's autoloader is no longer deep-scanned.
- More detailed progress output, including for taint graph resolution, and short, actionable CLI messages.

## Upgrading

For most projects, upgrading is as simple as updating the Composer dependency:

```bash
composer require --dev vimeo/psalm:^7.0 -W
```

Everything else is in the [upgrade guide](https://psalm.dev/docs/running_psalm/upgrading_to_psalm_7/).

Most projects will notice:

- **Taint analysis runs by default.** Set `runTaintAnalysis="false"` to opt out.
- If enabled via `<psalm suggestPurityAnnotations="true">`, **new `MissingPureAnnotation`/`MissingImmutableAnnotation` issues** appear for code that can be annotated. Fix them with `--alter --issues=MissingPureAnnotation,MissingImmutableAnnotation`. The annotations Psalm adds improve both security analysis and type inference:
  - **Fewer security false positives:** Psalm specializes the taints of calls to pure and read-only functions and methods per call site. Tainted input passed at one call then no longer taints the result of every other call. Without annotations, Psalm can only infer this for functions and methods that can't be overridden.
  - **Better type inference:** Psalm remembers the results of methods of immutable classes between calls, so `$a->get() !== null ? $a->get() : ''` is a `string`. Without `@psalm-immutable`, the second call is still `?string`.
  - **Checked contracts:** pure code can call the annotated functions, and every override and implementation is checked not to do more than the annotation allows.

## Thanks

Thanks to everyone who contributed to Psalm 7, and to everyone who tested the betas and release candidates and reported issues!

**Full Changelog**: [6.20.3...7.0.0](https://github.com/vimeo/psalm/compare/6.20.3...7.0.0)
