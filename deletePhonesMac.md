# DELETE /phones/{mac}

## Example 1 

Remove a phone from the inventory.

    DELETE /tancredi/api/v1/phones/01-23-45-67-89-AB

(empty request body)

Success response:

    Status: 204 No Content

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

