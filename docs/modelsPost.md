# POST /models

## Create a new model

Create a new phone model instance and add it to the models collection.
Only `a-z`, `A-Z`, `0-9`, `_`, `-` and `.` characters are allowed as model name

```text
POST /tancredi/api/v1/models
```

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

Success response:

    Status: 201 Created
    Location: /tancredi/api/v1/models/acme19.2

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

Failed responses:

    Status: 400 Bad Request
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://github.com/nethesis/tancredi/wiki/problems#malformed-data",
    "title": "Illegal character(s) in model name"
}
```

    Status: 400 Bad Request
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://github.com/nethesis/tancredi/wiki/problems#malformed-data",
    "title": "Missing model name"
}
```

    Status: 409 Conflict
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://nethesis.github.io/tancredi/problems#model-exists",
    "title": "The model name is already registered"
}
```
