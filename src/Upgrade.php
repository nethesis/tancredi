<?php namespace Tancredi\Upgrades;

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

$container['UpgradeHelper'] = function($c) {
    $helper = new \Tancredi\Entity\UpgradeHelper($c['logger']);
    return $helper;
};

# 
$target_version = getVersion($current_version = false);

while ($target_version > getVersion()) {
    $current_version = getVersion();
    $next_version = $current_version + 1;
    $container['logger']->info("Upgrading from $current_version to $next_version...");

    # Launch update scripts
    if (class_exists("\\Tancredi\\Upgrades\\Upgrader" . $next_version)) {
        $upgrader = "\\Tancredi\\Upgrades\\Upgrader" . $next_version;
        $upgraderObj = new $upgrader($container['storage'], $container['config'], $container['logger']);
        $results = $upgraderObj();
    }

    # Increment current version
    break;
    $scope = new \Tancredi\Entity\Scope('defaults', $container['storage'], $container['logger']);
    $scope->metadata['version'] = $next_version ;
    $scope->setVariables();
}


function getVersion($current_version = true) {
    global $container;
    $defaults = $container['storage']->storageRead('defaults', $original = !$current_version);
    $version = 0;
    if (array_key_exists('metadata',$defaults) && array_key_exists('version',$defaults['metadata'])) {
        $version = $defaults['metadata']['version'];
    }
    return $version;
}
