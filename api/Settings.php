<?php

/**
 * Simpla CMS
 *
 * @copyright	2016 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */


require_once('Simpla.php');

/**
 * Class Settings
 *
 *
 * Настройки сайта
 * @property string $site_name          Имя сайта
 * @property string $company_name       Имя компании
 * @property string $date_format        Формат даты
 * @property string $admin_email        Email для восстановления пароля
 * Оповещения
 * @property string $order_email - Email для оповещение о заказах
 * @property string $comment_email - Email для оповещение о комментариях
 * @property string $notify_from_email - Обратный адрес оповещений
 * Формат цены
 * @property string $decimals_point - Разделитель копеек
 * @property string $thousands_separator - Разделитель тысяч
 * Настройки каталога
 * @property string $products_num - Товаров на странице сайта
 * @property string $products_num_admin - Товаров на странице админки
 * @property string $max_order_amount - Максимум товаров в заказе
 * @property string $units - Единицы измерения товаров
 * Изображения товаров
 * @property string $watermark_offset_x - Горизонтальное положение водяного знака
 * @property string $watermark_offset_y - Вертикальное положение водяного знака
 * @property string $watermark_transparency - Прозрачность знака (больше — прозрачней)
 * @property string $images_sharpen - Резкость изображений (рекомендуется 20%)
 *
 * @property string $theme -
 * @property string $last_1c_orders_export_date -
 * @property string $license -
 *
 * @property string $pz_server
 * @property string $pz_password
 * @property string $pz_phones
 */

class Settings extends Simpla
{
    /**
     * @var array $vars
     */
    private $vars = array();

    public function __construct()
    {
        parent::__construct();

        // Выбираем из базы настройки
        $this->db->query('SELECT name, value FROM __settings');

        // и записываем их в переменную
        foreach ($this->db->results() as $result) {
            if (!($this->vars[$result->name] = @unserialize($result->value))) {
                $this->vars[$result->name] = $result->value;
            }
        }
    }

    public function __get($name)
    {
        if (isset($this->vars[$name])) {
            return $this->vars[$name];
        } elseif ($res = parent::__get($name)) {
            return $res;
        } else {
            return null;
        }
    }

    public function __set($name, $value)
    {
        $this->vars[$name] = $value;

        if (is_array($value)) {
            $value = serialize($value);
        } else {
            $value = (string) $value;
        }

        $this->db->query('SELECT COUNT(*) as count FROM __settings WHERE name=?', $name);

        if ($this->db->result('count')>0) {
            $this->db->query('UPDATE __settings SET value=? WHERE name=?', $value, $name);
        } else {
            $this->db->query('INSERT INTO __settings SET value=?, name=?', $value, $name);
        }
    }

    public function __isset($name)
    {
        return isset($this->vars[$name]);
    }
}
