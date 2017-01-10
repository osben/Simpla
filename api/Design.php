<?php

/**
 * Simpla CMS
 *
 * @copyright	2016 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once(dirname(__FILE__).'/'.'Simpla.php');
require_once(dirname(dirname(__FILE__)).'/Smarty/libs/Smarty.class.php');

/**
 * Class Design
 * @property Smarty $smarty
 */

class Design extends Simpla
{
    public $smarty;
    public $compile_dir;
    public $template_dir;
    public $cache_dir = 'cache'; // TODO вынести в config
    public $theme;

    /**
     * Design constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Создаем и настраиваем Смарти
        $this->smarty = new \Smarty();
        $this->smarty->compile_check = $this->config->smarty_compile_check;
        // TODO $this->smarty->force_compile = $this->config->force_compile;
        $this->smarty->caching = $this->config->smarty_caching;
        $this->smarty->cache_lifetime = $this->config->smarty_cache_lifetime;
        $this->smarty->debugging = $this->config->smarty_debugging;
        $this->smarty->error_reporting = E_ALL & ~E_NOTICE;

        // Берем тему из настроек
        $this->theme        = $this->settings->theme;
        $this->compile_dir  = $this->config->root_dir.'/compiled/'. $this->theme ;
        $this->template_dir = $this->config->root_dir.'/design/'. $this->theme .'/html';
        $this->cache_dir    = $this->config->root_dir.'/cache';

        $this->set_compiled_dir($this->compile_dir);
        $this->set_templates_dir($this->template_dir);

        if (!is_dir($this->config->root_dir.'/compiled')) {
            mkdir($this->config->root_dir.'/compiled', 0777);
        }

        // Создаем папку для скомпилированных шаблонов текущей темы
        if (!is_dir($this->compile_dir)) {
            mkdir($this->compile_dir, 0777);
        }

        $this->smarty->setCacheDir($this->cache_dir);

        $this->smarty->registerPlugin('modifier', 'resize',        array($this, 'resize_modifier'));
        $this->smarty->registerPlugin('modifier', 'crop',        array($this, 'crop_modifier'));
        $this->smarty->registerPlugin('modifier', 'token',        array($this, 'token_modifier'));
        $this->smarty->registerPlugin('modifier', 'plural',        array($this, 'plural_modifier'));
        $this->smarty->registerPlugin('function', 'url',        array($this, 'url_modifier'));
        $this->smarty->registerPlugin('modifier', 'first',        array($this, 'first_modifier'));
        $this->smarty->registerPlugin('modifier', 'cut',        array($this, 'cut_modifier'));
        $this->smarty->registerPlugin('modifier', 'date',        array($this, 'date_modifier'));
        $this->smarty->registerPlugin('modifier', 'time',        array($this, 'time_modifier'));
        $this->smarty->registerPlugin('function', 'api',        array($this, 'api_plugin'));

        if ($this->config->smarty_html_minify) {
            $this->smarty->loadFilter('output', 'trimwhitespace');
        }
    }

    /**
     * @param  array|string $tpl_var
     * @param  mixed $value
     * @param  boolean $nocache
     * @return Smarty_Internal_Data
     */
    public function assign($tpl_var, $value = null, $nocache = false)
    {
        return $this->smarty->assign($tpl_var, $value, $nocache);
    }

    /**
     * @param  string $template
     * @return string
     */
    public function fetch($template)
    {
        // Передаем в дизайн то, что может понадобиться в нем
        $this->assign('config',      $this->config);
        $this->assign('settings',    $this->settings);
        return $this->smarty->fetch($template);
    }

    /**
     * @param  string $compile_dir
     * @return void
     */
    public function set_compiled_dir($compile_dir)
    {
        $this->smarty->setCompileDir($compile_dir);
    }

    /**
     * @param  string $template_dir
     * @return void
     */
    public function set_templates_dir($template_dir)
    {
        $this->smarty->setTemplateDir($template_dir);
    }

    /**
     * @param  string $name
     * @return mixed
     */
    public function get_var($name)
    {
        return $this->smarty->getTemplateVars($name);
    }

    /**
     * Очистить кеш
     *
     * @return mixed
     */
    public function clear_cache()
    {
        $this->smarty->clearAllCache();
    }


    /**
     * @param  $filename
     * @param  int $width
     * @param  int $height
     * @param  bool $set_watermark
     * @return string
     */
    public function resize_modifier($filename, $width=0, $height=0, $set_watermark=false)
    {
        $resized_filename = $this->image->add_resize_params($filename, '', $width, $height, $set_watermark);
        $resized_filename_encoded = $resized_filename;

        if (substr($resized_filename_encoded, 0, 7) == 'http://' || substr($resized_filename_encoded, 0, 8) == 'https://') {
            $resized_filename_encoded = rawurlencode($resized_filename_encoded);
        }

        $resized_filename_encoded = rawurlencode($resized_filename_encoded);

        return $this->config->root_url.'/'.$this->config->resized_images_dir.$resized_filename_encoded.'?'.$this->config->token($resized_filename);
    }
    /**
     * TODO объединить resize_modifier и crop_modifier
     * @param  $filename
     * @param  int $width
     * @param  int $height
     * @param  bool $set_watermark
     * @return string
     */
    public function crop_modifier($filename, $width=0, $height=0, $set_watermark=false)
    {
        $resized_filename = $this->image->add_resize_params($filename, 'crop', $width, $height, $set_watermark);
        $resized_filename_encoded = $resized_filename;

        if (substr($resized_filename_encoded, 0, 7) == 'http://' || substr($resized_filename_encoded, 0, 8) == 'https://') {
            $resized_filename_encoded = rawurlencode($resized_filename_encoded);
        }

        $resized_filename_encoded = rawurlencode($resized_filename_encoded);

        return $this->config->root_url.'/'.$this->config->resized_images_dir.$resized_filename_encoded.'?'.$this->config->token($resized_filename);
    }
    /**
     * @param  string $text
     * @return string
     */
    public function token_modifier($text)
    {
        return $this->config->token($text);
    }

    /**
     * @param  $params
     * @return string
     */
    public function url_modifier($params)
    {
        if (is_array(reset($params))) {
            return $this->request->url(reset($params));
        } else {
            return $this->request->url($params);
        }
    }

    /**
     * @param  $number
     * @param  $singular
     * @param  $plural1
     * @param  null $plural2
     * @return null
     */
    public function plural_modifier($number, $singular, $plural1, $plural2=null)
    {
        $number = abs($number);
        if (!empty($plural2)) {
            $p1 = $number%10;
            $p2 = $number%100;
            if ($number == 0) {
                return $plural1;
            }
            if ($p1==1 && !($p2>=11 && $p2<=19)) {
                return $singular;
            } elseif ($p1>=2 && $p1<=4 && !($p2>=11 && $p2<=19)) {
                return $plural2;
            } else {
                return $plural1;
            }
        } else {
            if ($number == 1) {
                return $singular;
            } else {
                return $plural1;
            }
        }
    }

    /**
     * @param  array $params
     * @return mixed|false
     */
    public function first_modifier($params = array())
    {
        if (!is_array($params)) {
            return false;
        }
        return reset($params);
    }

    /**
     * @param  array $array
     * @param  int $num
     * @return array
     */
    public function cut_modifier($array, $num=1)
    {
        if ($num>=0) {
            return array_slice($array, $num, count($array)-$num, true);
        } else {
            return array_slice($array, 0, count($array)+$num, true);
        }
    }

    /**
     * @param  $date
     * @param  string $format
     * @return false|string
     */
    public function date_modifier($date, $format = null)
    {
        if (empty($date)) {
            $date = date("Y-m-d");
        }
        return date(empty($format)?$this->settings->date_format:$format, strtotime($date));
    }

    /**
     * @param  $date
     * @param  null $format
     * @return false|string
     */
    public function time_modifier($date, $format = null)
    {
        return date(empty($format)?'H:i':$format, strtotime($date));
    }

    /**
     * @param  array $params
     * @param  $smarty
     * @return false|mixed
     */
    public function api_plugin($params, &$smarty)
    {
        if (!isset($params['module']) || !isset($params['method'])) {
            return false;
        }

        $module = $params['module'];
        $method = $params['method'];
        $var = $params['var'];
        unset($params['module']);
        unset($params['method']);
        unset($params['var']);

        if (isset($params['_'])) {
            $res = $this->$module->$method($params['_']);
        } else {
            $res = $this->$module->$method($params);
        }
        $smarty->assign($var, $res);
    }
}
