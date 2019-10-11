# GET /models

## Example 1

Retrieve the complete (phone) models collection.

    GET /tancredi/api/v1/models

(empty request body)

Response:

    Status: 200 OK

```json
[{
    "name": "acme19.2",
    "display_name": "Acme IP phone v19 rev. 2",
    "model_url": "/tancredi/api/v1/models/acme19.2",
},{
    "name": "acme19.2",
    "display_name": "Acme IP phone v19 rev. 2",
    "model_url": "/tancredi/api/v1/models/acme19.2",
}]
```

## Example 2

Select the models that are referenced by phone instances (The phone property
`model` is equal to the model `name` property).

    GET /tancredi/api/v1/models?filter[used]

(empty request body)

Response:

    Status: 200 OK

```json
[]
```

## Example 3

Select the models which name starts with `acme`

    GET /tancredi/api/v1/models?filter[name]=acme

(empty request body)

Response:

    Status: 200 OK

```json
[]
```