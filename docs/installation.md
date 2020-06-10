---
nav_order: 1
---

# Installation

Tancredi is built on the PHP Slim 3 framwork and requires at least 
a PHP 5.6 web stack to run.  For instance, an Apache or Nginx web server 
with PHP-FPM are typical setup.

1. Clone the repository with 
       
       git clone https://github.com/nethesis/tancredi.git`

1. Go into directory and install dependencies with Composer

       cd tancredi
       curl -sS https://getcomposer.org/installer | php
       php composer.phar install

1. Tancredi reads its configuration from the `/etc/tancredi.conf`.
   Copy the `tancredi.conf.sample` file from the source code repository to `/etc/tancredi.conf` and
   modify it as needed. 

1. Set up write access permissions on 

   * `data/first_access_tokens`, 
   * `data/not_found_scopes`, 
   * `data/scopes`, 
   * `data/templates-custom`, 
   * `data/tokens`
   
   On CentOS 7 the `apache` user is a good choice:

       chown -R root:apache data/{first_access_tokens,not_found_scopes,scopes,templates-custom,tokens}
       chmod g+w data/{first_access_tokens,not_found_scopes,scopes,templates-custom,tokens}

1. Create a directory for log files and make sure the Tancredi user has granted write access.

       mkdir -p /var/log/tancredi
       chown -R root:apache /var/log/tancredi
       chmod g+w /var/log/tancredi

1. Configure the HTTP server to allow reaching `public/provisioning.php`
and `public/api-v1.php`. For instance set up the URL mapping rules as follow: 

       https://host.fqdn/provisioning/ => public/provisioning.php
       https://host.fqdn/tancredi/api/v1/ => public/api-v1.php

