<?php declare(strict_types=1);

namespace PsalmDotOrg;

use function array_keys;
use function array_map;
use function array_filter;
use function intval;
use function strtolower;

final class Settings 
{
    /** name => [default, description, the psalm.xml option it maps to (configuration docs anchor)] */
    private const AVAILABLE_SETTINGS = [
        'unused_variables' => [true, 'Detect unused variables and parameters', 'findUnusedVariablesAndParams'],
        'disable_var_parsing' => [false, 'Disable parsing of <code>@var</code> in variable assignments (still enabled for properties) for better type accuracy', 'disableVarParsing'],
        'unused_methods' => [false, 'Detect unused classes and methods', 'findUnusedCode'],
        'memoize_properties' => [true, 'Memoize property assignments', 'rememberPropertyAssignmentsAfterCall'],
        'memoize_method_calls' => [false, 'Memoize simple method calls', 'memoizeMethodCallResults'],
        'check_throws' => [false, 'Check for <code>@throws</code> docblock', 'checkForThrowsDocblock'],
        'strict_internal_functions' => [false, 'Report <code>false</code> and <code>null</code> returned by built-in functions', 'ignoreInternalFunctionFalseReturn'],
        'restrict_return_types' => [false, 'Force return types to be as tight as possible', 'restrictReturnTypes'],
        'use_phpdoc_without_magic_call' => [false, 'Use PHPDoc methods and properties without magic call.', 'usePhpDocMethodsWithoutMagicCall'],
        'ensure_override_attribute' => [false, 'Ensure methods marked as override are actually overriding something', 'ensureOverrideAttribute'],
        'ensure_array_offsets_exist' => [true, 'Require array offsets to be known to exist (<code>ensureArrayStringOffsetsExist</code>, <code>ensureArrayIntOffsetsExist</code>)', 'ensureArrayStringOffsetsExist'],
        'find_unused_psalm_suppress' => [false, 'Report unused <code>@psalm-suppress</code> annotations', 'findUnusedPsalmSuppress'],
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

    /**
     * Links to the configuration docs, keyed by setting name.
     *
     * @return array<string, string>
     */
    public static function docsLinks(): array
    {
        return array_map(
            fn($row) => '/docs/running_psalm/configuration/#' . strtolower($row[2]),
            self::AVAILABLE_SETTINGS
        );
    }

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return array_filter(array_map(fn($row) => $row[1], self::AVAILABLE_SETTINGS));
    }
}
