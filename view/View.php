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

}
