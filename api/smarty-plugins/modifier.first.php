<?php

function smarty_modifier_first($params = array())
{
    if (!is_array($params)) {
        return false;
    }
    return reset($params);
}
