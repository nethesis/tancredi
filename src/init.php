<?php

if( ! ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

// Default configuration
$default_config = array(
    'logfile' => "/var/log/tancredi.log",
    'loglevel' => "ERROR",
    'ro_dir' => __DIR__ . "/../data/",
    'rw_dir' => __DIR__ . "/../data/"
);

// Read configration 
$ini_config = array();
if (file_exists('/etc/tancredi.conf')) { 
    $ini_config = parse_ini_file('/etc/tancredi.conf'); 
}

$GLOBALS['config'] = array_merge($default_config, $ini_config);

