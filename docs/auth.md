---
parent: Tancredi API v1
---

## API authentication

The Tancredi administrative API does not implement an authentication method by
itself, but it is possible to plug in a custom one:

1. In the `/etc/tancredi.conf` configuration file specify the authentication
class name. For instance type `auth_class = "MyAuth"`.

2. Put a file `MyAuth.php` in the `src/Entity` directory. This is an example
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
