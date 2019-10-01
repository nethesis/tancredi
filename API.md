Tancredi exposes an API endpoint that allows to read and write variables used into devices templates

* GET /variables return all available variables with: name, default value, type
* GET /variables/NAME return variable "NAME"

* GET /phones return all configured phones with: mac address, model (if known), brand, UUID
* POST /phones uuid, mac, [model], ...value+
* PATCH /phones/MAC_ADDRESS value+
* DELETE /phones/MAC_ADDRESS\

* GET /models filter=usedonly|brand:BRAND
* GET /models/NAME 
* POST /models name, description, value+
* PATCH /models/NAME , description, value+
* DELETE /models/NAME (allowed only if this model isn't in use)