<?php

/**
 * Simpla CMS
 *
 * @copyright	2017 Denis Pikusov
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
        $this->smarty->addPluginsDir($this->config->root_dir.'/api/smarty-plugins');

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

}
