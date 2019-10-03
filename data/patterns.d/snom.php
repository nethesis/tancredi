<?php

// Filename pattern for Snom Phones

$patterns = array(
    array(
        'pattern' => 'snom(?<model>[D]{0,1}[0-9]{3})\.htm',
        'template' => 'snom-${MODEL}.tmpl'
    ),
    array(
        'pattern' => 'snom(?<model>[D]{0,1}[0-9]{3})-(?<mac_address>000413[0-9A-F]{6})\.htm',
        'template' => 'snom-${MODEL}-${MAC}.tmpl'
    ),
    array(
        'pattern' => 'general\.xml',
        'template' => 'snom_general.tmpl'
    )
);

if (!isset($template) or !isset($brand) or !isset($model) or !isset($mac_address)) {
    foreach ($patterns as $pattern) {
        if (preg_match('/'.$pattern['pattern'].'/', $request_filename, $tmp)) {
            if (array_key_exists('template',$pattern)) $template = $pattern['template'];
            if (!isset($brand)) $brand = 'snom';
            if (!isset($model) and array_key_exists('model',$tmp)) $model = $tmp['model'];
            if (!isset($mac_address) and array_key_exists('mac_address',$tmp)) $mac_address = $tmp['mac_address'];
        }
    }
}
