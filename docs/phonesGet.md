# GET /phones

## Retrieve all phones

Retrieve the complete phone inventory. See also [GET
/phones/{mac}](phonesMacGet).

    GET /tancredi/api/v1/phones

(empty request body)

Response:

    Status: 200 OK

```json
[{
    "mac": "01-23-45-67-89-AB",
    "model": "acme19.2",
    "display_name": "Acme",
    "model_url": "/tancredi/api/v1/models/acme19.2",
    "phone_url": "/tancredi/api/v1/phones/01-23-45-67-89-AB"
},{
    "mac": "01-23-45-76-98-DE",
    "model": "acme15.0",
    "display_name": "Acme",
    "model_url": "/tancredi/api/v1/models/acme15.0",
    "phone_url": "/tancredi/api/v1/phones/01-23-45-76-98-DE"
}]
```
