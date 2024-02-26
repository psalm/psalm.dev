<?php declare(strict_types=1);

namespace PsalmDotOrg;

use function array_keys;
use function array_map;
use function array_filter;

final class Settings 
{
    private const AVAILABLE_SETTINGS = [
        'unused_variables' => [true, 'Detect unused variables and parameters'],
        'unused_methods' => [false, 'Detect unused classes and methods'],
        'memoize_properties' => [true, 'Memoize property assignments'],
        'memoize_method_calls' => [false, 'Memoize simple method calls'],
        'check_throws' => [false, 'Check for <code>@throws</code> docblock'],
        // it's a hidden setting
        'strict_internal_functions' => [false, ''],
        'restrict_return_types' => [false, 'Force return types to be as tight as possible'],
        'use_phpdoc_without_magic_call' => [false, 'Use PHPDoc methods and properties without magic call.'],
        'ensure_override_attribute' => [false, 'Ensure methods marked as override are actually overriding something']
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

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return array_filter(array_map(fn($row) => $row[1], self::AVAILABLE_SETTINGS));
    }
}
