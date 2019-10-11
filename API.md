Tancredi exposes an administrative API endpoint that allows to read and write **variables** used into devices templates and other attributes used for security. 

The following paths basically expects the `application/json` media type header in both request and response payloads.

* `GET` [/phones](getPhones) return the collection of configured phones with: mac address, model (if known), brand name, security tokens
* `GET` [/phones/{mac}](getPhoneByMac) return the phone instance with the given key `mac` address
* `POST` [/phones](postPhone) add a phone to inventory specifying tok1, tok2, mac, model, variables
* `PATCH` [/phones/{mac}](patchPhoneByMac) change `variables` of phone with given `mac` 
* `DELETE` [/phones/{mac}](deletePhoneByMac) delete the phone with given `mac` from inventory

* GET /models filter=usedonly|brand:BRAND
* GET /models/NAME 
* POST /models name, display_name, variables
* PATCH /models/NAME , display_name, variables
* DELETE /models/NAME (allowed only if this model isn't in use)