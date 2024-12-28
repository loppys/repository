<?php

namespace Vengine\Libraries\Repository\Helpers;

class ConvertCaseHelper
{
    public static function snakeCaseToCamelCase($string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    public static function camelCaseToSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
