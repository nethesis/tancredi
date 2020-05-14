# PATCH /models/{name}

## Change the resource attributes

Change the `variables` attribute value, and merge them with scope variables. Null variables are removed from scope.
Also display_name is changed.

    PATCH /tancredi/api/v1/models/acme19.2

```json
{
    "display_name": "Acme IP phone v19 rev. 2 (changed)",
    "variables": {
        "var1": "value1-changed",
        "var2": "value2"
    }
}
```

Success response:

    Status: 204 No Content

Empty response - the new `variables` value corresponds to the object passed in
the request body and the `display_name` is set to a new string.

## Read only attributes

The attribute `name` is read-only. Attempt to change its value causes the whole
request to fail.

    PATCH /tancredi/api/v1/models/acme19.2

```json
{
    "name": "acme19.2",
    "display_name": "Acme IP phone v19 rev. 2 (allowed, but not applied)"
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
