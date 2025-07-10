<!--
  title: Psalm v7: up to 10x performance!
  date: 2025-05-03 18:00
  author: Daniil Gentili
-->

[Cross-posted from Daniil Gentili's blog &raquo;](https://blog.daniil.it/2025/07/10/psalm-v7-up-to-10x-performance/).


Announcing the public beta of Psalm v7!

[Psalm](https://psalm.dev) is one of the biggest and most powerful PHP Static analysis tools, featuring exclusive features like [security analysis](https://psalm.dev/docs/security_analysis/), and [Psalm v7](https://github.com/vimeo/psalm/releases/tag/7.0.0-beta10) brings **huge** performance improvements to [security analysis](https://psalm.dev/docs/security_analysis/), up to **10x** thanks to a full refactoring of both the internal representation of taints, and optimization of the graph resolution logic.

A major new feature was also added: **combined analysis**!  
Combined analysis, enabled by default in Psalm v7, allows running normal analysis, security analysis and dead code analysis all at the same time, within a single run, greatly reducing overall runtimes!

Future beta releases will also **enable taint analysis by default**, given that now it can be run alongside normal analysis.

Psalm v7 also brings performance improvements to dead code analysis, and fixes for `list` types.

Even more performance improvements and new features will be released soon!