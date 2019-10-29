# Tancredi API v1

Tancredi exposes an administrative API endpoint that allows to read and write
**variables** used into phone provisioning files and other properties used
for security.

## Data model

The Tancredi data model has two main entities: **phone** and (phone) **model**.

Both entities have the **variables** attribute, that is a collection of settings
required to generate the phone provisioning files. A variable value must be
expressed as a JSON string.

A **phone** can be related to a **model**. If this relationship is not defined,
global default values are assumed.

A third entity, **defaults** contains the global default values.

## Phone variables inheritance

During the provisioning files generation each of the above entities acts like a
variables *scope*. A variable value is generally defined by the *most specific
scope rule*, in the following order:

1. phone
2. model
3. defaults

If the most specific *scope* does not define a variable the next one is
considered. Thus a variable value can be **inherited** from the general scope to
the specific one.

## Media types

If not otherwise stated assume `application/json` media type for both request
and response.

Error responses use media type `application/problem+json` as defined by [RFC
7807](https://tools.ietf.org/html/rfc7807).

## String formats

* EUI-48/MAC address (IEEE 802) six groups of two hexadecimal digits, separated
  by hyphens (-) in transmission order (e.g. `01-23-45-67-89-AB`)

## Paths

### phones/

* [GET /phones](phonesGet) return the collection of configured phones
* [GET /phones/{mac}](phonesMacGet) return the phone instance with the given `mac` address
* [POST /phones](phonesPost) add a phone to inventory specifying mac, model and variables
* [PATCH /phones/{mac}](phonesMacPatch) change `variables` of phone with given `mac`
* [DELETE /phones/{mac}](phonesMacDelete) delete the phone with given `mac` from inventory

### models/

* [GET /models](modelsGet) return the phone models collection
* [GET /models/{name}](modelsNameGet) return the model with given `name` (e.g. `snom720`)
* [POST /models](modelsPost) add a new model instance name, display_name, variables
* [PATCH /models/{name}](modelsNamePatch) change `variables` and other properties of model `name`
* [DELETE /models/{name}](modelsNameDelete) delete model `name` (if not used, if possible)

### defaults/

* [GET /defaults](defaultsGet) return the default values for known variables
* [PATCH /defaults](defaultsPatch) change the default value of some variables
