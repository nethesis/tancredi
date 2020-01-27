# tancredi

Tancredi is a *phone provisioning engine* ideal for internet deployments.

## Phone provisioning

An IP phone connects to Tancredi over **HTTP** and fetches some files containing
its configuration.

- The **phone** provisioning URL is protected by a **temporary random secret
  token** and is unique to each phone.

- Configuration files are generated dynamically starting from a set of
  **template files** specific to the phone **model** and from phone and model
  **variables**.

## Administrative API

The phone and model variables can be managed with a private API endpoint.
Tancredi does not provide an administrative user interface, however you can
build one based on its management endpoint.

See [API](API) for details.

## Template files for phone provisioning

The variables defined with the administrative API can be used in the template
files along with other run-time defined variables.

See [Template files](https://github.com/nethesis/tancredi/blob/master/docs/templates.md) for details.

## Installation

Tancredi requires at least PHP 5.6 to run.

Clone the repository with 
`git clone https://github.com/nethesis/tancredi.git`
Go into directory and install dependencies with Composer
```
cd tancredi
curl -sS https://getcomposer.org/installer | php
php composer.phar install
```

Tancredi needs write access to data/first_access_tokens, data/not_found_scopes, data/scopes, data/templates-custom, data/tokens. If you use apache as http server, give apache user write permission to those files and directories:
```
chown -R root:apache data/{first_access_tokens,not_found_scopes,scopes,templates-custom,tokens}
chmod g+w data/{first_access_tokens,not_found_scopes,scopes,templates-custom,tokens}
```

You can change the base path of read/write data directories and read only data directories by changing those variables into `/etc/tancredi.conf`

Create log file and make sure Tancredi user is allowe to write into it
```
mkdir -p /var/log/tancredi
chown -R root:apache /var/log/tancredi
chmod g+w /var/log/tancredi
```

## Configuration

Add configuration to your HTTP server to allow reaching `public/provisioning.php`
and `public/api-v1.php`.

Tancredi can be configured unsing '/etc/tancredi.conf' configuration file. there
is a `tancredi.conf.sample` configuration file in the root directory that can be
copied and renamed in /etc and used as template.
