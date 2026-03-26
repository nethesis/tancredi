#!/usr/bin/env bats

#
# Copyright (C) 2024 Nethesis S.r.l.
# http://www.nethesis.it - nethserver@nethesis.it
#
# This script is part of NethServer.
#
# NethServer is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License,
# or any later version.
#
# NethServer is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with NethServer.  If not, see COPYING.
#

setup () {
    load tancredi_client
}

@test "Reset storage dir before upgrade" {
    tancredi_reset_rw_dir
}

@test "Seed outdated nethesis-NPX5v2 scope before upgrade" {
    local scope_path
    scope_path="$(tancredi_scope_path nethesis-NPX5v2)"

    mkdir -p "$(dirname "$scope_path")"
    cp "$(tancredi_shipped_scope_path nethesis-NPX5v2)" "$scope_path"
    sed -i 's/^version = .*/version = 1/' "$scope_path"
    sed -i 's/^tmpl_firmware = .*/tmpl_firmware = "nethesis-firmware.tmpl"/' "$scope_path"

    # Verify that the scope file was correctly seeded
    [[ -f "$scope_path" ]]
    grep -q '^version = 1$' "$scope_path"
    grep -q '^tmpl_firmware = "nethesis-firmware.tmpl"$' "$scope_path"
}

@test "Run upgrade script" {
    run php "$(tancredi_upgrade_script)"
    [[ $status -eq 0 ]]
}

@test "Upgrade sets defaults version" {
    run GET /tancredi/api/v1/defaults
    assert_http_code "200"
    assert_http_body '"vlan_id_phone"'
    assert_http_body '"vlan_id_pcport"'
}

@test "Upgrade sets snom-D785 ldap filters (012)" {
    run GET /tancredi/api/v1/models/snom-D785
    assert_http_code "200"
    assert_http_body '"ldap_number_filter"'
    assert_http_body '"ldap_name_filter"'
}

@test "Upgrade sets fanvil-X3U cap_linekey_count (005)" {
    run GET /tancredi/api/v1/models/fanvil-X3U
    assert_http_code "200"
    assert_http_body '"cap_linekey_count":"3"'
}

@test "Upgrade sets sangoma-S305 cap_linekey_count (007)" {
    run GET /tancredi/api/v1/models/sangoma-S305
    assert_http_code "200"
    assert_http_body '"cap_linekey_count":"15"'
}

@test "Upgrade sets gigaset-Maxwell3 screensaver blacklist (008)" {
    run GET /tancredi/api/v1/models/gigaset-Maxwell3
    assert_http_code "200"
    assert_http_body '"cap_screensaver_time_blacklist":"3,5,7,10,15,30,60,120,300"'
}

@test "Upgrade keeps yealink-T43 expansion settings" {
    run GET /tancredi/api/v1/models/yealink-T43
    assert_http_code "200"
    assert_http_body '"cap_expmodule_count":"3"'
    assert_http_body '"cap_expkey_count":"60"'
}

@test "Upgrade sets snom expansion key modules (006)" {
    run GET /tancredi/api/v1/models/snom-D735
    assert_http_code "200"
    assert_http_body '"cap_expkey_count":"18"'
    assert_http_body '"cap_expmodule_count":"3"'
}

@test "Upgrade sets fanvil sidekey pages (009)" {
    run GET /tancredi/api/v1/models/fanvil-X4U
    assert_http_code "200"
    assert_http_body '"fanvil_sidepages_count":"1"'
}

@test "Upgrade sets fanvil-X3U fanvil_lkpages_count (011)" {
    run GET /tancredi/api/v1/models/fanvil-X3U
    assert_http_code "200"
    assert_http_body '"fanvil_lkpages_count":"0"'
}

@test "Upgrade rewrites nethesis-NPX5v2 firmware template (013)" {
    run grep -F 'version = 13' "$(tancredi_scope_path nethesis-NPX5v2)"
    [[ $status -eq 0 ]]

    run grep -F 'tmpl_firmware = "nethesis-firmware-v2.tmpl"' "$(tancredi_scope_path nethesis-NPX5v2)"
    [[ $status -eq 0 ]]

    run GET /tancredi/api/v1/models/nethesis-NPX5v2
    assert_http_code "200"
    assert_http_body '"tmpl_firmware":"nethesis-firmware-v2.tmpl"'
}

@test "Upgrade is idempotent (second run succeeds)" {
    run php "$(tancredi_upgrade_script)"
    [[ $status -eq 0 ]]
}

@test "Models unchanged after idempotent upgrade" {
    run GET /tancredi/api/v1/models/snom-D785
    assert_http_code "200"
    assert_http_body '"ldap_number_filter"'

    run GET /tancredi/api/v1/models/fanvil-X3U
    assert_http_code "200"
    assert_http_body '"cap_linekey_count":"3"'

    run GET /tancredi/api/v1/models/yealink-T43
    assert_http_code "200"
    assert_http_body '"cap_expmodule_count":"3"'
    assert_http_body '"cap_expkey_count":"60"'

    run GET /tancredi/api/v1/models/nethesis-NPX5v2
    assert_http_code "200"
    assert_http_body '"tmpl_firmware":"nethesis-firmware-v2.tmpl"'
}

@test "Reset storage dir after upgrade tests" {
    tancredi_reset_rw_dir
}
