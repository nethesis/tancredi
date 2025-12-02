#!/usr/bin/env bats

setup () {
    load tancredi_client
}

@test "POST /tancredi/api/v1/phones (00-15-65-AA-BB-CC, Yealink T46, success)" {
    run POST /tancredi/api/v1/phones <<EOF
{
    "mac": "00-15-65-AA-BB-CC",
    "model": "yealink-T46",
    "display_name": "Yealink T46G Test Phone",
    "variables": {
        "sip_login": "1001",
        "sip_password": "testpass123",
        "owner": "Test User",
        "timezone": "Europe/Rome",
        "ntp_server": "pool.ntp.org",
        "time_format": "1"
    }
}
EOF
    assert_http_code "201"
    assert_http_header "Location" "/tancredi/api/v1/phones/00-15-65-AA-BB-CC"
}

@test "GET /tancredi/api/v1/phones/00-15-65-AA-BB-CC (success)" {
    run GET /tancredi/api/v1/phones/00-15-65-AA-BB-CC
    assert_http_code "200"
    assert_http_body "Test User"
    assert_http_body "yealink-T46"
}

@test "GET /provisioning/{tok1}/001565aabbcc.cfg (template rendering)" {
    # First, get the phone data to extract tok1
    run GET /tancredi/api/v1/phones/00-15-65-AA-BB-CC
    assert_http_code "200"
    
    # Extract tok1 token
    tok1=$(extract_token "tok1")
    
    if [[ -z "$tok1" ]]; then
        echo "Failed to extract tok1 token" 1>&2
        return 1
    fi
    
    # Call provisioning API with Yealink user agent
    run GET_PROVISIONING "Yealink SIP-T46G 41.0.0.0 00:15:65:aa:bb:cc" "/provisioning/${tok1}/001565aabbcc.cfg"
    
    assert_http_code "200"
    assert_http_header "Content-Type" "text/plain; charset=utf-8"
    
    # Check for some expected content from Yealink template
    assert_http_body "#!version:1.0.0.1"
    assert_http_body "account.1.enable"
    
    # print downloaded file
    body="$(sed -n -r '/^\s*$/,$ p' <<<"$output" | tail -n +2 || true)"
    echo "------ downloaded template start ------"
    printf '%s\n' "$body"
    echo "------- downloaded template end -------"

    # Compare against fixture
    fixture_file="$(dirname "${BASH_SOURCE[0]}")/../fixtures/yealink-t46-expected.cfg"
    assert_template_matches_fixture "$fixture_file"
}

@test "DELETE /tancredi/api/v1/phones/00-15-65-AA-BB-CC (cleanup)" {
    run DELETE /tancredi/api/v1/phones/00-15-65-AA-BB-CC
    assert_http_code "204"
    assert_http_body_empty
}

