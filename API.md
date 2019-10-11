Tancredi exposes an administrative API endpoint that allows to read and write **variables** used into devices templates and other attributes used for security. 

The following paths basically expects the `application/json` media type header in both request and response payloads.

## phones/

* [GET /phones](getPhones) return the collection of configured phones with: mac address, model (if known), brand name, security tokens
* [GET /phones/{mac}](getPhonesMac) return the phone instance with the given key `mac` address
* [POST /phones](postPhones) add a phone to inventory specifying tok1, tok2, mac, model, variables
* [PATCH /phones/{mac}](patchPhonesMac) change `variables` of phone with given `mac` 
* [DELETE /phones/{mac}](deletePhonesMac) delete the phone with given `mac` from inventory

## models/

* [GET /models](getModels) filter=usedonly|brand:BRAND
* [GET /models/{name}](getModelsName) return the model with given `name` (e.g. `snom720`)
* [POST /models](postModels) add a new model instance name, display_name, variables
* [PATCH /models/{name}](patchModelsName) change `variables` and other attributes of model `name`
* [DELETE /models/{name}](deleteModel) delete model `name` (if not used, if possible)