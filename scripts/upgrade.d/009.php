<?php namespace upgrade9;

/*
 * Copyright (C) 2021 Nethesis S.r.l.
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
// Add Fanvil sidekey pages and update Fanvil line key number and pages
//
$fixes = [
    'fanvil-X210' => ['fanvil_sidepages_count' => '1'],
    'fanvil-X3U' => ['fanvil_sidepages_count' => '1'],
    'fanvil-X4U' => ['fanvil_sidepages_count' => '1'],
    'fanvil-X5U' => ['fanvil_sidepages_count' => '1'],
    'fanvil-X6U' => ['fanvil_sidepages_count' => '1'],
];

foreach ($fixes as $model_id => $variables) {
    $scope = new \Tancredi\Entity\Scope($model_id, $container['storage'], $container['logger']);
    if(isset($scope->metadata['version']) && $scope->metadata['version'] >= 9) {
        continue;
    }
    $scope->metadata['version'] = 9;
    $scope->setVariables($variables);
    $container['logger']->info("Add Fanvil sidekey pages and update Fanvil line key number and pages for template X-5 models");
}
