<!--
  title: Uncovering PHP bugs with @template
  canonical: https://medium.com/@muglug/uncovering-php-bugs-with-template-a4ca46eb9aeb
  date: 2019-01-30
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

This guide discusses functionality available in two PHP static analysis tools: [Psalm](https://github.com/vimeo/psalm) (from Vimeo), and [Phan](https://github.com/phan/phan). PHPStan [has plans to support](https://github.com/phpstan/phpstan/pull/1692) templated types in an upcoming version.

* * *

At a former job I wrote a load of C# code, and I really enjoyed playing around with generic classes. You could write code like…

```
class LazyList<T> : IEnumerable<T> { ... }

...

var list = new LazyList<Foo>(ids);  
foreach (var value in list) {}
```

…and know that `value` inside the loop has the type `Foo`. Having that guarantee (and knowing it would be enforced by the type checker) left you free to worry about Everything Else.

If you’ve written [C#](https://docs.microsoft.com/en-us/dotnet/csharp/programming-guide/generics/generic-type-parameters), [Java](https://docs.oracle.com/javase/tutorial/java/generics/bounded.html), [TypeScript](https://www.typescriptlang.org/docs/handbook/generics.html), or [any](https://docs.hhvm.com/hack/generics/introduction) [number](https://en.cppreference.com/w/cpp/language/template_parameters) of [other](https://docs.swift.org/swift-book/LanguageGuide/Generics.html) [languages](https://doc.rust-lang.org/rust-by-example/generics.html), this feature—generic type parameters — may be familiar. It enables you to write very simple equations, in the form of type annotations, that a type checker will solve to produce useful type information.

When I arrived at Vimeo (and started using PHP), I left those syntactic niceties behind. PHP lacks type parameters, so the equivalent of the above code looks something like this:

```
class LazyList extends \ArrayObject { ... }

...

$list = new LazyList(Foo::class, $ids);foreach ($list as $value) {}
```

That code might make sense to a human (and work at runtime), but it leaves the type checker in the lurch. Type checkers like Psalm rely on [PhpDoc annotations](https://docs.phpdoc.org/glossary.html#term-docblock) to provide information that the language can’t, but there’s no standardized annotation you can give `LazyList` that will tell you what type `$value` has.

At this point developers normally pick one of three options:

*   Do nothing, and have the type checker treat `$value` as a `mixed` type. If you haven’t come across `mixed` before, it basically means a mystery type.
*   Prefix the foreach with a `/** @var Foo $value */` docblock. Doing that for every such loop adds plenty of boilerplate, and it’s fairly brittle — if you change `$list`, you must also change the `@var` docblock to match.
*   Add an explicit (and unnecessary) `$value instanceof Foo` check inside the loop.

Our codebase mostly used the first approach, but the `mixed` type reduced our type checker’s ability to find bugs¹. For example, there were a few places where we’d iterate over `$list` and call `$value->methodWithTypo()` inside the loop. Since `$value` was typed to `mixed` our type checker could not see the bug.

We needed a way to tell the type checker exactly how `LazyList` behaves. After going through a number of alternative docblock tags, I settled on one already used for the purpose by Phan — `@template` — and added some additional behavior (since adopted by that type checker too).

With `@template` and a few other annotations I’ll explain we fill out the above class’s definition:

```php
<?php

/**
 * @template T
 * @extends \ArrayObject<int, T>
 */
class LazyList extends \ArrayObject
{
  /**
   * @var class-string<T>
   */
  public $type;

  /**
   * @param class-string<T> $type
   */
  public function __construct(string $type, array $ids)
  {
    $this->type = $type;
    // more
  }

  // more
}
```

Here’s what the different annotations mean:

*   `@template T`   
    This tells Psalm that any docblock reference to `T` inside the class should be treated as a type parameter. It’s directly equivalent to writing class `LazyList<T> {…}` in a language whose syntax supports type parameters.
*   `@extends \ArrayObject<int, T>`  
    This `@extends` annotation says that `ArrayObject`’s [templated types](https://github.com/vimeo/psalm/blob/00e95cbd6b94f3562d54201a3b9ceb4131a44352/src/Psalm/Internal/Stubs/CoreGenericClasses.php#L200-L205) `TKey` and `TValue` are now bound to `int` and `T`, respectively, meaning that any inherited methods of `ArrayObject` require you to pass in the correct arguments (e.g. `$list->offsetSet(5, new Foo())`), with a type checker error emitted if the arguments are incorrect.
*   `@var class-string<T>`, `@param class-string<T> $type`  
    We’ve introduced the `class-string` type to describe strings that are also fully qualified class names — such as `Foo::class `— and we use the more restrictive type `class-string<Foo>` to denote strings that are either a class name of Foo (`Foo::class`) or a class name of a subclass of `Foo` (like `FooChild::class`). If you’ve encountered [Hack](https://hacklang.org), this approach might be familiar — they use `classname<Foo>`, but it’s the same idea.  
    **Note**: if your IDE is unhappy with these foreign annotations, you can use `@psalm-var`/`@psalm-param` instead.

By adding that single `@template` tag to `LazyList`, we help the type checker understand the use of that object in hundreds of places throughout our codebase.

We use `@template` tags in a bunch of other scenarios:

*   They’re used by Psalm to describe the output of [PHP’s array functions](https://github.com/vimeo/psalm/blob/00e95cbd6b94f3562d54201a3b9ceb4131a44352/src/Psalm/Internal/Stubs/CoreGenericFunctions.php) and [DOM methods](https://github.com/vimeo/psalm/blob/00e95cbd6b94f3562d54201a3b9ceb4131a44352/src/Psalm/Internal/Stubs/CoreGenericClasses.php#L694-L741)
*   They’re combined with intersection types to [describe the results of PHPUnit mock calls](https://github.com/psalm/phpunit-psalm-plugin/blob/65b5b19c951fab0df9e7db3e3a509d7a82f433d3/stubs/TestCase.php#L10-L15).
*   They’re used by Vimeo’s ORM to type the results of `PDOStatement::fetch` calls after setting the fetch mode to `PDO::FETCH_CLASS`.

In each case we’re adding information that the typechecker didn’t know previously, meaning fewer false positives and more bugs found.

In general, `@template` can be used in most places that you’d use a templated/type parameter in one of the languages mentioned above.

### Going Deeper

Since adding initial support for `@template`, a number of people have requested more comprehensive templating rules that are present in other languages. The following features are available in more recent releases of Psalm.

#### Template type constraints (Psalm-only)

What if we want to restrict the creation of `LazyList` instances only to class strings corresponding to a certain type?

We want a situation in which `LazyList(Foo::class ...)` is permitted, as is `LazyList(SubclassOfFoo::class ...)`, but not `new LazyList(Bar::class ...)`.

We can accomplish this by appending `of SomeClassOrInterface` to our tag:

```
/**
 * @template T of Foo
 */
class LazyList extends \ArrayObject {...}
```

Phan has plans for [a similar feature](https://github.com/phan/phan/issues/1666).

#### Combining with @psalm-assert/@phan-assert

Psalm and Phan support assertions in function docblocks in the form of `@psalm-assert`/`@phan-assert`. You can use this annotation to write your own templated `assertInstanceOf` checks like so:

```php
<?php
/**
 * @template T
 *
 * @param class-string<T> $class
 *
 * @psalm-assert T $input
 * @phan-assert T $input
 */
function assertInstanceOf(string $class, object $input) : void {
  if (!is_a($input, $class)) {
    throw new \UnexpectedValueException('Bad');
  }
}
```

#### Combining with `@param-out (Psalm-only)`

Psalm now supports `out` params, in the form of a `@param-out` declaration.

This means we can type check the function `[usort](http://php.net/manual/en/function.usort.php)` without changing Psalm’s internals — we just [define usort using template types](https://github.com/vimeo/psalm/blob/1c17d2e2f299a3c680a25ff8055aa144b1047c4f/src/Psalm/Internal/Stubs/CoreGenericFunctions.php#L131):

```
/**
 * @template T
 *
 * @param array<mixed, T> $arr
 * @param callable(T, T):int $callback
 *
 * @param-out array<int, T> $arr
 */
function usort(array &$arr, callable $callback): bool {}
```

Now Psalm detects that the following is an error:

```php
<?php

$a = [[1], [2], [3]];
usort($a, "strcmp");
```

#### Going wild with templates

We can do some weird and wonderful things with the power of templated types.

In the following code, the first parameter of the function `foo` expects a closure that itself takes a closure and returns whatever type that inner closure returns. We then call it with a closure whose inner closure returns a different type than the one it returns. Whether or not you understood that, [Psalm sees the bug](https://getpsalm.org/r/6330e878f6).

```php
<?php

/**
 * @template T
 * @param Closure(Closure():T):T $s
 */
function foo(Closure $s) : void {}

foo(
  /** @param Closure():int $s */
  function(Closure $s) : string {
    return '#' . $s();
  }
);
```

### Towards a future full of types

At Vimeo we use a lot of TypeScript, and our developers love it. While part of TypeScript’s success lies in its excellent type checker, I’d argue that its real breakthrough has been the way it enables developers, through its expansive type system, to eloquently describe how their code operates.

I want PHP to have a similarly expressive type system, and, with the addition of `@template`, I hope developers will write not just type-safe code, but better structured, more logical code.

If you have ideas for other advanced typing features, [please open a ticket](https://github.com/vimeo/psalm/issues)!

* * *

¹ Vimeo’s own Psalm config now prevents developers from adding untyped PHP code. Existing issues have been grandfathered in via [Psalm’s baseline functionality](https://getpsalm.org/docs/dealing_with_code_issues/#using-a-baseline-file).
