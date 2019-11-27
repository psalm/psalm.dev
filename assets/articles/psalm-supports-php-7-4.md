<!--
  title: Psalm supports PHP 7.4
  date: 2019-11-27 11:00:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

Thursday, November 28th 2019 is a big day for PHP: version 7.4 will be released to the world.

There are five major new language enhancements coming in PHP 7.4:

- Typed properties
- Arrow functions
- Return type covariance (and param type contravariance)
- Null coalescing assignment
- Spread operator in arrays

Psalm is ready for them.

## Typed properties

Psalm has best-in-class support for verifying docblock-provided property types, and that verification is even more important with PHP 7.4’s  new [explicit property types](https://wiki.php.net/rfc/typed_properties_v2).

Psalm provides a number of property checks that other packages do not:

### Prevent property access before initialisation

In PHP 7.4, accessing a property with an explicit type causes a fatal error in PHP if that property has not yet been defined. Psalm detects those errors for you:

```php
<?php

class A {
  private string $s;
  
  public function __construct() {
    echo $this->s; // causes fatal error
    $this->s = "hello";
  }
}
```

###  Warn about properties not initialised within constructors

Psalm will also warn you about properties that weren’t assigned a value inside the constructor:

```php
<?php

class A {
  private string $string_set_in_method;
  public int $unitialised_int;
  
  public function __construct() {
    // Psalm understands that $this->string_set_in_method
    // is set here
    $this->setString();
  }
  
  private function setString() : void {
    $this->string_set_in_method = "hello";
  }
}

echo (new A)->unitialised_int; // causes fatal error
```

## Arrow functions (aka short closures)

[Arrow functions](https://wiki.php.net/rfc/arrow_functions_v2) are good for a few reasons, but perhaps their main benefit is that they save space. You may be used to passing closures to `array_map` like this: 

```php
<?php

/**
 * @param array<int> $ints
 * @return array<string>
 */
function formatInts(array $ints) : array {
  return array_map(
    function(int $i) : string {
      return number_format($i, 3);
    },
    $ints
  );
}
```

With arrow functions the same closure can be expressed on a single line:

```php
<?php

/**
 * @param array<int> $ints
 * @return array<string>
 */
function formatInts(array $ints) : array {
  return array_map(
    fn($i) => number_format($i, 3),
    $ints
  );
}
```

Note that we’ve dropped the param type and return type declarations for that closure – they’re optional, and _Psalm can infer them_ in most situations.

Psalm also detects when the given `array_map` return type doesn’t match up with the expected one:

```php
<?php

/**
 * @param array<int> $ints
 * @return array<object>
 */
function formatInts(array $ints) : array {
  return array_map(
    fn($i) => number_format($i, 3),
    $ints
  );
}
```

## Return type covariance & param type contravariance

In PHP 7.3 this causes a fatal error:

```php
class A {
	public function getInstance(): self {
	    return new self();
	}
}
class AChild extends A {
	public function getInstance(): self {
	    return new self();
	}
}
```

In PHP 7.4 [the above behaviour is now allowed](https://wiki.php.net/rfc/covariant-returns-and-contravariant-parameters).

Psalm detects which version of PHP you’re running, and [will allow this behaviour if it detects PHP 7.4](https://psalm.dev/r/a5e73f4178?php=7.4). Users of PHP 7.3 and below [will still see an error reported](https://psalm.dev/r/a5e73f4178?php=7.3).

## Null coalescing assignment

PHP now supports the `??=` [null coalescing assignment operator](https://wiki.php.net/rfc/null_coalesce_equal_operator).

`$foo ??= $bar` is equivalent to `$foo = $foo ?? $bar`, and Psalm will treat it as such.

Psalm will also warn you when the null coalescing assignment is unnecessary:

```php
<?php

function takesString(string $s) : void {
  $s ??= "hello";
  echo $s;
}
```

## Spread operator in arrays

Psalm can understand the new [array spread syntax](https://wiki.php.net/rfc/spread_operator_for_array).

```php
<?php

$arrayA = [1, 2, 3];
$arrayB = [4, 5];
$result = [0, ...$arrayA, ...$arrayB, 6 ,7];

echo $result[3];
echo $result[7];
echo $result[9]; // error
```

## Enjoy PHP 7.4!

PHP 7.4 is a massive step forward for the language, and Psalm allows you to use its new features in a type-safe manner.

---

Want to know what’s happening in the world of Psalm?

[Follow @psalmphp on Twitter](https://twitter.com/psalmphp).
