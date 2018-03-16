<?php

function smarty_modifier_date($date, $format = null)
{
    require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
    $simpla = new Simpla();

    if (empty($date)) {
        $date = date("Y-m-d");
    }
    return date(empty($format)?$simpla->settings->date_format:$format, strtotime($date));
}
