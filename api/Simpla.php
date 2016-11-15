<?php

/**
 * Simpla CMS
 *
 * @copyright	2016 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

/**
 * Основной класс Simpla для доступа к API Simpla
 *
 * Class Simpla
 * @property Config $config
 * @property Request $request
 * @property Database $db
 * @property Settings $settings
 * @property Design $design
 * @property Products $products
 * @property Variants $variants
 * @property Categories $categories
 * @property Brands $brands
 * @property Features $features
 * @property Money $money
 * @property Pages $pages
 * @property Blog $blog
 * @property Cart $cart
 * @property Image $image
 * @property Delivery $delivery
 * @property Payment $payment
 * @property Orders $orders
 * @property Users $users
 * @property Coupons $coupons
 * @property Comments $comments
 * @property Feedbacks $feedbacks
 * @property Notify $notify
 * @property Managers $managers
 */
class Simpla
{

    /**
     * Свойства - Классы API
     *
     * @var array
     */
    private $classes = array(
        'config'     => 'Config',
        'request'    => 'Request',
        'db'         => 'Database',
        'settings'   => 'Settings',
        'design'     => 'Design',
        'products'   => 'Products',
        'variants'   => 'Variants',
        'categories' => 'Categories',
        'brands'     => 'Brands',
        'features'   => 'Features',
        'money'      => 'Money',
        'pages'      => 'Pages',
        'blog'       => 'Blog',
        'cart'       => 'Cart',
        'image'      => 'Image',
        'delivery'   => 'Delivery',
        'payment'    => 'Payment',
        'orders'     => 'Orders',
        'users'      => 'Users',
        'coupons'    => 'Coupons',
        'comments'   => 'Comments',
        'feedbacks'  => 'Feedbacks',
        'notify'     => 'Notify',
        'managers'   => 'Managers'
    );

    // Созданные объекты
    private static $objects = array();

    /**
     * Конструктор оставим пустым, но определим его на случай обращения parent::__construct() в классах API
     *
     * Simpla constructor.
     */
    public function __construct()
    {
        //error_reporting(E_ALL);
    }

    /**
     * Магический метод, создает нужный объект API
     *
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        // Если такой объект уже существует, возвращаем его
        if (isset(self::$objects[$name])) {
            return(self::$objects[$name]);
        }

        // Если запрошенного API не существует - ошибка
        if (!array_key_exists($name, $this->classes)) {
            return null;
        }

        // Определяем имя нужного класса
        $class = $this->classes[$name];

        // Подключаем его
        include_once(dirname(__FILE__).'/'.$class.'.php');

        // Сохраняем для будущих обращений к нему
        self::$objects[$name] = new $class();

        // Возвращаем созданный объект
        return self::$objects[$name];
    }
    /*
     * Вспомогательные методы
     * TODO подумать над удобной реализацией
     */

    /**
     * Функция для смены кодировки строки
     *
     * @param  string $str
     * @param  $to_encoding
     * @param  $from_encoding
     * @param  bool $alt
     * @return bool|string
     */
    public function convert_str_encoding($str, $to_encoding, $from_encoding, $alt = false)
    {
        if (function_exists('iconv')) {
            $str = @iconv($from_encoding, $to_encoding, $str);
        } elseif (function_exists('mb_convert_encoding')) {
            $str = @mb_convert_encoding($str, $to_encoding, $from_encoding);
        } else {
            // TODO add сonverting Windows-1251 to UTF-8 and the reverse when no iconv and mb_convert_encoding
            return $alt ? $alt : $str;
        }

        return $str;
    }
}
