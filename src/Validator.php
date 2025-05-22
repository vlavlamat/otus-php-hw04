<?php

namespace App;

class Validator
{
    public static function validate(string $input): bool
    {
        if (empty($input)) {
            throw new \InvalidArgumentException('Empty input.');
        }

        $balance = 0;
        $length = strlen($input);

        for ($i = 0; $i < $length; $i++) {
            if ($input[$i] === '(') {
                $balance++;
            } elseif ($input[$i] === ')') {
                $balance--;
                if ($balance < 0) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return $balance === 0;
    }
}
