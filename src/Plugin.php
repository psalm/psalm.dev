<?php

namespace PsalmDotOrg;

/** @psalm-immutable */
final class Plugin
{
    /**
     * @param list<int>|null $psalm_majors Psalm majors supported by at least one stable release,
     *                                     null when no release declares a Psalm or plugin API requirement
     * @param list<string> $libraries Packages whose presence makes Psalm enable a bundled plugin
     */
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly int $monthly_downloads,
        public readonly int $stars,
        public readonly ?string $last_release,
        public readonly ?array $psalm_majors,
        public readonly bool $bundled,
        public readonly array $libraries,
    ) {
    }

    /** @param array<string, mixed> $data an entry as written by json_encode() of this class */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }

    public function maxPsalmMajor(): ?int
    {
        return $this->psalm_majors === null || $this->psalm_majors === [] ? null : max($this->psalm_majors);
    }
}
