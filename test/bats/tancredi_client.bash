#!/bin/bash

#
# Copyright (C) 2019 Nethesis S.r.l.
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

tancredi_base_url=http://127.0.0.1

xcurl () {
    local argv
    argv=("${@}")
    argv[$((${#argv[@]} - 1))]="${tancredi_base_url}${argv[-1]}"
    curl \
      -s -i \
      -H 'Accept: application/json, application/problem+json' \
      -X "${argv[@]}"
}

GET () {
    xcurl GET "${@}"
}


DELETE () {
    xcurl DELETE "${@}"
}

POST () {
    xcurl POST \
        -d @- -H 'Content-Type: application/json; charset=UTF-8' \
        "${@}"
}

POSTFILE () {
    xcurl POST \
        "${@}"
}

PATCH () {
    xcurl PATCH \
        -d @- -H 'Content-Type: application/json; charset=UTF-8' \
        "${@}"
}

GET_PROVISIONING () {
    local user_agent="${1}"
    local path="${2}"
    curl -s -i \
        -H "User-Agent: ${user_agent}" \
        "${tancredi_base_url}${path}"
}

extract_field () {
    local field_name="${1}"
    sed -n -r '/^\s*$/,$ p' <<<"$output" | grep -o "\"${field_name}\":\"[^\"]*\"" | cut -d'"' -f4
}

assert_http_code () {
    if ! grep -q -F "HTTP/1.1 $1" <<<"${lines[@]}"; then
        echo "$output" 1>&2
        return 1
    fi
    return 0
}

assert_http_header () {
    sed '/^$/q' <<<"$output" | grep -q -F "${1}: ${2}" || :
    if [[ ${PIPESTATUS[1]} != 0 ]]; then
        echo "$output" 1>&2
        return 1
    fi
    return 0
}

assert_http_body () {
    sed -n -r '/^\s*$/,$ p' <<<"$output" | grep -q -F "$1" || :
    if [[ ${PIPESTATUS[1]} != 0 ]]; then
        echo "$output" 1>&2
        return 1
    fi
    return 0
}

assert_http_body_re () {
    sed -n -r '/^\s*$/,$ p' <<<"$output" | grep -q -E "$1" || :
    if [[ ${PIPESTATUS[1]} != 0 ]]; then
        echo "$output" 1>&2
        return 1
    fi
    return 0
}

assert_http_body_empty () {
    sed -n -r '/^\s*$/,$ p' <<<"$output" | grep -E '\w' || :
    if [[ ${PIPESTATUS[1]} == 0 ]]; then
        echo "$output" 1>&2
        return 1
    fi
    return 0
}

assert_template_matches_fixture () {
    local fixture_file="${1}"
    local test_output_file="/tmp/tancredi_test_output_$$.txt"

    echo "$output" > "$test_output_file"

    if [[ ! -f "$fixture_file" ]]; then
        echo "Fixture file not found: $fixture_file" 1>&2
	    echo "----------------Fixture expected content------------------"  1>&2
        cat "$test_output_file" 1>&2
        echo "----------------------------------------------------------"  1>&2
        return 1
    fi

    local diff_output diff_status
    echo diff -u "$fixture_file" "$test_output_file"
    if ! diff_output="$(diff -u "$fixture_file" "$test_output_file")"; then
        diff_status=$?
        if [[ $diff_status -eq 1 ]]; then
            printf '%s\n' "$diff_output" 1>&2
            echo "Template output does not match fixture file" 1>&2
        else
            echo "Failed to compare template output: $diff_output" 1>&2
        fi
        rm -f "$test_output_file"
        return 1
    fi

    rm -f "$test_output_file"
    return 0
}
