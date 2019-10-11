# Tancredi API v1

Tancredi exposes an administrative API endpoint that allows to read and write
**variables** used into phone provisioning files and other properties used
for security.

## Data model

The Tancredi data model has two main entities: **phone** and (phone) **model**.

Both entities have the **variables** property, that is a collection of settings
required to generate the phone provisioning files.

A **phone** can be related to a **model**. If this relationship is not defined,
global default values are assumed.

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

* [GET /models](modelsGet)
* [GET /models/{name}](modelsNameGet) return the model with given `name` (e.g. `snom720`)
* [POST /models](modelsPost) add a new model instance name, display_name, variables
* [PATCH /models/{name}](modelsNamePatch) change `variables` and other properties of model `name`
* [DELETE /models/{name}](modelsNameDelete) delete model `name` (if not used, if possible)