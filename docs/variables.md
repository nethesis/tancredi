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
</p>
<p>{{ var.description | markdownify }}</p>

<p><tt>{{ var.datatype }}</tt>
{% if var.domain %}
- {{ var.domain | markdownify }}
{% endif %}
</p>
{% endfor %}
