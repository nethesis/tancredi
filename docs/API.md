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

The entities discussed above acts like a variables *scope* during the generation
of provisioning files (see [Tancredi template files](./templates) for more
details). Each variable has its own meaning, defined by the template files and
their documentation.

## Media types

If not otherwise stated assume `application/json` media type for both request
and response.

Error responses use media type `application/problem+json` as defined by [RFC
7807](https://tools.ietf.org/html/rfc7807). See also the
[problem types list](problems).

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

### backgrounds/
* [GET /backgrounds](backgroundsGet) list of available background files
* [POST /backgrounds](backgroundsPost) upload a background file
* [DELETE /backgrounds/{file}](backgroundsDelete) delete a background file

### firmware/

* [GET /firmware](firmwareGet) list of available firmware files
* [POST /firmware](firmwarePost) upload a firmware file
* [DELETE /firmware/{file}](firmwareDelete) delete a firmware file

### ringtones/
* [GET /ringtones](ringtonesGet) list of available ringtone files
* [POST /ringtones](ringtonesPost) upload a ringtone file
* [DELETE /ringtones/{file}](ringtonesDelete) delete a ringtone file

### screensavers/
* [GET /screensavers](screensaversGet) list of available screensaver files
* [POST /screensavers](screensaversPost) upload a screensaver file
* [DELETE /screensavers/{file}](screensaversDelete) delete a screensaver file

## API authentication

The Tancredi administrative API does not implement an authentication method
itself, but it is possible to plug in a custom one:

(1) In the `/etc/tancredi.conf` configuration file specify the authentication
class name. For instance type `auth_class = "MyAuth"`.

(2) Put a file `MyAuth.php` in the `src/Entity` directory. This is an example
class:
```php
<?php
namespace Tancredi\Entity;

class MyAuth
{
    private $config;

    public function __construct($config = null) {
        // You can use configuration file variables here
        $this->config = $config;
    }

    public function __invoke($request, $response, $next)
    {
        if ($request->hasHeader('foo') and $request->getHeaderLine('foo') === 'bar') {
            $response = $next($request, $response);
        } else {
            return $response->withStatus(403);
        }
    }
}
```
