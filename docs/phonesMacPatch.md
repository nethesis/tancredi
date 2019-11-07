# PATCH /phones/{mac}

## Change variable values

Change the `variables` attribute value, by assigning a completely new object.

    PATCH /tancredi/api/v1/phones/01-23-45-67-89-AB

```json
{
    "variables": {
        "var1": "value1/changed",
        "var2": "value2"
    }
}
```

Success response:

    Status: 204 No Content

Empty response - the new `variables` value corresponds to the object passed in
the request body.

## Change the model

Change the `model` value

    PATCH /tancredi/api/v1/phones/01-23-45-67-89-AB

```json
{
    "model": "acme19.2-custom"
}
```

Success response:

    Status: 200 OK

```json
{
    "mac": "01-23-45-67-89-AB",
    "model": "acme19.2-custom",
    "display_name": "Acme",
    "tok1": "3cb63010-6e80-41ff-9437-c4b1413975db",
    "tok2": "88eebf1d-b860-498f-8bfa-4947e170873b",
    "model_url": "/tancredi/api/v1/models/acme19.2-custom",
    "variables": {
        "var1": "value1",
        "var2": "value2"
    }
}
```

The `model_uriref` changes with `model` automatically, so the new resource state
is returned in the response.

## Read only attributes

Attributes `mac`, `tok1`, `tok3` are read-only. Attempt to change their values
causes the whole request to fail.

    PATCH /tancredi/api/v1/phones/01-23-45-67-89-AB

```json
{
    "mac": "doesn't work",
    "model_url": "doesn't work",
    "tok1": "doesn't work",
    "tok2": "doesn't work"
}
```

Failed response:

    Status: 403 Forbidden
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://nethesis.github.io/tancredi/problems#read-only-attribute",
    "title": "Cannot change a read-only attribute"
}
```
