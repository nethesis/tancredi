# tancredi

Tancredi is a *phone provisioning engine* ideal for internet deployments.

## Phone provisioning

An IP phone connects to Tancredi over **HTTP** and fetches some files containing
its configuration.

- The **phone** provisioning URL is protected by a **temporary random secret
  token** and is unique to each phone.

- Configuration files are generated dynamically starting from a set of
  **templates** specific to the phone **model** and some phone and model
  variables.

## Administrative API

The phone and model variables can be managed with a private API endpoint.
Tancredi does not provide an administrative user interface, however you can
build one based on its management endpoint.

See [API](API) for details.

## Installation

Tancredi requires at least PHP 5.6 to run.

[...]

## Configuration

Tancredi can be configured unsing '/etc/tancredi.conf' configuration file. there
is a tancredi.conf.sample configuration file in the root directory that can be
copied and renamed in /etc and used as template.
