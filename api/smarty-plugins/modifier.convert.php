<?php

function smarty_modifier_convert($price, $currency_id = null, $format = true)
{
    require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
    $simpla = new Simpla();

    return $simpla->money->convert($price, $currency_id, $format);
}
