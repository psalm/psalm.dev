At the moment Psalm is over five times as popular as it was a year ago. That's great for the project, but it's also led to an equivalent increase in feature requests and bug reports. Every year the number of new issues roughly doubles – 50 per month in 2018, 90 per month in 2019, 180 per month in mid-2020.

The more issues get created, the harder it is for me (and one or two other regular contributors) to address them all. I’m slightly worried about burning out, so I’m asking for your help.

## How you can help

If you know PHP, you can probably be useful. Psalm is written in PHP, and much of its analysis is reasonably straightforward. I’m always happy to explain the more complicated bits to anyone who’s interested.

Whether it’s adding a feature, fixing a bug, or just cleaning up the codebase a bit, every little helps.

### Where to get started

I’ve added a GitHub tag called [Easy problems](https://github.com/vimeo/psalm/issues?q=is%3Aissue+is%3Aopen+label%3A%22easy+problems%22) that should provide you some low-hanging fruit.

There’s also a [contributing guide](https://github.com/vimeo/psalm/blob/master/CONTRIBUTING.md) that tells you how to test your code and, if you need, [a short guide to Psalm’s internals](https://github.com/vimeo/psalm/blob/master/docs/how_psalm_works.md).

### Don’t be afraid!

One great thing about working on Psalm is that it’s _very_ hard to introduce any sort of type error in Psalm’s codebase. There are almost 5,000 PHPUnit tests, so the risk of you messing up (without the CI system noticing) is very small.

### Why static analysis is cool

If you need another reason to help, the problem space is just thoroughly interesting.

Day-to-day PHP programming involves solving concrete problems, but they're rarely very complex. Psalm, on the other hand, attempts to solve a pretty hard collection of problems, which then allows it to detect a ton of bugs in PHP code without actually executing that code.

There's a lot of interesting theory behind the things Psalm does, too. If you want you can go very deep, though you don't need to know really any theory to improve Psalm.

Lastly, working to improve static analysis tools will also make you a better PHP developer – it'll help you think more about how values flow through your program. 

## “Why can’t I pay you?”

Money’s great, but that's not what I'm after. There are a bunch of other PHP developers whose work I rely on, though – if you don't have time to support Psalm with code, you support Psalm indirectly by sponsoring these developers:

- [Sara Golemon](https://github.com/sponsors/sgolemon) - core PHP contributor & v8 release manager
- [Sebastian Bergmann](https://github.com/sponsors/sebastianbergmann) - creator of PHPUnit
- [Marco Pivetta](https://github.com/sponsors/Ocramius) - prolific userland developer who helps push Psalm forward

Lastly, if you work at a large company that uses Psalm regularly, consider encouraging your colleagues to contribute to its development!
