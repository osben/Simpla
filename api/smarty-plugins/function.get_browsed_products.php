<?php

function smarty_function_get_browsed_products($params, &$smarty)
{
    if (!empty($_COOKIE['browsed_products'])) {
        require_once('./api/Simpla.php');
        $simpla = new Simpla();

        $browsed_products_ids = explode(',', $_COOKIE['browsed_products']);
        $browsed_products_ids = array_reverse($browsed_products_ids);

        if (isset($params['limit'])) {
            $params['id'] = array_slice($browsed_products_ids, 0, $params['limit']);
        }
        if (!isset($params['visible'])) {
            $params['visible'] = 1;
        }

        $products = $simpla->products->get_products_compile($params);

        $smarty->assign($params['var'], $products);
    }
}
