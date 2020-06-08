<?php namespace upgrade4;

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

$models = $container['storage']->listScopes('model');
foreach ($models as $id) {
    if(substr($id, 0, 6) == 'fanvil') {
        $scope = new \Tancredi\Entity\Scope($id, $container['storage'], $container['logger']);
        if($scope->metadata['version'] >= 4) {
            continue;
        }
        $scope->metadata['version'] = 4;
        $scope->setVariables([
            'cap_background_file' => '1',
            'cap_screensaver_file' => '',
            'screensaver_time' => '600',
        ]);
        $container['logger']->info("Fixed background and screensaver settings for model $id");

    } elseif(substr($id, 0, 16) == 'gigaset-Maxwell') {
        $model = substr($id, 16, 1);
        $scope = new \Tancredi\Entity\Scope($id, $container['storage'], $container['logger']);
        if($scope->metadata['version'] >= 4) {
            continue;
        }
        $scope->metadata['version'] = 4;
        $scope->setVariables([
            'cap_background_file' => ($model == '4') ? '1' : '',
            'cap_screensaver_file' => ($model == '3' || $model == '4') ? '1' : '',
            'cap_screensaver_time' => ($model == '2' || $model == 'B') ? '1' : '',
            'screensaver_time'  => ($model == '2' || $model == 'B') ? '600' : '',
        ]);
        $container['logger']->info("Fixed background and screensaver settings for model $id");

    }
}

$defaults = new \Tancredi\Entity\Scope('defaults', $container['storage'], $container['logger']);
if($defaults->metadata['version'] < 4) {
    $defaults->metadata['version'] = 4;
    $defaults->setVariables([
        'cap_ringtone_count' => "1",
        'cap_ringtone_blacklist' => "-1,0",
        'background_file' => "",
        'screensaver_file' => "",
        'screensaver_time' => '600',
        'cap_background_file' => "",
        'cap_screensaver_file' => "",
    ]);
    $container['logger']->info("Fixed screensaver, ringtone and background variables in defaults scope");
}
