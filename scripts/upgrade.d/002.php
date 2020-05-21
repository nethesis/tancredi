<?php namespace upgrade2;

/*
 * Copyright (C) 2020 Nethesis S.r.l.
 * http://www.nethesis.it - nethserver@nethesis.it
 *
 * This script is part of NethServer.
 *
 * NethServer is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License,
 * or any later version.
 *
 * NethServer is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with NethServer.  If not, see COPYING.
 */

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
$container['logger']->info("Added vlan_id_phone and vlan_id_pcport to defaults");
