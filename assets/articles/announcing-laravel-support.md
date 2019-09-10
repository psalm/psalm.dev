<!--
	title: Announcing Psalm support for Laravel
	canonical: https://medium.com/@muglug/announcing-psalm-support-for-laravel-8a0fc507e220
    date: 2019-03-05
    author: Matt Brown
    author_link: https://twitter.com/mattbrowndev
-->

Today I’m releasing a [Psalm plugin for Laravel](https://github.com/psalm/laravel-psalm-plugin)!

### Background

Two months ago [I announced the latest version of Psalm](https://medium.com/vimeo-engineering-blog/announcing-psalm-v3-76ec78e312ce), and discussed how its new plugin framework would make authoring integrations for third-party packages much easier. A bunch of great plugins have sprung up since, but none for [Laravel](https://laravel.com/).

Authoring a such a plugin from scratch requires an in-depth knowledge of that framework. Nobody came forward to volunteer, and my own experience with Laravel is essentially zero.

Luckily there’s a popular tool that’s done most of the work already: the [Laravel IDE Helper](https://github.com/barryvdh/laravel-ide-helper). It generates static files that help PHPStorm understand Laravel’s functions and methods.

There are two types of files the IDE Helper generates:

*   regular stubs (PHP code without function/method bodies)
*   a `.phpstorm.meta.php` file that allows PHPStorm to interpret different return types for a set of known functions depending on those functions’ arguments.

Psalm already understood regular stubs, and adding support for the IDE Helper’s generated `.phpstorm.meta.php` was relatively simple. The plugin generates those files in a separate directory, and [tells Psalm to use them in its analysis](https://github.com/psalm/laravel-psalm-plugin/blob/a700c89061d151d1c08851abd93d834f9183534d/src/Plugin.php#L75-L76).

The advantage of this approach is threefold:

*   Psalm can benefit from future improvements to the Laravel IDE Helper without me having to do any extra work.
*   Anyone who uses Psalm in a project with a `.phpstorm.meta.php` stub can now benefit from Psalm’s understanding of them, regardless of whether they use Laravel.
*   Users of other IDEs can benefit from these stubs too, if they use [Psalm’s Language Server functionality](https://psalm.dev/docs/language_server/).

### Installation

If you haven’t already, [install Psalm](https://psalm.dev/quickstart). Then run

    composer require --dev psalm/plugin-laravelvendor/bin/psalm-plugin enable psalm/plugin-laravel

[Head to the plugin’s Github repo](https://github.com/psalm/laravel-psalm-plugin) for all the usual open source stuff.

* * *

Thanks to [Barry vd. Heuvel](https://twitter.com/barryvdh) for creating and maintaining the Laravel IDE Plugin, and to a number of contributors for improving Psalm’s plugin architecture so this was a reasonably simple integration.
