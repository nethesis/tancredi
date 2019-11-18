# GET /models/{name}

## Simple query

Get a model with the given `name`

    GET /tancredi/api/v1/models/acme19.2

(empty request body)

Success response:

    Status: 200 OK

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

    Status: 404 Not found
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://nethesis.github.io/tancredi/problems#not-found",
    "title": "Resource not found"
}
```

## Get a model original state

The **optional** `original_url` attribute is a reference to another resource or
the resource itself, representing a phone model in its initial state, where all
variables are at their factory default values.

The purpose of this reference is helping the restore of the initial values of
the model variables, to clear any user customization.

See also the [model "original" attribute description](modelsPost#attributes).

Get the variables factory default value for a model, by adding the `original=1`
parameter to the query string:

    GET /tancredi/api/v1/models/acme19.2?original=1

(empty request body)

Success response:

    Status: 200 OK

```json
{
    "name": "acme19.2",
    "display_name": "Acme IP phone v19 rev. 2",
    "variables": {
        "var1": "orig-value1"
    }
}
```

Note that the `original_url` attribute is not set, because the returned model is
at its factory-default state.

## Get inherited variables

Get the model `name` with inherited variables values, by adding the `inherit=1`
parameter to the query string:

    GET /tancredi/api/v1/models/acme19.2?inherit=1

(empty request body)

Success response:

    Status: 200 OK

```json
{
    "name": "acme19.2",
    "display_name": "Acme IP phone v19 rev. 2",
    "original_url": "/tancredi/api/v1/models/acme19.2?original=1",
    "variables": {
        "var1": "value1",
        "var2": "value2",
        "var3": "inherited from defaults",
        "var4": "inherited from defaults"
    }
}
```
