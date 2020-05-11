# POST /firmware

## Upload a firmware file

Upload a firmware file that could be used in scopes

```text
POST /firmware
```

Success response:

    Status: 204

Failed response:

    Status: 400

```json
{
    "type": 'https://github.com/nethesis/tancredi/wiki/problems#malformed-data',
    "title": "Invalid file name"
}

