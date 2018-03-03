<?php

function smarty_modifier_cut($array, $num=1)
{
    if ($num>=0) {
        return array_slice($array, $num, count($array)-$num, true);
    } else {
        return array_slice($array, 0, count($array)+$num, true);
    }
}
