# GET /firmware

## Simple query

Get the list of firmware files uploaded by the user

    GET /firmware

(empty request body)

Success response:

    Status: 200 OK

```json
[
    {"name": "firmware1", "size": 38491164, "mtime": 1568203320 },
    "firmware2",
    "firmware3"
]
```
