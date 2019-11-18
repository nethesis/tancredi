# POST /models

## Attributes

* `name` is the unique identifier of a model resource
* `original` is the name of an already existing model
* `display_name` is a human readable string for the model itself
* `variables` is an object where each key-value represents a variable with its corresponding value

## Create a new model

Create a new phone model instance and add it to the models collection.

```text
POST /tancredi/api/v1/models
```

```json
{
    "name": "acme19.2",
    "original": "acme19.2",
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
    "original_url": "/tancredi/api/v1/models/acme19.2?original=1",
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
