<?php namespace upgrade11;

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
// Fix for https://github.com/nethesis/dev/issues/6007
//

$fixes = array(
    'yealink-T43' => [
        'cap_expkey_count' => '40',
    ],
    'fanvil-X3U' => [
        'fanvil_lkpages_count' => '0',
    ],
);
foreach ($fixes as $model_id => $variables) {
    $scope = new \Tancredi\Entity\Scope($model_id, $container['storage'], $container['logger']);
    if(isset($scope->metadata['version']) && $scope->metadata['version'] >= 11) {
        continue;
    }
    $scope->metadata['version'] = 11;
    $scope->setVariables($variables);
    $container['logger']->info("Fix ".basename(__FILE__)." applied to scope $model_id");
}
