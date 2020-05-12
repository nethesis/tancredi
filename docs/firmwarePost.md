# POST /firmware

## Upload a firmware file

Upload a firmware file that could be used in scopes
Use RFC1867

Example request:
```text
curl -kv -F "firmware=@/etc/hosts" 'https://192.168.122.76/tancredi/api/v1/firmware' -H 'Accept: application/json, text/plain, */*' --compressed -H 'User: admin' -H 'Secretkey: 71531d20e69d0e9d83a666e9b4fd5fae11dcdbb1'
```


```text
POST /firmware
```

Success response:

    Status: 204

Failed response:

    Status: 400

```json
{
    "type": "https://github.com/nethesis/tancredi/wiki/problems#malformed-data",
    "title": "Invalid file name"
}
