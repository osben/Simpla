<?php

function smarty_modifier_plural($number, $singular, $plural1, $plural2=null)
{
    $number = abs($number);
    if (!empty($plural2)) {
        $p1 = $number % 10;
        $p2 = $number % 100;
        if ($number == 0) {
            return $plural1;
        }
        if ($p1 == 1 && !($p2 >= 11 && $p2 <= 19)) {
            return $singular;
        } elseif ($p1 >= 2 && $p1 <= 4 && !($p2 >= 11 && $p2 <= 19)) {
            return $plural2;
        } else {
            return $plural1;
        }
    } else {
        if ($number == 1) {
            return $singular;
        } else {
            return $plural1;
        }
    }
}
