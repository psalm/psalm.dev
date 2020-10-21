<!--
  title: Psalm 4 supports PHP 8
  date: 2020-10-21 07:00:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
  notice: This is part of a series of articles about the new features of [Psalm 4](/articles/psalm-4).
-->

PHP 8 is coming out soon, and Psalm is ready.

**Tip:** as well as supporting all the new features outlined below, Psalm 4 can also tell you if your PHP 7 code might break in PHP 8 – just run it with <br>`--php-version=8.0`

Here’s a big ol’ list of new PHP 8 features, with a few comments on how Psalm can help you use them safely.

## Constructor property promotion

If you’ve ever written out code that looks like this:

```php
<?php
class IntLinkedList {
    public int $value;
    public ?self $next;
    
    public function __construct(
        int $value,
        ?self $next = null
    ) {
        $this->value = $value;
        $this->next = $next;
    }
}
```

Then you’ll probably love constructor property promotion, which removes a ton of boilerplate. The above code becomes

```php
<?php
class IntLinkedList {
    public function __construct(
        public int $value,
        public ?self $next = null
    ) {}
}
```

### It’s great for small objects

PHP has a long history of people using arrays in place of objects for small bits of data, mainly because they're a little easier to construct.

Constructor property promotion helps turn this:

```
function addEvent(Video $video, int $time, string $label) {
    $video->events[] = ['time' => $time, 'label' => $label];
}
```

into

```
class VideoEvent {
    public function __construct(
        public int $time,
        public string $label
    ) {}
}

function addEvent(Video $video, int $time, string $label) {
    $video->events[] = new VideoEvent($time, $label);
}
```

You might say "Oh, but that’s _more_ code" and you'd be correct, but it’s faster and **uses less memory** than the equivalent array, so it’s a worthwhile change.

Constructor property promotion also goes really well with `@psalm-immutable` annotations, providing a concise and type-safe way of constructing immutable [data transfer objects](https://en.wikipedia.org/wiki/Data_transfer_object):

```php
<?php
/** @psalm-immutable */
class ImmutableLinkedList {
    public function __construct(
        public int $value,
        public ?self $next = null
    ) {}
}

$list = new ImmutableLinkedList(5, new ImmutableLinkedList(12));
echo $list->value;
$list->value = 6;
```

## Nullsafe operator

The nullsafe operator allows you to simplify a lot of existing code:

```php
<?php
class IntLinkedList {
    public function __construct(
        public int $value,
        private ?self $next
    ) {}

    public function getNext() : ?self {
        return $this->next;
    }
}

// the old, long way
function oldWay(IntLinkedList $l) : ?int {
    $next = $l->getNext();
    
    if ($next === null) {
        return null;
    }
    
    $next = $next->getNext();
    
    if ($next === null) {
        return null;
    }
    
    return $next->value; 
}

// the new short way
function newWay(IntLinkedList $l) : ?int {
    return $l->getNext()?->getNext()?->value;
}
```

If you've used Laravel, this is a language-level replacement for the `optional()` helper function.

## Named arguments

Named arguments can help you write slightly clearer code.

You can call functions with only the params you need:

```php
<?php

function foo(string $a = "", int $b = 0, string $c = "")  : void {
    echo $a . ' ' . $b . ' ' . $c;
}

foo(b: 5, a: "hello");
```

Things become a bit more useful when deserialising keyed arrays into classes:

```php
<?php

class User {
    public function __construct(
        public int $id,
        public string $name,
        public int $age
    ) {}
}

/**
 * @param array{id: int, name: string, age: int} $data
 */
function processUserData(array $data) : User {
    return new User(...$data);   
}

/**
 * @param array{id: int, name: string, aeg: int} $data
 */
function processUserDataInvalid(array $data) : User {
    return new User(...$data);   
}
```

## Match expressions

[Match expressions](https://wiki.php.net/rfc/match_expression_v2) are sort of a tighter form of `switch` statements: they’re designed to capture all potential values of a given conditional, which means that Psalm can warn when it detects that not all possiblities are captured:

```php
<?php

class Airport {
    const JFK = "jfk";
    const LHR = "lhr";
    const LGA = "lga";

    /**
     * @param self::* $airport
     */
    public static function getName(string $airport): string {
        return match ($airport) {
            self::JFK => "John F Kennedy Airport",
            self::LHR => "London Heathrow",
        };
    }
}
```

That `@param self::* $airport` is a constant wildcard type, [documented here](https://psalm.dev/docs/annotating_code/typing_in_psalm/#specifying-stringint-options-aka-enums).

## Throw expressions

Like match expressions, throw expressions can also help you write less code:

```php
<?php

function foo() : void {
    // PHP 7
    if (isset($_GET['foo']) && is_string($_GET['foo'])) {
        $foo = $_GET['foo'];
    } else {
        throw new UnexpectedValueException("bad foo");
    }
    
    // PHP 8
   	$bar = isset($_GET['bar']) && is_string($_GET['bar'])
        ? $_GET['bar']
        : throw new UnexpectedValueException("bad bar"); 
    
    echo $foo . ' ' . $bar;
}
```

## Union types

Psalm has supported union types from the very beginning, as they’re necessary to describe the output of many builtin PHP functions (such as `strpos`, which returns `int|false`).

PHP now supports union types in native declarations, and now so does Psalm:

```php
<?php

function takesIntOrFalse(int|false $foo) : void {
    if ($foo === false) {
        echo "false";
    } else {
        echo $foo;
    }
}

function doStrpos(string $a, string $b) : int|false {
    return strpos($a, $b);
}

takesIntOrFalse(doStrpos("a", "b")); // prints "false"
takesIntOrFalse(doStrpos("aa", "a")); // prints "0"
```

## get_debug_type

Useful when dealing with unknown values, and also when debugging, `get_debug_type` allows you to write code like

```php
<?php

function takesUnknown(mixed $var) : void {
    switch (get_debug_type($var)) {
        case "string":
            echo $var;
            break;

        case Exception::class;
            echo "an Exception with message " . $var->getMssage();
            break;
    }
}
```

## Attributes

Psalm support for [checks on PHP 8 attributes](https://github.com/vimeo/psalm/issues/4367) will come within the next couple of weeks, shaped by what the community wants. Feel free to chime in!
