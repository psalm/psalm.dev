<?php

namespace PsalmDotOrg;

/** @psalm-immutable */
final class PluginCatalog
{
    /**
     * @param list<Plugin> $plugins sorted by monthly downloads; abandoned and untagged packages excluded
     */
    public function __construct(
        public readonly int $psalm_major,
        public readonly int $abandoned_count,
        public readonly int $unreleased_count,
        public readonly array $plugins,
    ) {
    }

    public static function empty(): self
    {
        return new self(0, 0, 0, []);
    }

    /** @param array<string, mixed> $data as written by json_encode() of this class */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['psalm_major'],
            $data['abandoned_count'],
            $data['unreleased_count'],
            array_values(array_map([Plugin::class, 'fromArray'], $data['plugins'])),
        );
    }
}
