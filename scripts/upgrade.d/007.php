<?php namespace upgrade7;

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

//
// Set right Sangoma phone linekey number
//
$fixes = [
    'sangoma-S305' => ['cap_linekey_count' => 15],
    'sangoma-S400' => ['cap_linekey_count' => 25],
    'sangoma-S405' => ['cap_linekey_count' => 25],
    'sangoma-S406' => ['cap_linekey_count' => 25],
    'sangoma-S500' => ['cap_linekey_count' => 35],
    'sangoma-S505' => ['cap_linekey_count' => 35],
    'sangoma-S700' => ['cap_linekey_count' => 45],
    'sangoma-S705' => ['cap_linekey_count' => 45]
];

foreach ($fixes as $model_id => $variables) {
    $scope = new \Tancredi\Entity\Scope($model_id, $container['storage'], $container['logger']);
    if(isset($scope->metadata['version']) && $scope->metadata['version'] >= 7) {
        continue;
    }
    $scope->metadata['version'] = 7;
    $scope->setVariables($variables);
    $container['logger']->info("Fixed cap_linekey_count in model $model_id");
}
