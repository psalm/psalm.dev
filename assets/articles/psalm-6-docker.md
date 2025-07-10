<!--
  title: Official Psalm docker image
  date: 2025-05-03 18:00
  author: Daniil Gentili
-->

[Cross-posted from Daniil Gentili's blog &raquo;](https://blog.daniil.it/2025/07/10/official-psalm-docker-image/).

[Psalm](https://psalm.dev) is one of the biggest and most powerful PHP Static analysis tools, featuring exclusive features like [security analysis](https://psalm.dev/docs/security_analysis/), and in [Psalm 6.9](https://github.com/vimeo/psalm/releases/tag/6.9.0), an official, hyperoptimized Docker image was introduced.

Psalm's docker image uses a custom build of PHP built from scratch with a custom [deepbind patch](https://github.com/php/php-src/pull/18612) and the jemalloc allocator, running Psalm +30% faster on average than normal PHP (+50% faster if comparing to PHP without opcache installed).  

**My [deepbind patch](https://github.com/php/php-src/pull/18612) was also merged into PHP and will be available to all users (even those not using the Docker image) in PHP 8.5!**

To use it right now, on PHP 8.4, simply run:

```
docker run -v $PWD:/app --rm -it ghcr.io/danog/psalm:latest /composer/vendor/bin/psalm --no-cache
```

Issues due to missing extensions can be fixed by enabling them in psalm.xml and/or requiring them in composer.json, [see here &raquo;](https://psalm.dev/docs/running_psalm/configuration/#enableextensions) for more info.

Extensions not stubbed by Psalm itself (and thus not available as a psalm config option) may be stubbed using [traditional PHP stubs](https://github.com/JetBrains/phpstorm-stubs/).
