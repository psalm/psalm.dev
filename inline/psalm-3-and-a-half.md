It’s been over seven months since version 3.0 of [Psalm](https://psalm.dev) was released. There have been over a thousand commits to Psalm since then, bringing all sorts of fun features. Time for an update!

This blog post includes interactive code snippets—feel free to edit and explore Psalm further!

### Preamble: What is Psalm?

Psalm has a number of different components, built with the goal of helping you find and fix bugs in PHP code.

Psalm’s central component is a type analysis engine that reads in PHP files and infers types for expressions, identifying potential bugs along the way. Today that type analysis engine powers a number of additional features:

*   Unused code detection — figuring out which variables, properties, methods and classes aren’t used.
*   [IDE support](https://psalm.dev/docs/language_server/) — identifying bugs and providing suggestions as you type.
*   [Automated fixes](https://psalm.dev/docs/fixing_code/) for some of the issues Psalm finds, and for moving (and renaming) classes and methods.
*   A [plugin system](https://psalm.dev/docs/authoring_plugins/) that both consumes, and can help inform, Psalm’s type analysis engine.

The updates since 3.0 fall into three categories: [type system improvements](#type-system-improvements), [improved ecosystem compatibility](#improved-ecosystem-compatibility) and [tooling improvements](#tooling-improvements).

Lastly I talk about an upcoming security-related feature: [taint analysis](#taint-analysis).

----

## Type system improvements

JavaScript and PHP share a bunch of common paradigms, and developers at web-oriented companies often switch between the two. I and others at Vimeo love the thoughtful type system that TypeScript’s authors have created. It helps make sense of JavaScript paradigms, and I want Psalm to be able to offer the same for PHP.

### Improved support for @template (generic annotations)

A few months ago [I wrote an article](https://medium.com/vimeo-engineering-blog/uncovering-php-bugs-with-template-a4ca46eb9aeb) about Psalm’s support for `@template`. That article got a lot of attention, but, more importantly, it got a lot of people to add `@template` types to their own codebases, and I’ve fixed over 100 template-related issues over on github.com.

I’ve added [a brief introduction to templates](https://psalm.dev/docs/annotating_code/templated_annotations/) to Psalm’s documentation to help you get started!

### Enum-like types

Psalm now supports `key-of<...>` and `value-of<...>`. These types enable you to restrict expected input and output based on class constant arrays:

```php
<?php

class Airports {  
  const JFK = 'jfk';  
  const LGA = 'lga';  
  const EWR = 'ewr';  

  const ALL = [  
    self::JFK => 'John F. Kennedy Airport',  
    self::LGA => 'La Guardia Airport',  
    self::EWR => 'Newark Liberty International',  
  ];  

  /**  
   * @psalm-param key-of<self::ALL> $code  
   */  
  public static function getName(string $code) : string {  
    return self::ALL[$code];  
  }  
}

Airports::getName(Airports::JFK); // good  
Airports::getName('lga'); // good  
Airports::getName('sfo'); // type error
```

When it’s analysing the code above, Psalm converts `key-of<self::ALL>` into a [union type](https://psalm.dev/docs/annotating_code/type_syntax/union_types) of `'jfk'|'lga'|'sfo'`, enabling it to complain when passed `'sfo'`.

This can also be used with templated arrays—`key-of<T>` where `T` is defined by `@template T as array`.

### Annotating functions that exit or throw

Some functions are designed to halt execution (either by throwing an exception, or by calling `exit()`). We might call them in our codebase and write code as if they stop the program flow, but our static analysis tool doesn’t know it:

```php
<?php

function redirect() : void {  
  header('Location: https://vimeo.com;');
  exit();  
}

function maybeRedirect(bool $some_condition) : void {  
  if ($some_condition) {  
    $i = 5;  
  } else {  
    redirect();  
  }

  echo $i; // Possibly undefined variable $i  
}
```

To solve this problem (without having to totally rewrite the code) we can add a `never-return` return type annotation to `redirect()`:

```php
<?php

/**  
 * @psalm-return never-return  
 */  
function redirect() : void {  
  header('Location: https://vimeo.com');
  exit();  
}

function maybeRedirect(bool $some_condition) : void {  
  if ($some_condition) {  
    $i = 5;  
  } else {  
    redirect();  
  }

  echo $i; // No issue
}
```

With that annotation Psalm understands that `$i` is always defined when it’s used. This feature is stolen from [Hack](https://hacklang.org), which has a comparable `noreturn` type.

### By-reference parameter output

Variables that are passed by reference can be hard for static analysis tools to reason about.

```php
<?php

function changeToClass(string &$s) : void {  
  $s = new \stdClass(); // typechecker error  
}

$a = "hello";  
bar($a);  
echo strlen($a); // runtime error, no typechecker error
```

Psalm’s existing handling of this pattern was to complain inside the function `bar()` that you were violating the type constraint that `$s` should be a string.

You can now use a `@param-out` annotation to tell Psalm that you _intend_ for the function to behave that way:

```php
<?php

/**  
 * @param-out \stdClass $s  
 */  
function changeToClass(string &$s) : void {  
  $s = new \stdClass(); // no error  
}

$a = "hello";  
bar($a);  
echo strlen($a); // typechecker error
```

### class-string and class-string\<SomeClass\>

Psalm now has an annotation to describe strings that represent class names. `class-string` is the simple version—a param typed with `class-string` will accept any `::class` constant, `__CLASS__`, or the output of `get_class($someObject)`.

`class-string<SomeClass>` is more specific—it will only accept class-strings that represent the object in question, or one of its descendants. That means we can write a certain sort of code that Psalm can verify:

```php
<?php

class A {  
  public function foo() : void {}  
}

class AChild extends A {}

/**  
 * @psalm-param class-string<A> $s  
 */  
function makeA(string $s) : A {  
  $a = new $s();  
  $a->foo();  
  $a->bar(); // error  
  return $a;  
}

makeA(A::class);
makeA(AChild::class);
makeA(Exception::class); // error
```

The `class-string` annotation [can also be parameterised with templated types](https://psalm.dev/docs/annotating_code/templated_annotations/#param-class-string-t).

----

## Improved ecosystem compatibility

Version 3.0 launched with a new plugin infrastructure that supports custom stub files. Stub files enable you to provide alternative docblocks for Psalm to consume (replacing those provided by a given package’s maintainer). Those plugin stub files include support for [custom assertion annotations](https://psalm.dev/docs/annotating_code/adding_assertions/) and generic types that help Psalm understand third-party packages.

Recently some of those third-party packages have started to incorporate Psalm assertions and `@template` annotations into their own codebases, removing the need for a separate plugin to understand the codebase.

[PHPUnit](https://github.com/sebastianbergmann/phpunit) recently added [Psalm assertion annotations](https://psalm.dev/docs/annotating_code/adding_assertions/) to its `TestCase::assertXXX` methods, and Doctrine has added `@template` annotations to its [collections framework](https://github.com/doctrine/collections). There’s also an effort underway to bring Psalm assertions to [webmozart/assert](https://github.com/webmozart/assert).

----

## Tooling improvements

Psalm finds bugs in code, but there’s a lot more that it can do with its knowledge of any given codebase.

### Improved UX

The latest version of Psalm now comes with a progress bar, created by [Ilijia Tovilo](https://github.com/iluuu1994) with help from [Bruce Weirdan](https://github.com/weirdan) and [Tyson Andre](https://github.com/TysonAndre). It can be optionally disabled with `--no-progress`, and doesn’t show up when running as part of a CI pipeline (Travis, Jenkins, CircleCI etc.).

### Fixing bugs

A year and a half ago, Psalm introduced Psalter, a tool that enables you to fix some of the errors Psalm finds. It now has a few extra features:

#### Unused method and property removal

Psalm can now automatically remove unused methods and properties:

```php
<?php // fix

class Queue {
  public function clear() : void {}
  public function clearLegacy() : void {}
}

(new Queue())->clear();
```

#### Missing param type suggestions

I’ve given Psalter a new skill—the ability to add missing param types to methods based on how they’re used in your codebase, so if you only call a method with a string as its first argument, Psalm adds a `@param string $someParamName` to that method’s docblock.

```php
<?php // fix

class A {
  public function takesString($s) : void {
    echo $s;
  }
}

(new A)->takesString("hello");
```

#### Automatic removal of unused variables

Psalm can now remove unused assignments, along with the useless expressions that follow them. Here, the entire line beginning `$a = ...` is removed:

```php
<?php // fix

function foo() : void {
    $a = substr("wonderful", 2);
    $b = "hello";
    echo $b;
}
```

### Refactoring code

Sometimes you don’t just want to fix code—you want to embark on a wholesale refactor.

There’s a fantastic paid tool (PHPStorm) that provides a great UI for refactoring, and a couple of dedicated open-source solutions.

None of those tools quite works with code annotated in the comprehensive style Psalm encourages, so [I’ve added that functionality to Psalm](https://psalm.dev/docs/manipulating_code/refactoring/).

### Improving IDE integration with autocomplete

PHPStorm is a fantastic IDE, and it’s used by a number of Vimeo developers, but I never really got into the habit of using it, instead preferring Sublime Text. Sublime is a fast, configurable IDE, but it doesn’t have much language-specific functionality by default.

It’s possible to add back some of the features you lose by connecting to a language server (a daemon that any IDE can query to fetch information about the code you’re writing). Two years ago I started working on a language server for Psalm, and it got released officially as part of Psalm 3.0 in November.

That language server can now autocomplete class names, method names, and properties, and it also provides assistance for method and function signatures, leveraging Psalm’s knowledge of your codebase (mostly thanks to superb contributions from [Josh Di Fabio](https://github.com/joshdifabio) and [Ilijia Tovilo](https://github.com/iluuu1994)).

----

## Future work

Whenever I think work on Psalm is basically done, something pops up that spurs new insights.

There’s tons of great work being done to improve static analysis tools outside the PHP ecosystem—at Facebook work continues on [Hack](https://hacklang.org) and [Pyre](https://pyre-check.org/) (their Python static analysis tool), and Stripe just released [Sorbet](https://sorbet.org/) to the world.

Pyre is especially interesting, as it includes an open-source implementation of the internal taint analysis system that Facebook has developed around Hack.

### Taint analysis

Taint analysis is a cousin of type analysis—instead of keeping track a variable’s type, we record whether it’s “tainted”—if the contents of the variable could be controlled by an attacker, and could leak somehow into operations that assume their inputs are well-formed.

Taint analysis can typically follow the taintedness of a given variable across function boundaries, and can mark a variable as safe if it passes through particular configured functions. Taint analysis relies on sources and sinks: sources are things that could be controlled by an attacker like `$id = $_GET['id']`, and sinks are places where we don’t want tainted data (such as `<div id="t_<?= $id ?>">`).

The basic mechanics of Psalm’s implementation are working, and it has already discovered a few XSS vulnerabilities in Vimeo’s codebase, but there’s still more to do. I’m hoping to release the feature properly in late September or early October. [Follow Psalm on Twitter](https://twitter.com/psalmphp) for updates!

For now, you can play with a toy example:

```php
<?php

class A {
  public string $userId;

  public function __construct() {
    $this->userId = (string) $_GET["user_id"];
  }

  public function getAppendedUserId() : string {
    return "aaaa" . $this->userId;
  }
}

class B {
  public function doDelete(A $a, PDO $pdo) : void {
    $userId = $a->getAppendedUserId();
    $this->deleteUser($pdo, $userId);
  }

  public function deleteUser(PDO $pdo, string $userId) : void {
    $pdo->exec("delete from users where user_id = " . $userId);
  }
}
```

----

## Hear me speak!

I'll be speaking at the upcoming [International PHP Conference](https://phpconference.com/testing-quality/php-or-type-safety-pick-any-two/) in Munich, Germany in late October. If you're around, come and say hi!


