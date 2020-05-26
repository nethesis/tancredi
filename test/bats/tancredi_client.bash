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
