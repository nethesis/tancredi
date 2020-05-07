<?php

require_once __DIR__.'/../vendor/autoload.php';
$container = new \Pimple\Container();
$container['config'] = $config;
$container['logger'] = function($c) {
    return \Tancredi\LoggerFactory::createLogger('upgrade', $c);
};

$container['storage'] = function($c) {
    global $config;
    $storage = new \Tancredi\Entity\FileStorage($c['logger'],$config);
    return $storage;
};

# Launch update scripts
$filesArray=glob(__DIR__ . "/upgrade.d/*.php");
foreach ($filesArray as $file) {
    $container['logger']->info("Launching upgrade script $file");
    include $file;
}
