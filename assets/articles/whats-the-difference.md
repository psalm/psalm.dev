<!--
  title: Psalm 4: Fast by default
  date: 2020-10-21 07:00:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
  notice: This is part of a series of articles about the new features of [Psalm 4](/articles/psalm-4).
-->

Psalm 4 comes with diff mode — where only changed methods and their dependents are analysed — turned on by default.

This means Psalm runs much faster by default (if you prefer to go slow, there’s a new `--no-diff` option).

Psalm’s diff mode isn’t new - we’ve been using it on-and-off at Vimeo for the last four years, but it hasn’t always worked well with Psalm’s other features, in particular unused class and method detection. I’ve done a lot of work in the past year to get everything working well, and everyone can now benefit.

Why has it taken so long? Psalm is a powerful tool, performing a number of different analyses, and Psalm’s diff mode has to be aware of exactly what to cache, and when. The diff mode keeps track of which methods have changed in a given class, and only re-analyses those methods. It also now keeps track of unused classes and methods, so you’ll be alerted quickly when a small change you’ve made renders a whole class unnecessary.

## Where diffing helps

There are three things that happen to every file in Psalm's project directories when you run it for the first time:

_Scanning_<br>
An Abstract Syntax Tree (AST) is generated from its contents<br>
That AST is traversed to disccover all dependencies

_Analysing_<br>
That AST is traversed to find type-related bugs

The aim of diffing is to re-use as much information as possible on subsequent runs.

### Speeding up abstract syntax tree generation

Psalm uses an excellent [AST generator for PHP](https://github.com/nikic/PHP-Parser). That library generates ASTs from large files quickly, but it's even quicker not to use it at all.

The simplest way to avoid regenerating ASTs is to cache them, and invalidate cache when the underlying file changes.

Psalm goes a bit further: if it detects that only part of a file has changed (e.g. renaming a variable inside a method) it only re-generates the AST for the changed parts of the file. This optimisation is most noticeable when making minor changes to very large files.

Once Psalm has an updated the AST, it then compares that AST against the cached one (before the most recent file change) and generates a high-level diff between the two that helps it speed up analysis for later.

### Speeding up analysis

Psalm's analysis is the most computationally-expensive thing it does, so we want to cache as much of it as possible.

When running Psalm in diff mode the difference between the cached AST and the newly-updated one is used to figure out which classes, methods and properties have changed. That allows Psalm to only re-analyse the parts of the AST that have changed.

It also caches issues it discovered in the past, along with where in the code it found them, so it'll report issues it found in a previous run without having to re-analyse methods.

This means that, after a small change to a file, the reported issues for that file can be a mix of both cached and newly-generated issues.

Taken together, these updates help make Psalm’s analysis very fast.

