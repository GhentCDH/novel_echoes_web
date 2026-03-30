<?php

namespace App\Helper;

class RomanNumeralParser
{
    private static array $romanValues = [
        'M'  => 1000,
        'CM' => 900,
        'D'  => 500,
        'CD' => 400,
        'C'  => 100,
        'XC' => 90,
        'L'  => 50,
        'XL' => 40,
        'X'  => 10,
        'IX' => 9,
        'V'  => 5,
        'IV' => 4,
        'I'  => 1,
    ];

    public static function toInteger(string $roman): int
    {
        $roman = strtoupper(trim($roman));
        $result = 0;
        $i = 0;
        $length = strlen($roman);

        while ($i < $length) {
            if ($i + 1 < $length && isset(self::$romanValues[$roman[$i] . $roman[$i + 1]])) {
                $result += self::$romanValues[$roman[$i] . $roman[$i + 1]];
                $i += 2;
            } else {
                $result += self::$romanValues[$roman[$i]] ?? 0;
                $i++;
            }
        }

        return $result;
    }
}
