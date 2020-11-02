<!--
  title: Psalm supports PHP 8 Attributes
  date: 2020-11-02 08:00:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

First, the headline: Psalm 4 now supports PHP 8’s attributes, with a bunch of new checks to make sure you’re using them correctly.

The implementation is fairly straightforward, and I hope it’ll be useful to a lot of you in the coming years.

PHP 8 Attributes are ideal for metadata that’s used in runtime reflection in performance-critical applications – [the RFC](https://wiki.php.net/rfc/attributes_v2) gives a number of great examples.

## Attributes for static analysis

It’s a little less obvious that attributes are appropriate for use with static analysis tools (where no runtime reflection is needed). It boils down to aesthetics: would you rather see something like functional purity annotated with PHP 8 Attributes:

```
use Psalm\Pure;

#[Pure]
function foo(int $i) : int {...} 
```

Or in the function’s docblock:

```
/**
 * @psalm-pure 
 */
function foo(int $i) : int {...} 
```

The PHP 8 version is definitely prettier, and your IDE may already provide completion of attributes (that it doesn’t provide for docblocks).

Advocates for using docblocks argue that developers have been using them to describe the behaviour of PHP functions and classes for many years, and there isn’t a pressing reason to stop now – especially since most PHP developers won’t be using PHP 8 in production code any time soon.

Recently the PhpStorm team announced their intention to use PHP 8 attributes [for new static-analysis-specific annotations](https://blog.jetbrains.com/phpstorm/2020/10/phpstorm-2020-3-eap-4/). While I don’t agree with that decision some of Psalm’s users probably do, and I don’t want Psalm to get in their way.

This week many are hoping that 2020 will be very different to 2016, but in one regard it’ll be mostly the same: then, as now, I’m making changes to Psalm so that PhpStorm users are happy!

## The Compromise of 2016
 
If you’re using Psalm today you’re benefiting from the months I spent in 2016 figuring out how to annotate Vimeo’s codebase so that Psalm could find as many bugs as possible.

Whenever I or one of my colleagues broke something in production, the first question I’d ask is “could Psalm have caught this”. Often the answer was “yes, if the codebase had been better-documented”.

One thing that we weren’t able to document properly was array shapes (aka object-like arrays). A lot of Vimeo’s functions passed around arrays assuming that particular keys existed on a given array:

```php
<?php

/**
 * @param array<string, string> $arr
 */
function takesKeyedArray(array $arr) : string {
    return $arr["a"];
}

echo takesKeyedArray(["a" => "hello"]); // works
echo takesKeyedArray(["b" => "hello"]); // silently breaks
```

This occasionally led to runtime bugs because we didn’t have a way to define those contracts. In many such places we _should_ have been using named objects to convey this information instead, but my aim was always to document how the code actually worked, and not change its behaviour.

Inspired by [Hack’s array shapes](https://docs.hhvm.com/hack/built-in-types/shapes) I added a way to annotate array shapes in Psalm via docblocks. They look like this:

```php
<?php

/**
 * @param array{a: string} $arr
 */
function takesKeyedArray(array $arr) : string {
    return $arr["a"];
}

echo takesKeyedArray(["a" => "hello"]);
echo takesKeyedArray(["b" => "hello"]); // type error
``` 

This addition helped us turn business logic errors into type errors, but it was a bit controversial internally. There I was, a relative newcomer to PHP, deciding that existing annotations (that the wider PHP community had agreed upon) weren’t up to the job. Even worse, this proposed annotation didn’t work in PhpStorm<sup>1</sup>.

As a compromise I introduced `@psalm`-namespaced annotations that could live alongside the PhpStorm-compatible ones:

```php
<?php

/**
 * @return array<string|int>
 * @psalm-return array{name: string, age: int}
 */
function getServer() {
  return ["name" => "us-east-1", "age" => 1420];
}
``` 

This middle-ground satisfied my colleagues, but more importantly it has meant that lots of people outside Vimeo have also been able to use Psalm’s [various types](https://psalm.dev/docs/annotating_code/type_syntax/atomic_types/) without upsetting PhpStorm.

Back then Psalm was only used by a handful of developers, and I never dreamt that PhpStorm would end up [adding support for a whole lot of @psalm-prefixed annotations](https://blog.jetbrains.com/phpstorm/2020/10/phpstorm-2020-3-eap-2/) four years later. There’s [even now a proposal](https://youtrack.jetbrains.com/issue/WI-56038) to add support for the array shape docblock syntax.

## The compromise of 2020

Most of PhpStorm's [newly-supported PHP 8 attributes](https://blog.jetbrains.com/phpstorm/2020/10/phpstorm-2020-3-eap-4/) have direct docblock equivalents in Psalm:

- `#[JetBrains\PhpStorm\Immutable]` => `@psalm-immutable`
- `#[JetBrains\PhpStorm\Pure]` => `@psalm-pure`
- `#[JetBrains\PhpStorm\Deprecated]` => `@deprecated`
- `#[JetBrains\PhpStorm\NoReturn]` => `@psalm-return no-return`

Though I’m slightly opposed to using attributes for static analysis, I still want Psalm to understand as much as reasonably possible in a given codebase, so using these PHP 8 attributes now results in the same behaviour as the equivalent docblocks:

```php
<?php

use JetBrains\PhpStorm\Immutable;

#[Immutable]
class Person {
    public function __construct(
        public string $first_name,
        public string $last_name,
        public int $age
    ) {}
}

$person = new Person("Jean-Luc", "Picard", 94);
$person->age = 59; // error is raised
```

### Not currently supported

The PhpStorm article also announces support for two other attributes: `ArrayShape` and `ExpectedValues`. I’m less enthusiastic about those – while most of the JetBrains-namespaced attributes are little more than boolean flags (pure vs not pure) the `ArrayShape` and `ExpectedValues` attributes can themselves store data, and I think that data is much easier to understand when it’s inside a docblock.

As an example, it's not clear (unless you understand how attributes can be scoped) which `array` the `ArrayShape` attribute refers to here – is it for the parameter type or the return type?

```
use JetBrains\PhpStorm\ArrayShape;

#[ArrayShape(["name" => "string", "age" => "int"])
function foo(array $arr) : array
```

The equivalent docblock annotation is obvious (and more succinct):

```
/** @return array{name: string, age: int} */
function foo(array $arr) : array
```

While I’m not adding support for `ArrayShape` or `ExpectedValues` at this time, I'll happily revisit the decision in the future if the community embraces the PhpStorm style for array shapes.

## One last thing

[I have created this PHP 8 Composer package](https://github.com/psalm/psalm-attributes) for anyone who’s an early PHP 8 adopter and also wants to use attributes for static analysis. All of the following attributes are now treated identically to the corresponding Psalm docblock annotations:

- `#[Psalm\Immutable]` is equivalent to `@psalm-immutable`
- `#[Psalm\Pure]` is equivalent to `@psalm-pure`
- `#[Psalm\Readonly]` is equivalent to `@readonly`
- `#[Psalm\Deprecated]` is equivalent to `@deprecated`
- `#[Psalm\Internal]` is equivalent to `@internal`

---

1. [PhpStorm](https://blog.jetbrains.com/phpstorm/) is a great piece of software that I’ve hardly ever used. I’m pretty sure that Psalm only exists because I was too lazy to install PhpStorm – my embarrassment at subsequent coding mistakes spurred Psalm’s creation.
