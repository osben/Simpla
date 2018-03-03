<?php

function smarty_modifier_token($text)
{
    require_once('./api/Simpla.php');
    $simpla = new Simpla();
    return $simpla->config->token($text);
}
