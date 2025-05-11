<!--
  title: Psalm v6 Deep Dive: Copy-on-Write + dynamic task dispatching
  date: 2025-05-11 18:00
  author: Daniil Gentili
-->

[Cross-posted from Daniil Gentili's blog &raquo;](https://blog.daniil.it).

Psalm is one of the biggest and most powerful PHP Static analysis tools, featuring exclusive features like [security analysis](https://psalm.dev/docs/security_analysis/).  

In [Psalm 6.1](https://github.com/vimeo/psalm/releases/tag/6.1.0), I implemented a major refactoring of multithreaded mode (automatically enabled on Linux/Mac OS) based on [amphp/parallel](https://github.com/amphp/parallel), which greatly reduced analysis speeds!  

But why was it so effective? To understand, one must first understand that in the vast majority of PHP multithreaded analysis tools, jobs are distributed *statically* between threads on startup, which means that towards the end of the analysis, a lot of workers just sit there doing nothing, just waiting for the other workers processing bigger and heavier files to finish.  

However, the new multithreaded mode now allows **Psalm** to *dynamically* distribute jobs to workers immediately, as soon as they finish processing their current task, reducing idle worker time and maximizing CPU usage, thus reducing the overall runtime!  

Implementation wasn't as easy as just plugging in [amphp/parallel](https://github.com/amphp/parallel), because Psalm relies heavily on the copy-on-write semantics of fork(): indeed, Psalm's multithreaded mode was quite fast even before the refactoring because it doesn't have to copy all type information to all workers when spawning them, as when workers are spawned using the fork() syscall, the entire memory is **not** copied to the forked process.  

Instead, it is copied only when a memory page is modified by the forked process, which means that unless workers start modifying large amounts of type information (which usually happens pretty rarely, as most of that data is immutable after Psalm's scan phase), most of the memory is not copied, leading to large performance improvements.  

[amphp/parallel](https://github.com/amphp/parallel) does not support using fork() to spawn workers out of the box, however I managed to add support using a custom context class (taking care to avoid some edge cases around reused file descriptors, which can cause issues with the event loop).  

The maintainer of amphp was kind enough to [begin integration of Psalm's fork context inside of parallel itself](https://github.com/amphp/parallel/pull/212) after I pinged him, which means amphp users will soon be able to make use of Psalm's fork context to improve worker spawning performance with copy-on-write fork() semantics.  

This release also adds an additional check to ensure VM overcommitting (the feature which allows copy-on-write optimizations) is enabled in the OS when running Psalm, by ensuring that the `vm.overcommit_memory` kernel setting is always set to 1.  

~~~

This post is the first of a series of **technical deep dives** into Psalm v6's performance improvements, which will be released over the next weeks, [click here](https://blog.daniil.it/category/psalm/deep-dive-psalm-v6/) to see all the other posts in the series, and [subscribe to the newsletter](https://blog.daniil.it/newsletter/) to always stay up to date on the latest Psalm news and developments!  
