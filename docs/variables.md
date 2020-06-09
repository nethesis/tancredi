---
layout: default
title: Variables
---

<h1>Variables</h1>
{% assign sorted_vars = site.data.variables | sort:'name' %}
{% for var in sorted_vars %}
<h2>{{ var.name }}</h2>
<p><em>{{ var.access }}</em> - <tt>{{ var.datatype }}</tt>
{% if var.index %}
<br>Indexed by <em>{{ var.index }}</em>
{% endif %}
</p>
<p>{{ var.description | markdownify }}</p>
{% endfor %}
