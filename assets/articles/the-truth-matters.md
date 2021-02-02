<!--
  title: Avoiding false-positives with flow-sensitive conditional analysis
  date: 2021-02-02 10:00:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

It’s hard to develop a new static analysis tool for a language that’s been around a couple of decades — alarm bells go off in your users’ minds if your tool flags code that has worked fine for a dozen years.

Some relatively new static analysis tools have overcome this through a sort of argument from authority. TypeScript’s author can say “I have designed famous programming languages, trust me when I say your code is poorly-written”.

I, however, have designed zero programming languages, so I have to be a little more accommodating when building a static analysis tool for PHP.

Psalm has a feature I'm calling flow-sensitive conditional analysis. I don't believe any similar tools employ it (though I'd love to be wrong). This article will hopefully show how Psalm’s analysis can help us avoid a false-positive on this snippet of code:

```php
<?php

class A {
    public function isValid() : bool {
        return (bool) rand(0, 1);
    }

    public function someAction() : void {}
}

function takesA(?A $a) : void {
    $valid_a = $a && $a->isValid();

    if ($valid_a) {
        $a->someAction();
    }
}
```

## Flow-sensitive type analysis

Any type-inferring static analysis tool for dynamically-typed languages like PHP or JavaScript needs to perform flow-sensitive *type* analysis, meaning that the tool understands how the datatypes of variables change within a function given that function’s collection of statements and expressions.

At every point during the analysis of a given set of statements such tools keep a map of variable names with corresponding types, updating that map as they traverse the code's abstract syntax tree.

This allows those tools to infer that executing this code will never result in a null reference:

```php
<?php
function getStringOrDefault(?string $a) : string {
    if ($a === null) {
        return 'default';
    }
    
    return $a;
}
```

Here Psalm’s behaviour is similar to other static analysis tools:

1. Psalm sees the conditional expression and generates a formula (in Conjunctive Normal Form) composed of a series of clauses. The `if` conditional `$a === null` is converted to the formula `($a is null)`. That formula represents all the things we know to be true inside the `if` block.
2. From this formula we generate a list of assertions that can be applied back to the types that are currently defined. To do this, we look for all clauses in our formula that contain just a single variable. In this case, that’s the clause with the  `($a is null)` assertion that we found earlier. We extract that clause, then reconcile it with the existing known type for `$a`, `string|null`, resulting in us setting the type of `$a` to `null`.
3. Psalm sees that the `if` block always returns as long as the formula we generated above evaluates to true, so after the `if` block Psalm knows that the formula _must_ evaluate to false. Thus the negation of the formula is true after the `if` block.
4. Psalm calculates the negation of the formula as `($a is !null)` trivially.
5. As in step 2, it looks for clauses containing a single variable which can translate to a type assignment. Here we take the `($a is !null)` clause and reconcile it with the `string|null` type for `$a` to give us the type `string`.
6. Psalm can then determine that `return $a` always returns a string.

## Flow-sensitive conditional analysis

While flow-sensitive type analysis keeps a map of variables and their respective types, flow-sensitive conditional analysis adds a list of conditionals it knows to be true at each point in the code.

Let’s introduce some extra complexity to our function above and see how flow-sensitive conditional analysis is useful:

```php
<?php
function getStringsOrDefault(?string $a, ?string $b) : string {
    if ($a === null && $b === null) {
        return "default";
    }
  
    if ($a !== null) {
        return $a;
    }
  
    return $b;   
}
```

1. Psalm analyses the first conditional `$a === null && $b === null`  and generates the formula `($a is null) && ($b is null)`.
2. Psalm then looks for clauses containing a single variable assertion, and sees two — `($a is null)` and `($b is null)`. Psalm dutifully assigns `$a` and `$b` to null inside the if condition (though these assignments are never used).
3. Since the `if` block always returns when entered, the `if` condition must be false after it, so we compute the negation `!(($a is null) && ($b is null))`  to get `($a is !null || $b is !null)`. This is a single clause, but since it contains two variables we cannot infer any types straight away. At this point tools that just perform flow-sensitive type analysis discard that negated formula, since the formula cannot translate directly into type assignments. But Psalm retains this information, stored as the set of conditions it knows to be true.
4. Now it encounters a new `if` condition `$a !== null`. It derives the formula `($a is !null)` and inside the `if` block it trivially determines `$a` has the type `string`
5. After that second `if` condition Psalm then knows that `($a null)`. It combines that formula with the previously-generated formula `($a is !null || $b is !null)`.\
`($a is null) && ($a is !null || $b is !null)` simplifies to\
`($b is !null)`.\
This gives us a clause with a single variable which allows us to determine that `$b` has the type `string`. **Tools that just perform flow-sensitive type analysis emit a false-positive warning here.**

If you’re still with me, congratulations!

Knowing a bit about flow-sensitive conditional analysis, let’s revisit the very first example:

```php
<?php

class A {
    public function isValid() : bool {
        return (bool) rand(0, 1);
    }

    public function someAction() : void {}
}

function takesA(?A $a) : void {
    $valid_a = $a && $a->isValid();

    if ($valid_a) {
        $a->someAction();
    }
}
```

Whenever Psalm encounters an assignment where the right-hand side is an AND/OR operation like this: 

```
$valid_a = $a && $a->isValid();
```

It introduces a new condition to its list of conditions it knows to be true:

```
($valid_a is falsy || (($a is !falsy) && <extra stuff>))
```

In plain English “it’s either the case that `$valid_a` is `falsy` _or_ it’s the case that `$a` is not falsy”.

Since we store everything in Conjunctive Normal Form this becomes

```
($valid_a is falsy || $a is !falsy)
  && ($valid_a is falsy || <extra stuff>)
```

From the `if` condition Psalm derives the formula `($valid_a is !falsy)`. Now Psalm knows that inside the `if` block this formula holds:

```
($valid_a is !falsy)
  && ($valid_a is falsy || $a is !falsy)
  && ($valid_a is falsy || <extra stuff>)
```

This formula can again be simplified to

```
($a is !falsy) && (<extra stuff>)
```

This tells Psalm that `$a` is never `falsy` inside the `if` condition, which allows the tool to avoid a false-positive that other tools miss.

## What’s the cost?

Performing flow-sensitive conditional analysis adds a small (roughly 6%) average increase in Psalm’s overall runtime. There are a number of pathological cases that can create very large formulae (Psalm maxes out at 20,000 terms before it gives up trying to simplify formulae).

This analysis can also generate some slightly convoluted issue messages. For instance

```php
<?php
function checkValue(string $a) : void {
    if ($a !== "hello") {
        if ($a === "hello") {
            // some code
        }
    }
}
```

Produces

> Condition `($a is string(hello))` contradicts a previously-established condition `($a is not string(hello))`

On the plus-side, a slightly-convoluted message is better than no message at all.

### I am an island

Lastly there's a cost to maintaining a system that doesn’t appear to exist outside of Psalm. I’m hoping this article might change that!
