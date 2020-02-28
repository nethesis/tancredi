# GET /phones/{mac}

## Simple query

Get a phone with the given MAC address.

    GET /tancredi/api/v1/phones/01-23-45-67-89-AB

(empty request body)

Success response:

    Status: 200 OK

```json
{
    "mac": "01-23-45-67-89-AB",
    "model": "acme19.2",
    "display_name": "Acme",
    "tok1": "3cb63010-6e80-41ff-9437-c4b1413975db",
    "tok2": "88eebf1d-b860-498f-8bfa-4947e170873b",
    "model_url": "/tancredi/api/v1/models/acme19.2",
    "provisioning_url1": "https://myexample.com/provisioning/3cb63010-6e80-41ff-9437-c4b1413975db/%MACD.xml",
    "provisioning_url2": "https://myexample.com/provisioning/88eebf1d-b860-498f-8bfa-4947e170873b/%MACD.xml",
    "variables": {
        "var1": "value1",
        "var2": "value2"
    }
}
```

It is possible to query `model_url` with parameter `inherit=1` to obtain the
default values for the items in `variables`. See also [GET
/models/{name}](modelsNameGet).

Failed response:

    Status: 404 Not found
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://nethesis.github.io/tancredi/problems#not-found",
    "title": "Resource not found"
}
```