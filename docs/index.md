---
title: Home
nav_order: 0
---

# Tancredi

Tancredi is a *phone provisioning engine* ideal for internet deployments,
released under the *GNU Affero General Public License v3.0*.

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

See [Tancredi API v1](./API) for details.

## Template files for phone provisioning

The variables defined with the administrative API can be used in the template
files along with other run-time defined variables. The provided template files 
can be easily overridden for specific needs

See [Templates](./templates) for details.

## Contributions

The project is hosted on GitHub: pull requests are welcome.

The initial development of Tancredi was sponsored by Nethesis as part of [NethVoice 14](https://www.nethesis.it/nethvoice/).

Send a mail to sviluppo@nethesis.it for any question.

## Other projects

The [Falconieri](http://github.com/nethesis/falconieri) project is a RPS (Redirect and Provisioning Service) gateway
that helps to store the phone provisioning URL in the phone vendor redirect service. Modern IP phones can contact the
phone vendor redirect service at boot time and discover their PBX address with it.
