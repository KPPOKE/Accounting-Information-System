<?php

class HashIdHelper
{
    // Alphabet shuffled for obfuscation. Change this string to change the generated hashes.
    private static $alphabet = 'wr58d9e2q1yau6j4nm7zk3xhp0sbgvtfc'; 
    private static $length = 5; // Minimum length of hash

    /**
     * Encode an integer ID into a string hash
     */
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

        // Pad with random characters if too short (simplified logic)
        // Note: For true reversibility with padding, more complex logic is needed.
        // For this simple version, we won't force padding to keep it strictly bijective and simple.
        
        return $output;
    }

    /**
     * Decode a string hash back into an integer ID
     */
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
                return false; // Invalid character found
            }

            $number = ($number * $alphaLength) + $position;
        }

        return $number;
    }
}
