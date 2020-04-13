<!--
  title: Conditional love
  date: 2020-04-13 09:30:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
  notice: TL;DR: Psalm now supports conditional return types – [borrowed from TypeScript](https://www.typescriptlang.org/docs/handbook/advanced-types.html#conditional-types) – that enable you to describe functions that return different types of things depending on input – for example a function that sometimes returns a string, and sometimes returns an array.
-->

Before Psalm was open-sourced, I had to first get it working on Vimeo’s codebase. One of the early hurdles was picking a return type for the following method, used thousands of times in our business logic:

```
abstract class DatabaseModel {
  public static function fetch($id) {
    if (is_array($id)) {
      // returns array<static>
      return static::_fetchMultiple($id);
    }
    
    // returns static
    return static::_fetchSingle($id);  
  }
}
```

In the above code, the `fetch` method is really two methods in one, mimicking the behaviour of an [overloaded function](https://en.m.wikipedia.org/wiki/Function_overloading):

```
function fetch(int $id) : static;
function fetch(array $ids) : array;
```

Since PHP doesn’t actually support function overloading, a common alternative is to use separate methods with different signatures – e.g. `fetchSingle(int $id)` and `fetchMultiple(array $ids)` – but attempts to change this behaviour were met with resistance. The `fetch` method was a core part of our ORM, and Psalm couldn’t yet be trusted to spot new issues introduced by such a refactor.

Adding a `@return static|array<static>` was also a non-starter – doing so would have introduced thousands of false-positives for code like this:

```
$videos = UserModel::fetch(5)->getVideos();
                               ^^^^^^^^^
    Possibly invalid call getVideos on array<UserModel>
```

At the time the only solution to avoid all the false-positives was to use the type `@return mixed`, which meant Psalm was blind to a lot of very real bugs in the codebase.

---

A lot has changed since then – the aforementioned method has been split in two, and Psalm now [offers a plugin API](https://psalm.dev/docs/running_psalm/plugins/authoring_plugins/) that allows users to provide their own return types for specific functions, so this custom behaviour can easily be accounted for.

Plugins are really useful for some projects but, for simple cases like the one above, code should ideally carry enough information for a static analysis tool to do its job – and that’s where conditional return types come in!

## What’s a conditional return type?

A conditional return type is one whose realised value depends on a [template/generic parameter](https://psalm.dev/docs/annotating_code/templated_annotations/).

Like a number of other ideas In Psalm, the syntax for conditional types is [borrowed from TypeScript](https://www.typescriptlang.org/docs/handbook/advanced-types.html#conditional-types).

Where, in TypeScript, you’d write

```
abstract class DatabaseModel {
  fetch<T extends Number|Number[]>(id: T):
    T extends Number
    ? static
    : static[]
  {
    ...
  }
}
```

In PHP (with Psalm annotations) you’d write

```
abstract class DatabaseModel {
  /**
   * @template T of int|array<int>
   * @param T $id
   * @psalm-return (
   *     T is int
   *     ? static
   *     : array<static>
   * ) 
   */
  public static function fetch($id) {
    ...
  }
}
```

That’s a lot of code, but Psalm also offers a shorthand (suggested by [Tyson Andre](https://github.com/TysonAndre)) for the above which you might find more legible:

```
abstract class DatabaseModel {
  /**
   * @param int|array<int> $id
   * @psalm-return (
   *     $id is int
   *     ? static
   *     : array<static>
   * ) 
   */
  public static function fetch($id) {
    ...
  }
}
```

This return type tells Psalm that the function returns `static` when `$id` is an `int`, and `array<static>` when `$id` is an `array<int>`.

If the value of `$id` can't be inferred – like in the call `UserModel::load($_GET["query"])` – Psalm will return the union of both branches – in this case returning `static|array<static>`.

You can also nest conditional types to add additional rules. Here’s the function stub Psalm uses to understand PHP’s `abs` [function](https://www.php.net/manual/en/function.abs.php):

```
/**
 * @param int|float|numeric-string $number
 * @psalm-return (
 *     $number is int
 *     ? int
 *     : (
 *         $number is float
 *         ? float
 *         : int|float
 *     )
 * )
 */
function abs($number) {}
```

`abs`, `var_export`, `mktime` and other similar functions [are now handled in Psalm](https://github.com/vimeo/psalm/blob/a79122256c2bccd681bb3c9453471fb95d6055b3/src/Psalm/Internal/Stubs/CoreGenericFunctions.phpstub#L331-L397) using conditional return types.

That’s all there is to it. If your code returns different types in a way that’s conditional on its input and you want Psalm to understand what’s going on (without having to refactor or write a plugin), then conditional return types are for you.

---

P.S. if you're interested in peeking under the hood, the basic mechanics [live here](https://github.com/vimeo/psalm/blob/166b4d04a547c2baf60f0f31748d3adcbf34a145/src/Psalm/Type/Union.php#L1302-L1346).

P.P.S. if people find this useful, I hope that other static analysis tools will support this syntax too – it’s relatively simple to implement, and saves users time in the long-run.
