<?php

/**
 * Simpla CMS
 *
 * @copyright	2017 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('api/Simpla.php');

class View extends Simpla
{
    /* Смысл класса в доступности следующих переменных в любом View */
    public $currency;
    public $currencies;
    public $user;
    public $group;
    public $page;

    /* Класс View похож на синглтон, храним статически его инстанс */
    private static $view_instance;

    public function __construct()
    {
        parent::__construct();

        // Если инстанс класса уже существует - просто используем уже существующие переменные
        if (self::$view_instance) {
            $this->currency        = &self::$view_instance->currency;
            $this->currencies    = &self::$view_instance->currencies;
            $this->user            = &self::$view_instance->user;
            $this->group        = &self::$view_instance->group;
            $this->page            = &self::$view_instance->page;
        } else {
            // Сохраняем свой инстанс в статической переменной,
            // чтобы в следующий раз использовать его
            self::$view_instance = $this;

            // Все валюты
            $this->currencies = $this->money->get_currencies(array('enabled'=>1));

            // Выбор текущей валюты
            if ($currency_id = $this->request->get('currency_id', 'integer')) {
                $_SESSION['currency_id'] = $currency_id;
                header("Location: ".$this->request->url(array('currency_id'=>null)));
            }

            // Берем валюту из сессии
            if (isset($_SESSION['currency_id'])) {
                $this->currency = $this->money->get_currency($_SESSION['currency_id']);
            }
            // Или первую из списка
            else {
                $this->currency = reset($this->currencies);
            }

            // Пользователь, если залогинен
            if (isset($_SESSION['user_id'])) {
                $u = $this->users->get_user(intval($_SESSION['user_id']));
                if ($u && $u->enabled) {
                    $this->user = $u;
                    $this->group = $this->users->get_group($this->user->group_id);
                }
            }

            // Текущая страница (если есть)
            $subdir = substr(dirname(dirname(__FILE__)), strlen($_SERVER['DOCUMENT_ROOT']));
            $page_url = trim(substr($_SERVER['REQUEST_URI'], strlen($subdir)), "/");
            if (strpos($page_url, '?') !== false) {
                $page_url = substr($page_url, 0, strpos($page_url, '?'));
            }
            $this->page = $this->pages->get_page((string)$page_url);
            $this->design->assign('page', $this->page);

            // Передаем в дизайн то, что может понадобиться в нем
            $this->design->assign('currencies',  $this->currencies);
            $this->design->assign('currency',    $this->currency);
            $this->design->assign('user',        $this->user);
            $this->design->assign('group',       $this->group);

            $this->design->assign('config',      $this->config);
            $this->design->assign('settings',    $this->settings);

            // Настраиваем плагины для смарти
            $this->design->smarty->registerPlugin("function", "get_posts",               array($this, 'get_posts_plugin'));
            $this->design->smarty->registerPlugin("function", "get_brands",              array($this, 'get_brands_plugin'));
            $this->design->smarty->registerPlugin("function", "get_browsed_products",    array($this, 'get_browsed_products'));
            $this->design->smarty->registerPlugin("function", "get_featured_products",   array($this, 'get_featured_products_plugin'));
            $this->design->smarty->registerPlugin("function", "get_new_products",        array($this, 'get_new_products_plugin'));
            $this->design->smarty->registerPlugin("function", "get_discounted_products", array($this, 'get_discounted_products_plugin'));
        }
    }

    /**
     *
     * Отображение
     *
     */
    public function fetch()
    {
        return false;
    }

    /**
     *
     * Плагины для смарти
     *
     */
    public function get_posts_plugin($params, &$smarty)
    {
        if (!isset($params['visible'])) {
            $params['visible'] = 1;
        }
        if (!empty($params['var'])) {
            $smarty->assign($params['var'], $this->blog->get_posts($params));
        }
    }

    public function get_brands_plugin($params, $smarty)
    {
        if (!isset($params['visible'])) {
            $params['visible'] = 1;
        }
        if (!empty($params['var'])) {
            $smarty->assign($params['var'], $this->brands->get_brands($params));
        }
    }

    public function get_browsed_products($params, &$smarty)
    {
        if (!empty($_COOKIE['browsed_products'])) {
            $browsed_products_ids = explode(',', $_COOKIE['browsed_products']);
            $browsed_products_ids = array_reverse($browsed_products_ids);

            if (isset($params['limit'])) {
                $params['id'] = array_slice($browsed_products_ids, 0, $params['limit']);
            }
            if (!isset($params['visible'])) {
                $params['visible'] = 1;
            }

            $products = $this->products->get_products_compile($params);

            $smarty->assign($params['var'], $products);
        }
    }


    public function get_featured_products_plugin($params, &$smarty)
    {
        if (!isset($params['visible'])) {
            $params['visible'] = 1;
        }
        $params['featured'] = 1;
        if (!empty($params['var'])) {
            $products = $this->products->get_products_compile($params);

            $smarty->assign($params['var'], $products);
        }
    }


    public function get_new_products_plugin($params, &$smarty)
    {
        if (!isset($params['visible'])) {
            $params['visible'] = 1;
        }
        if (!isset($params['sort'])) {
            $params['sort'] = 'created';
        }
        if (!empty($params['var'])) {
            $products = $this->products->get_products_compile($params);

            $smarty->assign($params['var'], $products);
        }
    }


    public function get_discounted_products_plugin($params, &$smarty)
    {
        if (!isset($params['visible'])) {
            $params['visible'] = 1;
        }
        $params['discounted'] = 1;
        if (!empty($params['var'])) {
            $products = $this->products->get_products_compile($params);

            $smarty->assign($params['var'], $products);
        }
    }
}
