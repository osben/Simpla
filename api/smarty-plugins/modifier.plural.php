<?php

function smarty_modifier_plural($params = array())
{
    if (!is_array($params)) {
        return false;
    }
    return reset($params);
}
