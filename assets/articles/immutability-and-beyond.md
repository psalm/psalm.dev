<!--
  title: Immutability and beyond: verifying program behaviour with Psalm
  date: 2019-09-10 06:30:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

As a language, PHP permits you to do basically anything. There are some built-in runtime constraints (like parameter and return types) that you can opt into, but it’s otherwise pretty free-wheeling. Fans of paradigms that work well in other languages (such as functional programming) can find themselves a little adrift in PHP land.

Psalm adds some strictness to the everyday task of writing PHP. Its strictness is customisable: you can choose to have Psalm complain about only the most basic things (like undefined classes and undefined variables), but you can also choose to have Psalm complain about [every type-related issue it can discover](https://psalm.dev/docs/running_psalm/issues/). Some might consider the latter option unnecessary masochism, but many embrace the challenge of writing code that passes Psalm’s most rigid checks.

I've recently added a raft of annotations that can help functional programming fans better describe their program’s behaviour, along with corresponding Psalm errors when code is deemed to have fallen foul of those annotations.

### Why functional programming?

There are already plenty of ways to think about how you structure your code (one example: [cyclomatic complexity](https://en.wikipedia.org/wiki/Cyclomatic_complexity)). Functional programming, with its focus on immutability and functional purity, gives you yet another way to rate your code’s architecture. It’s one that many find helpful when figuring out how to improve their code-writing skills.

If you're only dimly aware of what functional programming is, and how it can be useful in an object-oriented language like PHP, [this is a good introductory video](https://vimeo.com/180287057#t=51).

This article isn't designed to get everyone using these patterns, but I hope it'll pique your interest. 

---

## Functional programming fundamentals

### Immutability

An [immutable object](https://en.wikipedia.org/wiki/Immutable_object) is one in which every property is immutable — once instantiated, there’s no way for its state to change.

Immutable objects are easier to reason about than regular objects, as any given object’s properties will always have the same values, and their methods will always behave in a deterministic fashion.

One can easily create an immutable object whose properties are impossible to change at runtime:

```php
<?php

final class CantTouchThis {
  private string $a;
  private int $b;
  
  public function __construct(string $a, int $b) {
    $this->a = $a;
    $this->b = $b;
  }
  
  public function getA() : string {
    return $this->a;
  }
  
  public function getB() : int {
    return $this->b;
  }
}
```

### Purity

The idea of immutability is closely linked to the concept of [pure functions](https://en.wikipedia.org/wiki/Pure_function).

A pure function is one whose output is completely deterministic given its input, and which has no side effects. We can make an addition function whose purity is trivial to determine:

```php
<?php

class Arithmetic {
  public static function add(int $left, int $right) : int {
    return $left + $right;
  }
}

echo Arithmetic::add(40, 2);
echo Arithmetic::add(40, 2); // same value is emitted
```

Some operations render a function impure:

```php
<?php

class Arithmetic {
  public static function addCumulative(int $left) : int {
    /** @var int */
    static $i = 0; // side-effect
    $i += $left;
    return $i;
  }
}

echo Arithmetic::addCumulative(3); // outputs 3
echo Arithmetic::addCumulative(3); // outputs 6
```

We can see that the output of `Arithmetic::addCumulative` is not solely dependent on its input.

Fans of functional programming generally prefer to put as much logic into pure functions as possible, leaving a few impure functions to do messy work (database access, writing to files, and so on). Pure functions are also very easy to test.

A quote from [a former colleague](https://twitter.com/twitchard) and fan of functional programming:

> Immutability is nice because it’s a guarantee that the type signature fully encapsulates the behaviour of your function. Humans are good at reasoning about the local behaviour of functions, but type checkers are _much_ better at reasoning about their global behaviour.

---

## Annotating immutability in Psalm

This section introduces a bunch of new Psalm annotations:

- `@psalm-readonly` (for properties)
- `@psalm-pure`, `@psalm-mutation-free` and `@psalm-external-mutation-free` (for functions and methods)
- `@psalm-immutable` (for classes)

### Per-property immutability

Some OOP languages have built-in support for public properties that can be read  anywhere, but only written to once (on initialisation). PHP doesn't support them, but people tend to simulate the idea by making the property private and adding a public `get<PropertyName>` method:

```php
<?php
class A {
  private string $s;
  
  public function __construct(string $s) {
    $this->s = $s;
  }
  
  public function getS() : string {
    return $this->s;
  }
}
```

This works well, but can be unnecessary boilerplate — especially if the only consumers of `A` are internal to your codebase.

Psalm now supports an annotation for properties, `@psalm-readonly` — [suggested by Nuno Maduro](https://twitter.com/enunomaduro/status/1157966022579761152) — that tells Psalm to prohibit mutation of the property:

```php
<?php
class B {
  /** @psalm-readonly */
  public string $s;
  
  public function __construct(string $s) {
    $this->s = $s;
  }
}

$b = new B("hello");
echo $b->s;
$b->s = "boo"; // disallowed
```

Depending on how your app is built, using a `@psalm-readonly` property may be preferable to a private property and public getter.

### Mutation-free methods

Many instance methods don’t change state — like the explicit getters seen above, their output is just a function of their instance’s properties. Those methods are not wholly pure, but pure-_ish_.

Psalm supports an annotation that ensures that behaviour — `@psalm-mutation-free`:

```php
<?php
class D {
  private string $s;
  
  public function __construct(string $s) {
    $this->s = $s;
  }
  
  /**
   * @psalm-mutation-free
   */
  public function getShort() : string {
    return substr($this->s, 0, 5);
  }
  
  /**
   * @psalm-mutation-free
   */
  public function getShortMutating() : string {
    $this->s .= "hello";
    return substr($this->s, 0, 5);
  }
  
  /**
   * Psalm knows that simple property-getting methods
   * are mutation-free
   */
  public function getS() : string {
    return $this->s;
  }
}

$d = new D("hello");
echo $d->getShort(); // this is fine
$d->getShort(); // this is unused
```

Calling any mutation-free method has no side effects.

### Annotating immutable classes in Psalm

You can use the annotation `@psalm-immutable` on any class you like — it’s the equivalent of adding `@psalm-readonly` to every property and `@psalm-mutation-free` to every instance method of that class.

Psalm verifies that you’re using a `@psalm-immutable` class as intended, preventing any side effects.

We can annotate the class above to declare it all-around immutable, enabling us to make the property publicly visible and remove the boilerplate getter.

```php
<?php

/** @psalm-immutable */
class E {
  public string $s;
  
  public function __construct(string $s) {
    $this->s = $s;
  }
  
  public function getShort() : string {
    return substr($this->s, 0, 5);
  }
}

$e = new E("hello");
echo $e->getShort(); // this is fine
$e->getShort(); // this is unused
$e->s = "bad"; // this is an error
```

### Annotating pure functions

We use `@psalm-pure` to annotate functions (static methods or regular functions) whose output is wholly dependent on parameter values.

When added to the examples given earlier, we can see Psalm verify and disprove the purity of the respective methods.

```php
<?php

class Arithmetic {
  /** @psalm-pure */
  public static function add(int $left, int $right) : int {
    return $left + $right;
  }

  /** @psalm-pure */
  public static function addCumulative(int $left) : int {
    /** @var int */
    static $i = 0; // side-effect
    $i += $left;
    return $i;
  }
}
```

### Real-world example

We can use immutable classes and pure functions to create [a very simple currency implementation](https://psalm.dev/r/d404685e79).

There are also examples of immutable classes in PHP’s standard library, including [DateTimeImmutable](https://www.php.net/manual/en/class.datetimeimmutable.php):

```php
<?php
function foo(DateTimeImmutable $dt) : void {
  $dt->modify("+1 day")->format("Y-m-d"); // bad
  echo $dt->modify("+1 day")->format("Y-m-d"); // good
}
```

## Going deeper with external-mutation-free

Sometimes you'll have a class with some internal state that can get updated by one of the class’s methods:

```php
<?php

class Counter {
  private int $count = 0;
  
  public function increment() : void {
    $this->count++;
  }
  
  public function getCount() : int {
    return $this->count;
  }
}
```

You might think, “Well, that class can’t be used in a pure function — calling `Counter::increment` clearly has side effects.”

But not so fast! If the instance of `Counter` is created inside a pure function, calling `Counter::increment` will not have any side effects outside that function. We can describe this idea with a different annotation on the class — `@psalm-external-mutation-free`:

```php
<?php

/** @psalm-external-mutation-free */
class Counter {
  private int $count = 0;
  
  public function increment() : void {
    $this->count++;
  }
  
  public function getCount() : int {
    return $this->count;
  }
}

/** @psalm-pure */
function makeCounter() : Counter {
  $a = new Counter();
  $a->increment(); // this is fine
  return $a;
}

/** @psalm-pure */
function takeCounter(Counter $a) : Counter {
  $a->increment(); // Counter already exists, this is a mutation
  return new Counter();
}
```

---

## The purity scale

We now have a range of different annotations we can use to annotate instance methods, static methods, and regular functions.

- `@psalm-pure`  
  the function does not mutate any state external to the function; the function’s output is entirely deterministic based on its parameter input
- `@psalm-mutation-free`  
  the function does not mutate any state external to the function, and its output is deterministic based on its parameter input and the attached instance’s properties
- `@psalm-external-mutation-free`  
  the function does not mutate any state external to the _class,_ and its output is deterministic based on its parameter input and the attached instance’s properties
- \<no annotation\>  
  functions can do whatever they like

Additionally we have a couple of annotations we can use to annotate classes.

- `@psalm-immutable`  
  guarantees that all class properties can be written to just once in the constructor, and all instance methods are mutation-free
- `@psalm-external-mutation-free`  
  guarantees that all instance methods won’t mutate state outside the class
