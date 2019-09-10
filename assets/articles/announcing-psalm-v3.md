<!--
	title: Announcing Psalm v3
	canonical: https://medium.com/@muglug/announcing-psalm-v3-76ec78e312ce
    date: 2019-01-03
    author: Matt Brown
    author_link: https://twitter.com/mattbrowndev
-->

TL;DR: Psalm is a PHP static analysis tool that’s designed to improve large codebases by identifying both obvious and hard-to-spot bugs through the inference of types. The new update makes Psalm faster, brings a new Composer-based plugin framework, and adds baseline generation, IDE support, and more.

* * *

Psalm exists because [we realized we needed it at Vimeo](/articles/fixing-code-that-aint-broken). It was open-sourced a year later because we thought that others might appreciate it, too. Working on it has been a fascinating experience — when I began I’d never talked to a PHP developer outside Vimeo. That quickly changed once we open-sourced Psalm, and I’ve since had a great number of conversations with developers from all over.

From those conversations, it’s become clear that the initial public release of Psalm had a sweet spot . It was great for [code that didn’t rely heavily on third-party libraries](https://github.com/paragonie). But for the wider PHP community — users of Doctrine, Laravel, Mockery, and other great frameworks — Psalm hasn’t been such an obvious choice.

One of the great joys of open-sourcing your code, though, is seeing people take it in directions you hadn’t imagined, and in the two years that Psalm has been publicly available, it has benefited from a number of great contributions from the community.

## Plugin play

One recent contribution — an [overhaul of the plugin framework](https://github.com/vimeo/psalm/pull/855) by Bruce Weirdan — was substantial enough to warrant a major version bump.

Before this update, people who wanted custom functionality would have to author their own plugins, which, in turn, required an understanding of Psalm’s internal class structure. Then they’d have to go through another hoop to enable the plugin.

With this update, everything is much easier. For example, to [enable PHPUnit compatibility](https://github.com/psalm/phpunit-psalm-plugin) (including support for mocking and assertions), you can type:

composer require — dev psalm/plugin-phpunit  
vendor/bin/psalm-plugin enable psalm/plugin-phpunit

A few already-published plugins provide extra type information for popular packages:

*   [psalm/plugin-phpunit](https://packagist.org/packages/psalm/plugin-phpunit)
*   [psalm/plugin-mockery](https://packagist.org/packages/psalm/)
*   [weirdan/doctrine-psalm-plugin](https://packagist.org/packages/weirdan/doctrine-psalm-plugin)
*   [psalm/plugin-sabre-event](https://packagist.org/packages/psalm/plugin-sabre-event)

This update also makes it far easier to write plugins. Plugin authors can now more easily change how Psalm treats third-party libraries with stub files, taking advantage of Psalm-specific docblock annotations like `@psalm-template` and `@psalm-assert` to add new functionality quickly without having to understand how Psalm works. [This stubbed method](https://github.com/psalm/phpunit-psalm-plugin/blob/9db3b253bb06ba749a96157f7a8865c2f94d1169/stubs/Assert.php#L7-L18), for instance, enables the tool to understand what’s happening when it encounters

`$this->assertInstanceOf(Foo::class, $bar);`

in PHPUnit test files.

To make writing your first plugin easier, Bruce has helpfully [provided a skeleton](https://github.com/weirdan/psalm-plugin-skeleton) for you to start with.

## Baseline support

At Vimeo, there are lots of types of issues that Psalm finds in our code. Unfortunately we haven’t the time to fix them all, so we were forced just to ignore those issues [via our Psalm config](https://gist.github.com/muglug/6c6ac543a51d94c62967bd861783ed07).

Not only does this approach ignore existing issues , it also encourages developers writing new code to continue to make those same mistakes.

Thankfully a GitHub user named [Erik Booij](https://github.com/ErikBooijCB) proposed and then implemented a solution: Psalm would generate a baseline file that would only identify bugs in new code, leaving the old code alone. Now that developers at Vimeo can see issues that once were hidden, they feel like their code is being held to a higher standard.

If you want to set your project up with a baseline, [follow this short guide](https://getpsalm.org/docs/dealing_with_code_issues/#using-a-baseline-file).

## IDE support

Vimeo isn’t just a PHP company — it’s a PHP, Python, Go, Java, Swift, Ruby, JavaScript, C, and C++ company. It’s relatively common for developers to switch between different languages on any given day, and those peripatetic engineers often prefer editors that are language-agnostic. Thankfully there are many fantastic IDEs to choose from.

But there’s still some day-to-day frustration. Developers can write code in their editors that pass all the IDE’s checks (even in PhpStorm), but Psalm is still able to find bugs in it. Psalm can still be run on your local machine (outside your editor), but it’s a slightly annoying extra step.

Luckily there’s a way around this problem. We (the developers of static analysis tools) can create a language server.

### What’s a language server?

It’s a process that runs in the background, providing insight into code written in a given language via the [Language Server Protocol](https://microsoft.github.io/language-server-protocol/). IDEs that support the protocol can provide feedback to the developer about the code that they’re writing without those IDEs needing to know anything about the particular language that the developer is using.

### Giving Psalm the Language Server power-up

In the last couple of months, I’ve put in the effort to give Psalm the ability to act as a language server. I started by building upon some great work by [Tyson Andre](https://github.com/TysonAndre) and [Felix Becker](https://github.com/felixfbecker). [Nikita Popov](https://github.com/nikic) also helped by improving [PHP Parser](https://github.com/nikic/PHP-Parser)’s fault tolerance. (PHP Parser is used by Psalm to turn PHP files into something it can analyze.)

The main challenge was making Psalm fast — fast to start up and fast to respond to all the requests that the IDE sends it. This was done with better file diffing (so we only parse and analyze bits of files that have changed) and using multiple processor cores more effectively (so Psalm is able to extract data from thousands of files per second on modern hardware). A nice side effect of this work is that Psalm’s regular command-line tool has become faster, too.

Psalm’s language server currently provides diagnostics (its regular error reports) and support for show-definition-on-hover and go-to-definition. It’s used by Vimeo engineers [working in Emacs, Vim, Visual Studio Code, Sublime Text, and others](https://getpsalm.org/docs/language_server/).

## Future work

There aren’t any big new goals for Psalm, but development isn’t at an end. Besides the [41 yet-to-be-implemented features](https://github.com/vimeo/psalm/issues?q=is%3Aopen+is%3Aissue+label%3Aenhancement), we also want to improve GitHub integration so that you can [see the results of Psalm checks](https://developer.github.com/changes/2018-05-07-new-checks-api-public-beta/) in your PR.

I’m also working to expand Psalm’s [docblock-based type system](https://getpsalm.org/docs/typing_in_psalm/) so that it has feature parity with [Hack](https://hacklang.org)’s type system, having [built a tool](https://hacktophp.org/) that attempts to convert Hack code into its PHP equivalent.

* * *

Thanks for reading! If you haven’t yet, [try Psalm](https://getpsalm.org). It’s good, I promise. Also: have you heard about Vimeo? [All the cool kids use it.](https://vimeo.com/sbergmann)
