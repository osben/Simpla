<?php

function smarty_function_get_brands($params, &$smarty)
{
    if (!isset($params['visible'])) {
        $params['visible'] = 1;
    }
    if (!empty($params['var'])) {
        require_once('./api/Simpla.php');
        $simpla = new Simpla();

        $smarty->assign($params['var'], $simpla->brands->get_brands($params));
    }
}
