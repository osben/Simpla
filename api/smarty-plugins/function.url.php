<?php

function smarty_function_url($params, $template)
{
    require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
    $simpla = new Simpla();

    if (is_array(reset($params))) {
        return $simpla->request->url(reset($params));
    } else {
        return $simpla->request->url($params);
    }
}
