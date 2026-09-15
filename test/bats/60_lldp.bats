#!/usr/bin/env bats

@test "LLDP renders enabled, disabled, blank and missing values for every supported template" {
    run php "${TANCREDI_APP_ROOT}/test/lldp-rendering.php"
    if [[ "$status" -ne 0 ]]; then
        echo "$output" >&2
        return 1
    fi
}
