<!--
  title: Psalm, now with slightly better type inference
  date: 2020-01-07 09:35:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

Prepare yourselves for a mind-blowing piece of news: the latest minor version of Psalm (3.8.x) has slightly better type inference than 3.7.x.

If you’re not an ardent fan, you might be wondering why you should care, but here’s the thing: type inference is at the core of how Psalm works, so making Psalm’s type inference more accurate bleeds into everything else it does. More accurate type inference means more bugs caught and fewer false-positives.

(Type inference, for those unfamiliar, is the process of to figuring out the types of expressions in your code, be they variables, properties, conditionals or otherwise.)

## The existing (really quite good) type inference

Psalm does some fairly intricate handling of conditionals, allowing it to understand the following code rejected by other static analysis tools:

```php
<?php
class User {
  public string $name;
  public function __construct(string $name) {
    $this->name = $name;
  }
}

function getOwnerName(
  ?User $file_owner,
  ?User $folder_owner
) : string {
  if (!$file_owner && !$folder_owner) {
    throw new \UnexpectedValueException('Bad');
  }

  if ($file_owner) {
    return $file_owner->name;
  }

  // Psalm understands that $folder_owner
  // cannot be null here
  return $folder_owner->name;
}
```

If you're interested in this analysis, check out [my talk](https://docs.google.com/presentation/d/1BW9Xe1VFKtjqskcrRQ2m29f4oxeVFL-fL5Gww_JOPgE/edit?usp=sharing) from slide 81 onwards.

## The new (ever so slightly better) type inference

The improved system builds on that analysis, adding better handling for strings and callable types.

Psalm now has a better understanding of non-empty strings, enabling it to detect the bug here:

```php
<?php
/**
 * @param array<string> $names
 */
function filterNames(array $names) : void {
  $names = array_filter(array_map('trim', $names));
  foreach ($names as $name) {
    if (!$name) {
      // this can never happen
    }
  }
}
```

As an added bonus, you can now also use `non-empty-string` as a typehint whenever you want the type checker to enforce that passed strings are non-empty:

```php
<?php
/**
 * @psalm-param non-empty-string $name
 */
function sayHello(string $name) : void {
  echo 'Hello ' . $name;
}

function takeInput() : void {
  if (isset($_GET['name']) && is_string($_GET['name'])) {
    $name = trim($_GET['name']);

    sayHello($name); // possible bug

    if ($name) {
      sayHello($name); // this is ok
    }
  }
}
```

Psalm is also now smarter when it comes to assignments in conditionals – It accurately reports the bug here:

```php
<?php
interface Converter {
	function getEmail(string $value): ?EmailAddress;
}

interface EmailAddress {
	function isDomainValid(): bool;
}

/**
 * @param mixed $value
 */
function filterValue(Converter $converter, $value): EmailAddress
{
  if (\is_string($value)
    && ($value = $converter->getEmail($value)) !== null
    && $value->isDomaiValid()
  ) {
    return $value;
  }

  throw new Exception();
}
```

because it understands that `$value` is redefined as part of the null check.

## That’s it

Enjoy the slightly better type inference!
