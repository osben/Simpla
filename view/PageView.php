<?php

/**
 * Simpla CMS
 *
 * @copyright	2017 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('View.php');

class PageView extends View
{
    public function fetch()
    {
        $url = $this->request->get('page_url', 'string');

        $page = $this->pages->get_page($url);

        // Отображать скрытые страницы только админу
        if (empty($page) || (!$page->visible && empty($_SESSION['admin']))) {
            return false;
        }

        $this->design->assign('page', $page);
        $this->design->assign('meta_title', $page->meta_title);
        $this->design->assign('meta_keywords', $page->meta_keywords);
        $this->design->assign('meta_description', $page->meta_description);

        if (file_exists($this->config->root_dir . '/design/' . $this->settings->theme . '/html/page_' . $page->url . '.tpl')) {
            return $this->design->fetch('page_' . $page->url . '.tpl');
        } else {
            return $this->design->fetch('page.tpl');
        }
    }
}
