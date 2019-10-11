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

The following paths basically expects the `application/json` media type header
in both request and response payloads. If not otherwise stated assume that media
type.

Error responses use media type `application/problem+json` as defined by [RFC
7807](https://tools.ietf.org/html/rfc7807).

## String formats

* EUI-48/MAC address (IEEE 802) six groups of two hexadecimal digits, separated
  by hyphens (-) in transmission order (e.g. `01-23-45-67-89-AB`)

## Paths

### phones/

* [GET /phones](getPhones) return the collection of configured phones with: mac address, model (if known), brand name, security tokens
* [GET /phones/{mac}](getPhonesMac) return the phone instance with the given key `mac` address
* [POST /phones](postPhones) add a phone to inventory specifying tok1, tok2, mac, model, variables
* [PATCH /phones/{mac}](patchPhonesMac) change `variables` of phone with given `mac` 
* [DELETE /phones/{mac}](deletePhonesMac) delete the phone with given `mac` from inventory

### models/

* [GET /models](getModels) filter=usedonly|brand:BRAND
* [GET /models/{name}](getModelsName) return the model with given `name` (e.g. `snom720`)
* [POST /models](postModels) add a new model instance name, display_name, variables
* [PATCH /models/{name}](patchModelsName) change `variables` and other properties of model `name`
* [DELETE /models/{name}](deleteModel) delete model `name` (if not used, if possible)