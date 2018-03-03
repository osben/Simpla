<?php

function smarty_modifier_time($date, $format = null)
{
    return date(empty($format)?'H:i':$format, strtotime($date));
}
