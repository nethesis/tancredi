<?php

if( ! ini_get('date.timezone')) {
    ini_set('date.timezone', 'UTC');
}

// Default configuration
$default_config = array(
    'logfile' => "",
    'loglevel' => "DEBUG",
    'ro_dir' => __DIR__ . "/../data/",
    'rw_dir' => __DIR__ . "/../data/"
);

// Read configuration from file
$cfg_file = getenv("tancredi_conf") ?: '/etc/tancredi.conf';
$ini_config = false;

if (file_exists($cfg_file)) {
    $ini_config = parse_ini_file($cfg_file);
}

if($ini_config === false) {
    http_response_code(500);
    header("Content-Type: text/plain ;charset=UTF-8");
    echo("Bad configuration\n\nCould not parse $cfg_file\n");
    exit(1);
}

$GLOBALS['config'] = array_merge($default_config, $ini_config);

