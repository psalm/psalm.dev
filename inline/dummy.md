This blog post includes interactive code snippets - feel free to edit and explore Psalm further!

--------------

Here is a list that should be formatted nicely:

*   Some thing
*   [IDE support](https://psalm.dev/docs/language_server/) — fdsfsf fds fasfaf afsaffsd f fsadf.
*   [Automated fixes](https://psalm.dev/docs/fixing_code/)  sdffsd - gsfgagfg fdsfasfs.

Variables that are passed by reference can be hard for static analysis tools to reason about.

```php
<?php
function bar(string &$s) : void {  
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
function bar(string &$s) : void {  
  $s = new \stdClass(); // no error  
}

$a = "hello";  
bar($a);  
echo strlen($a); // typechecker error
```

Psalm can now automatically remove unused methods and properties:

```php
<?php // fixme
class Queue {
  public function clear() : void {}
  public function clearLegacy() : void {}
}

(new Queue())->clear();
```

I’ve given Psalter a new skill - the ability to add missing param types to methods based on how they’re used in your codebase, so if you only call a method with a string as its first argument, Psalm will add a `@param string $someParamName` to that method’s docblock.

```php
<?php // fixme
class A {
  public function foo($bar) : void {
    echo $bar;
  }
}

(new A)->foo("hello");
```
