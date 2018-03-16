<?php

function smarty_function_get_featured_products($params, &$smarty)
{
    if (!isset($params['visible'])) {
        $params['visible'] = 1;
    }
    $params['featured'] = 1;

    if (!empty($params['var'])) {

        require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
        $simpla = new Simpla();

        $products = $simpla->products->get_products_compile($params);

        $smarty->assign($params['var'], $products);
    }
}
