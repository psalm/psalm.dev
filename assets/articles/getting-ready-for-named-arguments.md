<!--
  title: Getting ready for Named Arguments
  date: 2020-08-17 07:00:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

[Named Arguments](https://wiki.php.net/rfc/named_params) are coming to PHP with the release of PHP 8!

Named Arguments will make many developers very happy, but they come with a pretty important caveat that I hope to help mitigate with the latest version of Psalm.

## The caveat

Let’s say we have an interface on an existing third-party library, and we want to query it. In PHP 7 the code is simple:

```
interface IQueryable {
    public function fetchValue(string $sqlString, bool $cache);
}

class MySqlQueryable implements IQueryable {
    public function fetchValue(string $queryString, bool $cache)
    {
        echo $queryString;
        // some code
    }
}

function executeSQL(IQueryable $queryable) {
    return $queryable->fetchValue(
        "SELECT count(*) from foo",
        true
    );
}

executeSql(new MySqlQueryable());
```

In PHP 8 the same code will work just fine unless we decide to call the method with named arguments, where we’ll run into a fatal error:

```
function executeSQL(IQueryable $queryable) {
    return $queryable->fetchValue(
        sqlString: "SELECT count(*) from foo",
        cache: true
    );
}

// Fatal Error: Unknown named parameter $sqlString
```

That’s because, though the interface has a `$sqlString` parameter, the actual implementing class does not (it’s instead named `$queryString`) and PHP doesn’t know what to do. We could fix the issue by renaming `$queryString` to `$sqlString`.

## Psalm now detects this…

The latest version of Psalm will alert you to these parameter name mismatches with a `ParamNameMismatch` issue, so it's easy to see which methods won't be compatible with named-argument calling.

## …and can fix it automatically

If you want to fix them all in one go, Psalm can do that – simply run

`vendor/bin/psalm --alter --issues=ParamNameMismatch`

And wait for Psalm to do its thing.

Running that command will convert the above example into

```
interface IQueryable {
    public function fetchValue(string $sqlString, bool $cache);
}

class MySqlQueryable implements IQueryable {
    public function fetchValue(string $sqlString, bool $cache)
    {
        echo $sqlString;
        // some code
    }
}
```

## Workarounds

If you don't want to perform such a big alteration, or you're a library maintainer who wants to retain some freedom, you have a few options:

### Self-contained projects

Do you have complete control over who uses your code, and how they use it?

If so, you might want to institute a blanket ban on the use of named arguments when calling methods.

You can do this with a `allowNamedArgumentCalls="false"` config flag in your Psalm config.

When this flag is added Psalm will allow mismatching parameter names on any method where the interface/parent class is in your project code.

### Libraries

If you don’t have complete control over how your code is called, things can get a little tricky.

If you have a published API like

```
interface IQueryable {
  function fetchValue(string $sqlString, bool $cache);
}
```

You’re now prevented from being allowed to change the param name to `$queryString`, else you’ll break any user of your API who calls that method using named arguments.

Psalm ([followed by more tools in the near future](https://github.com/Roave/BackwardCompatibilityCheck/issues/264)) now supports a `@no-named-arguments` docblock annotation that you can add to methods:

```
interface IQueryable {
  /** @no-named-arguments */
  function fetchValue(string $sqlString);
}
```

Once Psalm roles out support for calling methods with named arguments, it will prohibit named-argument calls to any method with this annotation.

Adding this annotation will allow library maintainers to rename parameters without breaking backwards-compatibility for anyone who uses static analysis tools. It’s a slightly looser guarantee than you might be happy with, but it’s 2020 and we’re all having to make do.

Psalm also supports a second config flag `allowInternalNamedArgumentCalls="false"`. This allows library maintainers to guarantee matching param names _except_ for internal classes and interfaces, where they’re free to change things up.

With that flag added to the Psalm config, this is now allowed:

```
/** @internal */
interface IQueryable {
    public function fetchValue(string $sqlString, bool $cache);
}

/** @internal */
class MySqlQueryable implements IQueryable {
    public function fetchValue(string $queryString, bool $cache)
    {
        // some code
    }
}
```

## Summary

Hopefully these options will allay some concerns around the introduction of named arguments. There’ll be more updates soon as Psalm adds support for PHP 8 features.
