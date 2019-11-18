# Problems

This is the reference index of error types, as required by [RFC
7807](https://tools.ietf.org/html/rfc7807).

## Phone exists

    The phone mac address is already registered

## Not found

    Resource not found

## Read only attribute

    Cannot change a read-only attribute

## Resource in use

    The resource is in use by other resources and cannot be deleted

## Model exists

    The model name is already registered

## Forbidden

    Access to resource is forbidden with current client privileges

See the `detail` attribute for detailed failure reason. For instance

    "Wrong credentials"
