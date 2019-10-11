# GET /models/{name}

## Example 1

Get a model with the given `name`

    GET /tancredi/api/v1/models/acme19.2

(empty request body)

Success response:

    Status: 200 OK

```json
{
    "name": "acme19.2",
    "display_name": "Acme IP phone v19 rev. 2",
    "variables": {
        "var1": "value1",
        "var2": "value2"
    }
}
```

Failed response:

    Status: 404 Not found
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://github.com/nethesis/tancredi/wiki/problems#not-found",
    "title": "Resource not found"
}
```