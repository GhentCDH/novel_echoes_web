<?php

namespace App\Controller;

enum SearchMode: string
{
    case SEARCH_AGGREGATE = "search_aggregate";
    case SEARCH = "search";
    case AGGREGATE = "aggregate";

    public static function fromName($name)
    {
        return defined("self::$name") ? constant("self::$name") : false;
    }

    public static function fromValue($value)
    {
        return match ($value) {
            "search_aggregate" => self::SEARCH_AGGREGATE,
            "search" => self::SEARCH,
            "aggregate" => self::AGGREGATE,
            default => false,
        };
    }
}