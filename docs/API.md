---
has_children: true
has_toc: false
nav_order: 4
---

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
of provisioning files (see [Templates]({{ '/templates' | relative_url }}) for more
details). Each variable has its own meaning, defined by the template files and
their documentation.

## Media types

If not otherwise stated assume `application/json` media type for both request
and response.

Error responses use media type `application/problem+json` as defined by [RFC
7807](https://tools.ietf.org/html/rfc7807). See also the
[problem types list]({{ '/problems' | relative_url }}).

## String formats

* EUI-48/MAC address (IEEE 802) six groups of two hexadecimal digits, separated
  by hyphens (-) in transmission order (e.g. `01-23-45-67-89-AB`)
