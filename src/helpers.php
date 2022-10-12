<?php

declare(strict_types=1);

if (!\function_exists('env')) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        if (\array_key_exists($key, $_ENV)) {
            $value = $_ENV[$key];
        } elseif (\array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        } else {
            return $default;
        }

        switch (\strtolower($value)) {
            case 'true':
            case '(true)':
                return true;

            case 'false':
            case '(false)':
                return false;

            case 'empty':
            case '(empty)':
                return '';

            case 'null':
            case '(null)':
                return null;
        }

        if (\str_starts_with($value, '"') && \str_ends_with($value, '"')) {
            return \substr($value, 1, -1);
        }

        return $value;
    }
}

if (!\function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return empty($needle) || \strpos($haystack, $needle) === 0;
    }
}

if (!\function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return empty($needle) || \substr($haystack, -\strlen($needle)) === $needle;
    }
}
