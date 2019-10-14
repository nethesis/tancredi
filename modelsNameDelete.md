# DELETE /models/{mac}

## Delete a model

Delete a model.

    DELETE /tancredi/api/v1/model/acme19.2

(empty request body)

Success response:

    Status: 204 No Content

Failed response 1:

    Status: 404 Not found
    Content-Type: application/problem+json
    Content-Language: en

```json
{
    "type": "https://github.com/nethesis/tancredi/wiki/problems#not-found",
    "title": "Resource not found"
}
```

