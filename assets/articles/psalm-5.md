<!--
  title: Announcing Psalm 5
  date: 2022-11-30 08:30:00
  author: The Maintainers of Psalm
-->

Read this announcement in [Ukrainian](/articles/psalm-5-uk), [French](/articles/psalm-5-fr) or [Italian](/articles/psalm-5-it).

---

We all wish we could go back in time, whether it’s to right some historic wrong, tell a loved one how much they meant to us, or to correct a minor architectural decision in a PHP static analysis tool.

Sadly time machines do not exist, but major version changes do. The biggest user-facing change in Psalm 5 is a relatively minor fix: array shapes are now considered sealed by default.

## Sealed vs unsealed array shapes

If you’ve used array shapes in the past, you should be familiar with the basic syntax:

```php
<?php

/**
 * @param array{id: string, name: string} $user
 * @return array{id: string, name: string}
 */
function takesUserData(array $user): array {
  return $user;
}
```

In the above example `takesUserData` only accepts an array with exactly two elements, `id` and `name`. The function’s docblock tells us that it returns an array with exactly two elements as well.

Psalm also allows an `array{id: string, name: string}` shape to be passed into any function which expects an `array<string>`. That makes basic sense — the array just has elements of type `string`, so it should be fine to pass it into a function (like `implode`) that expects an array of strings.

What if we change our function to add another element in the body of `takesUserData`?

```php
<?php

/**
 * @param array{id: string, name: string} $user
 * @return array{id: string, name: string}
 */
function takesUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

Psalm now complains that we’re not returning what we said we would — a change from previous versions of Psalm, which allowed this behaviour.

The previous (broken behaviour) meant that we could do something like `implode('', takesUserData($foo))` without Psalm raising an error. That could lead to [code that breaks at runtime](https://3v4l.org/PoVil).

When a typechecker allows behaviour that leads to runtime problems, we call that typechecker *unsound*. There are a few corner-cases in PHP where unsound typechecking is unavoidable, but Psalm tries to steer clear of it wherever possible. We've decided to slightly alter Psalm's behaviour in a way we hope will cause the minimum amount of grief for Psalm's users.

A note from Matt Brown, Psalm’s creator:

> This is all my fault. Sorry. I came up with the `array{id: string, name: string}` convention but didn't nail down all the semantics.

As of this writing other PHP static analysis tools ([Phan](https://phan.github.io/demo/?code=%3C%3Fphp%0A%0A%2F**%0A+*+%40param+array%7Bid%3A+string%2C+name%3A+string%7D+%24user%0A+*+%40return+array%7Bid%3A+string%2C+name%3A+string%7D%0A+*%2F%0Afunction+takesUserData%28array+%24user%29%3A+array+%7B%0A++%24user%5B%27extra_data%27%5D+%3D+new+stdClass%28%29%3B%0A++return+%24user%3B%0A%7D%0A%0A%24foo+%3D+%5B%27id%27+%3D%3E+%27DP42%27%2C+%27name%27+%3D%3E+%27Douglas+Adams%27%5D%3B%0Aecho+implode%28%27%27%2C+takesUserData%28%24foo%29%29%3B) , [PHPStan](https://phpstan.org/r/4a61d13c-74f0-46d3-9bad-f3a61dd1d172)) allow that behaviour, and we hope that they, in time, will adopt the `...` convention too and remove the unsoundness from their handling of array shapes.


If you want a function to take shapes with more than the explicit number of fields, you can use `...` to denote an unsealed array shape:

```php
<?php

/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takesUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}
```

This mirrors the behaviour of `...` in [Hack code](https://docs.hhvm.com/hack/built-in-types/shape#open-and-closed-shapes).

Psalm will prevent the output of that `takesUserData` function (with an unsealed array shape) from being used in `implode` calls:

```php
<?php

/**
 * @param array{id: string, name: string, ...} $user
 * @return array{id: string, name: string, ...}
 */
function takesUserData(array $user): array {
  $user['extra_data'] = new stdClass();
  return $user;
}

$foo = ['id' => 'DP42', 'name' => 'Douglas Adams'];
echo implode('', takesUserData($foo));
```

What does this mean for you? Probably nothing! Psalm’s own codebase uses a lot of array shapes, and only one of them allows extra fields. We hope the impact from this update will be extremely small.

## What else is in Psalm 5?

We added the long awaited support for intersection types and other newer PHP 8 features.

Psalm 5 also adds some new types:

- [list{int, string, float}](https://psalm.dev/docs/annotating_code/type_syntax/array_types/#list-shapes)
- [properties-of<T>](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#properties-oft)
- [Variable templates](https://psalm.dev/docs/annotating_code/type_syntax/utility_types/#variable-templates)
- [int-range<x, y>](https://psalm.dev/docs/annotating_code/type_syntax/scalar_types/#int-range)

These types will help detect a lot more bugs, and fix a bunch of false-negatives by allowing you to describe your code more accurately.

Under the hood we’ve made some massive changes to Psalm internals. The whole type system is now immutable, which fixes an entire class of multithreaded bugs and **improves performance by 15-20%** both in single-threaded and multi-threaded mode (mainly by reducing use of `__clone`).

We’ve also dropped support for the legacy plugin API (introduced in Psalm 3) since the new one has been around for a couple of years.

Psalm is a big project with a lot to do — if you want to contribute, there is a lot [you can help us](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22Help+wanted%22) with, including a whole bunch of issues [for devs that don't know anything about Psalm’s internals](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22easy+problems%22)!

In the coming months we'll be working on full PHP 8.2 support, and more!
