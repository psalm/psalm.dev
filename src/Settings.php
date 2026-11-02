<?php declare(strict_types=1);

namespace PsalmDotOrg;

use function array_keys;
use function array_map;
use function array_filter;
use function intval;

final class Settings 
{
    private const AVAILABLE_SETTINGS = [
        'unused_variables' => [true, 'Detect unused variables and parameters'],
        'disable_var_parsing' => [false, 'Disable parsing of <code>@var</code> in variable assignments (still enabled for properties) for better type accuracy'],
        'unused_methods' => [false, 'Detect unused classes and methods'],
        'memoize_properties' => [true, 'Memoize property assignments'],
        'memoize_method_calls' => [false, 'Memoize simple method calls'],
        'check_throws' => [false, 'Check for <code>@throws</code> docblock'],
        'strict_internal_functions' => [false, 'Report <code>false</code> and <code>null</code> returned by built-in functions'],
        'restrict_return_types' => [false, 'Force return types to be as tight as possible'],
        'use_phpdoc_without_magic_call' => [false, 'Use PHPDoc methods and properties without magic call.'],
        'ensure_override_attribute' => [false, 'Ensure methods marked as override are actually overriding something'],
        'ensure_array_offsets_exist' => [true, 'Require array offsets to be known to exist (<code>ensureArrayStringOffsetsExist</code>, <code>ensureArrayIntOffsetsExist</code>)'],
        'find_unused_psalm_suppress' => [false, 'Report unused <code>@psalm-suppress</code> annotations'],
    ];

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::AVAILABLE_SETTINGS);
    }

    /** @return array<string, bool> */
    public static function defaultValues(): array
    {
        return array_map(fn($row) => $row[0], self::AVAILABLE_SETTINGS);
    }

    /**
     * Decodes the BIT(1) setting columns of a `codes` row; mysqlnd returns them as ints or digit strings.
     *
     * @param array<string, mixed> $row
     * @return array<string, bool>
     */
    public static function fromRow(array $row): array
    {
        $settings = [];

        foreach (self::names() as $name) {
            $settings[$name] = (bool) intval($row[$name]);
        }

        return $settings;
    }

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return array_filter(array_map(fn($row) => $row[1], self::AVAILABLE_SETTINGS));
    }
}
