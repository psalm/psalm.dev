<!--
  title: It’s time to explain some things
  date: 2020-03-23 07:10:00
  author: Matt Brown
  author_link: https://twitter.com/mattbrowndev
-->

Creating Psalm has involved learning a lot about PHP and about type systems in general, making me a better developer in the process.

For some of its users, though, Psalm can seem mysterious – I often see commit messages along the lines of “make Psalm happy”, like it’s some sort of vengeful deity.

Instead, Psalm should provide clarity – ideally helping developers improve their understanding of PHP and programming languages in general. So the latest version now includes links (in its default output) that you can follow to understand why Psalm thinks something is problematic.

The links look like this:

> ERROR: InvalidArgument - somefile.php:8:19 - Argument 1 of getAttribute expects string, int provided (see&nbsp;[https://psalm.dev/004](https://psalm.dev/004))

Clicking that link takes you to a dedicated help document that explains the issue in more detail, and provides potential fixes.

As with everything related to Psalm, all documentation is open-source. If you see something that could be improved, feel free to create a pull request!
