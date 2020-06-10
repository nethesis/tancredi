---
title: Home
nav_order: 0
---

# Tancredi

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

See [Tancredi API v1](./API) for details.

## Template files for phone provisioning

The variables defined with the administrative API can be used in the template
files along with other run-time defined variables. The provided template files 
can be easily overridden for specific needs

See [Templates](./templates) for details.
