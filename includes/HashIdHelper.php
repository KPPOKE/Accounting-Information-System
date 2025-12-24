<?php

class HashIdHelper
{
    private static $alphabet = 'wr58d9e2q1yau6j4nm7zk3xhp0sbgvtfc'; 
    private static $length = 5;

    public static function encode($number)
    {
        if (!is_numeric($number)) {
            return false;
        }

        $number = (int)$number;
        $output = '';
        $alphaLength = strlen(self::$alphabet);

        do {
            $remainder = $number % $alphaLength;
            $output = self::$alphabet[$remainder] . $output;
            $number = floor($number / $alphaLength);
        } while ($number > 0);
        
        return $output;
    }

    public static function decode($string)
    {
        if (empty($string)) {
            return false;
        }

        $number = 0;
        $alphaLength = strlen(self::$alphabet);
        
        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];
            $position = strpos(self::$alphabet, $char);
            
            if ($position === false) {
                return false;
            }

            $number = ($number * $alphaLength) + $position;
        }

        return $number;
    }
}
