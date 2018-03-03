<?php

function smarty_modifier_crop($filename, $width=0, $height=0, $set_watermark=false)
{
    require_once('./api/Simpla.php');
    $simpla = new Simpla();

    $resized_filename = $simpla->image->add_resize_params($filename, 'crop', $width, $height, $set_watermark);
    $resized_filename_encoded = $resized_filename;

    if (substr($resized_filename_encoded, 0, 7) == 'http://' || substr($resized_filename_encoded, 0, 8) == 'https://') {
        $resized_filename_encoded = rawurlencode($resized_filename_encoded);
    }

    $resized_filename_encoded = rawurlencode($resized_filename_encoded);

    return $simpla->config->root_url.'/'.$simpla->config->resized_images_dir.$resized_filename_encoded.'?'.$simpla->config->token($resized_filename);
}
