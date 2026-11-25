<!--
  title: Psalm for AI agents: an MCP server, and plugins for Claude Code and OpenCode
  date: 2026-11-26
  author: Daniil Gentili
-->

Coding agents write a lot of PHP now. Psalm can tell them where that code is wrong, and with Psalm 7.22 it tells them
directly: Psalm ships an [MCP](https://modelcontextprotocol.io) server, `psalm-mcp`, and plugins that set it up for
**Claude Code** and **OpenCode** with one command.

![An agent asks Psalm about a line with psalm_inspect and psalm_check](https://psalm.dev/assets/articles/psalm-for-ai-agents.png)

**New: the Psalm [newsletter](https://psalm.dev/newsletter).** Get posts like this one by email:

<!-- newsletter -->

## Install

Psalm has to be installed in the project, as the plugins run the project's own Psalm:

```bash
composer require --dev vimeo/psalm
vendor/bin/psalm --init
```

**Claude Code:**

```text
/plugin marketplace add vimeo/psalm#agent-plugins
/plugin install psalm@psalm
```

**OpenCode:**

```bash
opencode plugin add 'github:vimeo/psalm#agent-plugins::path:psalm/opencode'
```

**Codex, Cursor, Gemini CLI and any other MCP client** start `php vendor/bin/psalm-mcp` in the project directory:

```bash
codex mcp add psalm -- php vendor/bin/psalm-mcp
```

The plugins are published on each Psalm release. The Claude Code plugin also starts Psalm's
[language server](https://psalm.dev/docs/running_psalm/language_server/), so Claude sees Psalm's errors after each
edit, and both plugins install the Psalm [agent skill](https://github.com/vimeo/psalm/tree/master/skills/psalm),
which teaches the agent how to run Psalm and fix what it reports.
[Using Psalm with AI agents](https://psalm.dev/docs/running_psalm/ai_agents/) covers the setup of each agent.

## The tools

Given this `src/Greeter.php`:

```php
<?php
namespace Acme\SampleProject;

final class Greeter
{
    public function name(?User $user): string
    {
        return $user->getName();
    }
}
```

**`psalm_check`** returns the issues of the files the agent changed, in a fraction of a second:

```text
ERROR: PossiblyNullReference - src/Greeter.php:8:23 - Cannot call method getName on possibly null value (CWE-476) (see https://psalm.dev/083)
        return $user->getName();
```

Without paths, it runs Psalm on the whole project, like CI does, with unused code detection and security analysis
across files: the check an agent should pass before it says it's done.

**`psalm_inspect`** shows what Psalm knows about the code at a position: the type it infers for each expression, and
the declaration of what a name refers to. The agent points at the code with a column, or with the text it sees:

```text
psalm_inspect  file: src/Greeter.php, line: 8, text: getName

`getName()` at 8:23
  type: string

`getName` at 8:23
  symbol: Acme\SampleProject\User::getName, declared at src/User.php:14:21
    public function getName(): string
```

So an agent can find out why Psalm reports an issue, instead of guessing at a type or adding a `@var` to silence it.

**`psalm_references`** lists where a class, function, method, property or constant is used:

```text
1 reference to Acme\SampleProject\User::getName:
src/Greeter.php:8:23 return $user->getName();
```

**`psalm_explain`** returns the documentation of an issue type, with an example and how to fix it.

## How it works

`psalm-mcp` is built on Psalm's language server: it keeps the codebase in memory and analyses again only what changed.
Agents edit files on disk, not in an editor, so before each tool call the server reloads the project files that
changed since the last one, and the next check sees the edit.

Building it also fixed a language server bug: after another file was analysed, hover and go-to-definition found nothing
in a file with only functions or top-level code ([#15003](https://github.com/vimeo/psalm/pull/15003)).

## Try it

```bash
composer require --dev vimeo/psalm:^7.22 -W
```

Then install the plugin of your agent, and ask it to fix the issues Psalm reports. Tell us how it goes on
[Discord](https://discord.gg/RwH8rEpxt9), and report problems on [GitHub](https://github.com/vimeo/psalm/issues).
