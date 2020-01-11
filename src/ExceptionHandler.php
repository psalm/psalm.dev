<?php

declare(strict_types=1);

namespace PsalmDotOrg;

final class ExceptionHandler
{
    /**
     * @psalm-return never-return
     */
    public static function json(\Throwable $throwable): void
    {
        $root_sources = implode('/', array_slice(explode('/', __DIR__), 0, -1));
        $message = str_replace($root_sources, '', $throwable->getFile() . ': ' . $throwable->getMessage());
        echo json_encode(
            [
                'error' => [
                    'message' => $message,
                    'line_from' => $throwable->getLine(),
                    'type' => 'psalm_error'
                ]
            ],
            JSON_THROW_ON_ERROR
        );
        exit;
    }
}
