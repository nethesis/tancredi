<?php namespace Tancredi;

require_once '../vendor/autoload.php';
use Pimple\Container;

$container = new Container();
$container['config'] = $config;
$container['logger'] = function($c) {
    return \Tancredi\LoggerFactory::createLogger('Upgrader', $c);
};

$container['storage'] = function($c) {
    global $config;
    $storage = new \Tancredi\Entity\FileStorage($c['logger'],$config);
    return $storage;
};

$container['logger']->info("Launching upgrade scripts");
# Launch update scripts
$filesArray=glob(__DIR__ . "/upgrades.d/*.php");
foreach ($filesArray as $file) {
    $container['logger']->info("Launching upgrade script $file");
    $incf = function() use ($container,$file) {
        include $file;
    };
    $incf();
}
