# POST /models

## Create a new model

Create a new phone model instance and add it to the models collection.
only [a-z], [A-Z], [0-9], "\_", "-" and "." characters are allowd as model name

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

Failed response:

    Status: 409 Conflict
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://nethesis.github.io/tancredi/problems#model-exists",
    "title": "The model name is already registered"
}
```
