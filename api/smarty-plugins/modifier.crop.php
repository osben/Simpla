<?php

function smarty_modifier_crop($filename, $width=0, $height=0, $set_watermark=false)
{
    require_once( dirname(dirname(__FILE__)) . '/Simpla.php');
    $simpla = new Simpla();

    return $simpla->image->crop_image($filename, $width, $height, $set_watermark);
}
