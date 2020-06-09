---
layout: default
title: Variables
---

<h1>Variables</h1>
{% assign sorted_vars = site.data.variables | sort:'name' %}
{% for var in sorted_vars %}

<h2>{{ var.name }}</h2>

{% if var.access == "ro" %}
    <p><em>Read-only access</em></p>
{% endif %}

{% if var.index %}
    <p>Indexed by <em>{{ var.index }}</em></p>
{% endif %}

<p>{{ var.description | markdownify }}</p>

{% if var.domain %}
    <p><tt>{{ var.datatype }}</tt> - {{ var.domain | markdownify }}</p>
{% else %}
    <p><tt>{{ var.datatype }}</tt></p>
{% endif %}

{% endfor %}
