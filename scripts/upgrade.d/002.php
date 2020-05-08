<?php

namespace upgrade2;

$defaults = $container['storage']->storageRead('defaults', false);
if ($defaults['metadata']['version'] >= 2) {
    return;
}

# Increment defaults version
$scope = new \Tancredi\Entity\Scope('defaults', $container['storage'], $container['logger']);
$scope->metadata['version'] = 2;
$scope->setVariables([
    'vlan_id_phone' => '',
    'vlan_id_pcport' => '',
]);
