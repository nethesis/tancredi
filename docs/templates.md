# Tancredi template files

When a phone is powered on it asks one or more files containing its
configuration from an HTTP server. Tancredi generates the requested file
dynamically, by interpolating a template file with the values of the phone
configuration variables.

The template file has to be written in [Twig
1](https://twig.symfony.com/doc/1.x/) and is stored under the `data/templates`
directory. The system administrator can override an existing template file by
adding a file with the same name under the `templates-custom` directory.

The base path of the `templates-custom` directory is defined in the
`/etc/tancredi.conf` configuration file, by the `rw_dir` parameter.

## Template/Request binding: the `patterns.d` directory

When a phone requests a file, Tancredi establishes what template has to be
interpolated to build the response by looking at the `.ini` files under the
`data/patterns.d` directory.

Each `.ini` file in that directory defines one or more request matching rules.
The rule definition has the following lines:

1. The `[rule identifier]` section

1. The `pattern = ` line is a regexp matching the HTTP request file name. The
rule is effective when the match occurs. As rules are evaluated in alphabetical
order, the first match stops the rules evaluation.

1. The `scopeid = ` line is the identifier of the returned scope. Variable
values depend also on values inherited from parent scopes (see [Phone variables
inheritance](./API#phone-variables-inheritance)) for details) and from
"run-time" variables (see the section below).

1. The `template = ` line is the **name of a variable** containing the template
file name.  This makes possible to set an alternative template for a specific
phone.

This is an example of `.ini` file inside `patterns.d`:

```ini
[yealink-general]
pattern = "y000000000[0-9]{3}\.cfg"
scopeid = "yealink"
template = "yealink_general_template"
```

## External data sources for run-time variables

Sometimes it is useful to retrieve a variable value from an external data
source. It is possible to plug in a filter that adds or modifies the variables
passed to the template context.

_TODO: add an example of .conf file to plug in the filter code_

