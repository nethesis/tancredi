# DELETE /background/{file}

## Delete a background file

Remove a background file previously uploaded

```text
DELETE /background/mountains.png
```

(empty request body)

Success response:

    Status: 204 No Content

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

or:

    Status: 400

```json
{
    "type": "https://github.com/nethesis/tancredi/wiki/problems#malformed-data",
    "title": "Invalid file name"
}
```
