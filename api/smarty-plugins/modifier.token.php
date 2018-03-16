<?php

function smarty_modifier_token($text)
{
    require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
    $simpla = new Simpla();

    return $simpla->config->token($text);
}
