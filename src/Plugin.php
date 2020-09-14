<?php

namespace PsalmDotOrg;

/** @psalm-immutable */
final class Plugin
{
    public $name;
    public $description;
    public $monthly_downloads;

    private function __construct(string $name, string $description, int $monthly_downloads)
    {
        $this->name = $name;
        $this->description = $description;
        $this->monthly_downloads = $monthly_downloads;
    }

    public static function fromRepoEntry(array $entry): self
    {
        return new self(
            $entry['name'],
            $entry['description'],
            $entry['downloads']['monthly']
        );
    }
}
