# GET /phones

## Example 1

Retrieve the complete phone inventory. See also [GET
/phones/{mac}](getPhonesMac).

    GET /tancredi/api/v1/phones

Empty request body

Response:

    Status: 200 OK

```json
[{
    "mac": "01-23-45-67-89-AB",
    "model": "acme19.2",
    "display_name": "Acme",
    "tok1": "3cb63010-6e80-41ff-9437-c4b1413975db",
    "tok2": "88eebf1d-b860-498f-8bfa-4947e170873b",
    "model_url": "/tancredi/api/v1/models/acme19.2",
    "variables": {
        "var1": "value1",
        "var2": "value2"
    }
},{
    "mac": "01-23-45-76-98-DE",
    "model": "acme15.0",
    "display_name": "Acme",
    "tok1": "e649145b-6953-4fd5-88bd-350426c5e92b",
    "tok2": "79d4752c-ba3a-40c9-b4e9-7845470cb4ae",
    "model_url": "/tancredi/api/v1/models/acme15.0",
    "variables": {
        "var1": "value1",
        "var2": "value2"
    }
}]
```