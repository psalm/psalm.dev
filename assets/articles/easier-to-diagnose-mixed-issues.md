<!--
	title: Making mixed issues easier to diagnose
	date: 2021-03-29 09:30:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

When running Psalm at its strictest (level 1) Psalm will tell you when you’re doing something risky with a type it cannot infer.

For example, when we ask Psalm to analyse this code:
 
```
<?php

function takesArray(array $arr) : void {
    foreach ($arr as $some_string) {
	    // more logic
    }
}
```

It gives us a warning

```
MixedAssignment - Unable to determine the type
    that $some_string is being assigned to
```

It’s probably not dangerous in practice — you might only ever be calling `takesArray` with an array of strings — but Psalm thinks it’s worth bringing to your attention.

A lot of developers try running Psalm at the strictest level, see a lot of these issues, and then either give up completely or opt for a less strict level (2 and higher).

For developers who do still want to use Psalm at its strictest, one quick way of appeasing the type checker is to hardcode a docblock `@var` type  above the `foreach` loop:

```
<?php

function takesArray(array $arr) : void {
    /** @var string $some_string */
    foreach ($arr as $some_string) {
	    // more logic
    }
}
```

This isn’t great, though — the type is no longer inferred naturally, so Psalm cannot verify the type in the docblock is actually correct.

A more robust fix — one that helps Psalm verify that calls to `takesArray` are correct — is to fix the issue at its source, by adding a docblock `@param` type:

```
<?php

/** @param array<string> $arr */
function takesArray(array $arr) : void {
    foreach ($arr as $some_string) {
	    // more logic
    }
}
```

Developers will often opt for the quick fix over the more robust one, because it can be hard to figure out where exactly the `mixed` type is coming from.

## Nudge

Now Psalm can tell developers where the `mixed` type came from:

```
MixedAssignment - Unable to determine the type
    that $some_string is being assigned to

  The type of $some_string is sourced from here
    function foo(array $arr) : void {
                       ^^^^
```

Hopefully this will encourage developers to chose the robust fix over the quick one, and also increase the usage of Psalm’s strictest level.
