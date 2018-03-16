<?php

function smarty_function_get_posts($params, &$smarty)
{
    if (!isset($params['visible'])) {
        $params['visible'] = 1;
    }
    if (!empty($params['var'])) {
        require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
        $simpla = new Simpla();

        $smarty->assign($params['var'], $simpla->blog->get_posts($params));
    }
}
