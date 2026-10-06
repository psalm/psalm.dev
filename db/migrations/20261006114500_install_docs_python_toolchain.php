<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Installs the pinned docs toolchain (requirements.txt) into a virtualenv and
 * rebuilds the docs with it.
 *
 * mkdocs isn't managed by composer, so the server kept building the docs with
 * whatever mkdocs was on PATH: mkdocs 1.1 with Markdown 3.2.1. That version
 * double-escapes code spans in headings, so "@psalm-taint-unescape
 * <taint-type>" rendered as "&lt;taint-type&gt;", and it can't render the
 * mkdocs-material 9 theme this repo now ships.
 *
 * Deploys run "phinx migrate" (see composer.json), so this runs on the next
 * update from master. Both steps are idempotent, but phinx records migrations
 * as run and won't repeat them: when the pins in requirements.txt change, add
 * a new migration rather than editing this one.
 */
final class InstallDocsPythonToolchain extends AbstractMigration
{
    /** pymdown-extensions 12.1 requires Python 3.10 or newer. */
    private const MIN_PYTHON = '3.10';

    /** Checked in order; the first one new enough wins. */
    private const INTERPRETERS = [
        'python3.13',
        'python3.12',
        'python3.11',
        'python3.10',
        'python3',
    ];

    public function up(): void
    {
        $root = dirname(__DIR__, 2);
        $venv = $root . '/.venv-docs';

        $this->execute_shell([$this->findPython(), '-m', 'venv', $venv], $root);
        $this->execute_shell(
            [$venv . '/bin/pip', 'install', '--disable-pip-version-check', '-r', $root . '/requirements.txt'],
            $root,
        );
        $this->execute_shell([$venv . '/bin/mkdocs', 'build'], $root);

        $this->getOutput()->writeln('<info>Docs toolchain installed in ' . $venv . ', docs rebuilt.</info>');
    }

    public function down(): void
    {
        // The virtualenv stays: removing it would leave the server unable to
        // build the docs, and it holds nothing that requirements.txt can't
        // recreate.
    }

    /**
     * Path to the newest supported interpreter.
     */
    private function findPython(): string
    {
        foreach (self::INTERPRETERS as $name) {
            $path = trim((string) shell_exec('command -v ' . escapeshellarg($name) . ' 2>/dev/null'));

            if ($path === '') {
                continue;
            }

            $version = trim((string) shell_exec(
                escapeshellarg($path)
                . ' -c ' . escapeshellarg('import sys; print("%d.%d" % sys.version_info[:2])')
                . ' 2>/dev/null',
            ));

            if ($version !== '' && version_compare($version, self::MIN_PYTHON, '>=')) {
                return $path;
            }
        }

        throw new RuntimeException(
            'No Python ' . self::MIN_PYTHON . ' or newer on PATH, cannot install the docs toolchain.',
        );
    }

    /**
     * Runs a fixed command, failing the migration if it does.
     *
     * @param list<string> $command
     */
    private function execute_shell(array $command, string $cwd): void
    {
        $quoted = implode(' ', array_map('escapeshellarg', $command));
        $this->getOutput()->writeln('<comment>$ ' . $quoted . '</comment>');

        exec('cd ' . escapeshellarg($cwd) . ' && ' . $quoted . ' 2>&1', $output, $status);

        if ($status !== 0) {
            throw new RuntimeException(
                'Command failed with status ' . $status . ': ' . $quoted . "\n" . implode("\n", $output),
            );
        }
    }
}
