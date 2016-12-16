<?php

/**
 * Simpla CMS
 *
 * @copyright	2016 Denis Pikusov
 * @link		http://simplacms.ru
 * @author		Denis Pikusov
 *
 */

require_once('api/Simpla.php');

class ExportAdmin extends Simpla
{
    private $export_files_dir = 'simpla/files/export/';

    public function fetch()
    {
        $this->design->assign('export_files_dir', $this->export_files_dir);
        if (!is_writable($this->export_files_dir)) {
            $this->design->assign('message_error', 'no_permission');
        }

        if (!function_exists('iconv') && !function_exists('mb_convert_encoding')) {
            $this->design->assign('message_error', 'iconv_or_mb_convert_encoding');
        }

        if ($this->request->method('post')) {
            switch ($this->request->post('action')) {
                case 'delete': {
                    $names = $this->request->post('check');

                    foreach ($names as $name) {
                        unlink($this->export_files_dir . $name);
                    }
                    break;
                }
            }
        }

        $export_files = glob($this->export_files_dir . "*.csv");

        $exports = array();
        if (is_array($export_files)) {
            foreach ($export_files as $export_file) {
                $export = new \stdClass();
                $export->name = basename($export_file);
                $export->size = filesize($export_file);
                $exports[] = $export;
            }
        }
        $exports = array_reverse($exports);
        $this->design->assign('exports', $exports);

        return $this->design->fetch('export.tpl');
    }
}
