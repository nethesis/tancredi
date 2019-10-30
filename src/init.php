<?php

require '../vendor/autoload.php'; 
include_once(__DIR__ . '/../src/functions.inc.php'); 
 
ini_set('date.timezone', 'UTC'); 

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

$config = array_merge($default_config, $ini_config);

use \Monolog\Logger; 
 
use \Monolog\Handler\StreamHandler; 
 
$log = new Logger('Tancredi'); 

switch ($config['log_level']) {
    case 'DEBUG':
        $level = Logger::DEBUG;
    break;
    case 'INFO':
    case 'NOTICE':
        $level = Logger::INFO;
    break;
    case 'WARNING':
        $level = Logger::WARNING;
    break;
    case 'ERROR':
    case 'CRITICAL':
    case 'ALERT':
    case 'EMERGENCY':
        $level = Logger::ERROR;
    break;
}

$log->pushHandler(new StreamHandler($config['logfile'], $level));

