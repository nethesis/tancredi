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
        $model = substr($id, 9, 1);
        $is_second_generation = $model == '5' || $model == '6' || substr($id, 10, 2) == 'SG';
        $scope->metadata['version'] = 4;
        $scope->setVariables([
            'cap_background_file' => '1',
            'cap_screensaver_file' => '',
            'screensaver_time' => '600',
            'backlight_time' => '30',
            'cap_contrast' => '1',
            'contrast' => '4',
            'cap_brightness' => $is_second_generation ? '1' : '',
            'brightness' => $is_second_generation ? '5' : '',
            'cap_backlight_time_blacklist' => '',
            'cap_screensaver_time_blacklist' => '',
        ]);
        $container['logger']->info("Fixed background and screensaver settings for model $id");

    } elseif(substr($id, 0, 15) == 'gigaset-Maxwell') {
        $model = substr($id, 16, 1);
        $scope = new \Tancredi\Entity\Scope($id, $container['storage'], $container['logger']);
        if($scope->metadata['version'] >= 4) {
            continue;
        }
        $scope->metadata['version'] = 4;
        $scope->setVariables([
            'cap_background_file' => ($model == 'T29' || $model == 'T46' || $model == 'T48' || $model == 'T54' || $model == 'T56' || $model == 'T57' || $model == 'T57' || $model == 'VP59') ? '1' : '',
            'cap_screensaver_file' => ($model == 'T29' || $model == 'T46' || $model == 'T48' || $model == 'T54' || $model == 'T56' || $model == 'T57' || $model == 'T57' || $model == 'VP59') ? '1' : '',
            'screensaver_time'  => '600',
            'backlight_time' => ($model == 'T21' || $model == 'T23' || $model == 'T27' || $model == 'T40' || $model == '41' || $model == '42') ? '30' : '0',
            'cap_contrast' => ($model == 'T40' || $model == 'T43' || $model == 'T49' || $model == 'T52' || $model == 'T53') ? '1' : '',
            'contrast' => ($model == 'T40' || $model == 'T43' || $model == 'T49' || $model == 'T52' || $model == 'T53') ? '6' : '',
            'cap_brightness' => ($model == 'T27' || $model == 'T29' || $model == 'T43' || $model == 'T46' || $model == 'T48' || $model == 'T53' || $model == 'T54' || $model == 'T57' || $model == 'T58' || $model == 'VP59') ? '1' : '',
            'brightness' => ($model == 'T27' || $model == 'T29' || $model == 'T43' || $model == 'T46' || $model == 'T48' || $model == 'T53' || $model == 'T54' || $model == 'T57' || $model == 'T58' || $model == 'VP59') ? '8' : '',
            'cap_backlight_time_blacklist' => $model == 'T19' ? '0,3,5,7,10,15,30,60,120,300,600,1200,1800,2400,3000,3600' : '3,5,7,10,1200,2400,3000,3600',
            'cap_screensaver_time_blacklist' => $model == 'T19' ? '0,3,5,7,10,15,30,60,120,300,600,1200,1800,2400,3000,3600' : '3,5,7,10,1200,2400,3000,3600',
        ]);
        $container['logger']->info("Fixed background and screensaver settings for model $id");

    } elseif(substr($id, 0,7) == 'yealink') {
        $model = preg_replace('/yealink-([A-Z0-9]*)$/','$1',$id);
        $scope = new \Tancredi\Entity\Scope($id, $container['storage'], $container['logger']);
        if($scope->metadata['version'] >= 4) {
            continue;
        }
        $scope->metadata['version'] = 4;
        echo "$model\n";
        $scope->setVariables([
            'cap_background_file' => ($model == '4') ? '1' : '',
            'cap_screensaver_file' => ($model == '3' || $model == '4') ? '1' : '',
            'screensaver_time'  => ($model == '2' || $model == 'B') ? '600' : '',
            'backlight_time' => '60',
            'cap_contrast' => $model == 'B' ? '1' : '',
            'contrast' => $model == 'B' ? '5' : '',
            'cap_brightness' => '1',
            'brightness' => '5',
            'cap_backlight_time_blacklist' => '3,5,7,10,15,30',
            'cap_screensaver_time_blacklist' => '3,5,7,10,1200,2400,3000,3600',
        ]);
        $container['logger']->info("Fixed background and screensaver settings for model $id");

    } elseif(substr($id, 0, 7) == 'sangoma') {
        $model = substr($id, 10, 1);
        $scope = new \Tancredi\Entity\Scope($id, $container['storage'], $container['logger']);
        if($scope->metadata['version'] >= 4) {
            continue;
        }
        $scope->metadata['version'] = 4;
        $scope->setVariables([
            'cap_background_file' => ($model == '5' || $model == '7') ? '1' : '',
            'cap_screensaver_file' => ($model == '5' || $model == '7') ? '1' : '',
            'screensaver_time'  => '600',
            'cap_screensaver_time_blacklist' => "3,5,7,10,15,30,1200,2400,3000,3600",
            'backlight_time' => ($model == '5' || $model == '7') ? '60' : '',
            'cap_backlight_time_blacklist' => ($model == '5' || $model == '7') ? "" : "0,3,5,7,10,15,30,60,120,300,600,1200,1800,2400,3000,3600",
            'cap_contrast' => ($model == '4' || $model == '3' || $model == '2') ? '1' : '',
            'contrast' => ($model == '4' || $model == '3' || $model == '2') ? '6' : '',
            'cap_brightness' => '1',
            'brightness' => '8',
        ]);
        $container['logger']->info("Fixed background and screensaver settings for model $id");

    } elseif(substr($id, 0, 4) == 'snom') {
        $model = substr($id, 6, 4);
        $scope = new \Tancredi\Entity\Scope($id, $container['storage'], $container['logger']);
        if($scope->metadata['version'] >= 4) {
            continue;
        }
        $scope->metadata['version'] = 4;
        $scope->setVariables([
            'cap_background_file' => ($model == 'D375' || $model == 'D385' || $model == 'D717' || $model == 'D735' || $model == 'D765' || $model == 'D785') ? '1' : '',
            'cap_screensaver_file' => '',
            'screensaver_time'  => '',
            'cap_screensaver_time_blacklist' => '0,3,5,7,10,15,30,60,120,300,600,1200,1800,2400,3000,3600',
            'backlight_time' => '30',
            'cap_backlight_time_blacklist' => '',
            'cap_contrast' => '',
            'contrast' => ($model == '4' || $model == '3' || $model == '2') ? '6' : '',
            'cap_brightness' => '1',
	    'brightness' => '9',
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
        'backlight_time' => '30',
        'cap_brightness' => '',
        'cap_contrast' => '',
        'brightness' => '5',
        'contrast' => '5',
        'cap_backlight_time_blacklist' => '',
        'cap_screensaver_time_blacklist' => '',
    ]);
    $container['logger']->info("Fixed display and ringtone variables in defaults scope");
}
