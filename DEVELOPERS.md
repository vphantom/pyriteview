# Developers

PyriteView is completely modular  and thus easily modified or augmented.  Plugins can be added to `modules/` and handle any existing event (see below) as well as provide additional classes and functions.

The build process compiles `modules/*.css` into a single file, so add-on modules just need to supply their own separate CSS file at no cost.

Similarly, the build compiles `modules/*.js` into a single script, so add-on modules should supply their own JS module rather than modify `pyriteview.js`. **FIXME:** Do we need to bootstrap them?

To summarize, a typical plugin involved with all aspects of PyriteView would consist of three new files added to `modules/`.  For example:

* `modules/example.php`
* `modules/example.css`
* `modules/example.js`

If your CSS refers to additional resources, they should be nested, which will be handled by the build process.  For example:

* `modules/example/image.png`
* `modules/example/font.ttf`

**FIXME:** Does `cleancss` actually handle this gracefully?

Similarly, if more than one module-specific template file is created, it should be nested as well.  For example:

* `templates/example/foo.html`
* `templates/example/bar.html`

### Requirements

In addition to the run-time requirements, for building PyriteView you will also need:

* NodeJS for its package manager "NPM"

Run `make dev-init` to initially download the requirements for the build process.

Then, running `make distrib` any time will rebuild `client.css[.gz]` and `client.js[.gz]`.


## Template Files

The following template files have special meaning.  Any other name is available for use for building your output.

### head.html

Displayed and flushed to the browser as early as possible.  Should reference resources and *not* include a TITLE tag yet.

### body.html

If processing the request was successful, during `shutdown` this is displayed for the rest of the document, including TITLE, closing HEAD, etc.

It is supplied with variable `title` which contains the final title of the page and `body` which contains the output printed by all code that ran between `startup` and `shutdown`.

### statusXXX.html

When a non-200 HTTP status code was set, one of these is rendered instead of `body.html` and must thus provide the same structure.  Built-in codes in use:

**404** When no module declared support for the base of the requested URL.

**500** When a module handled the requested URL, but returned `false`.


## Database

Global variable `$db` is available with an instance of the `PDB` wrapper to `PDO`.  **FIXME:** Link to PDB documentation.


## Router

A simple `PATH_INFO` based router dispatches the main processing of a URL to an event named "route/_basename_" or "route/_basename_+_sectionname_".  Processing of this event is expected to return a non-false value, or else the router will issue an HTTP 500 error.

For example, if "/index.php/foo/bar/baz" is requested, the base route is deemed to be `foo` and, if previously declared using the static method below, would trigger the event `route/foo` with the rest of the path as an array argument.  Further, if `foo+bar` was declared, the trigger will instead be `route/foo+bar` with the shorter rest of that path as argument.

### Router::register(*basename*)

To avoid the most obvious possibilities of URL-based attacks, valid base routes need to be registered when your module is loaded.  Thus a typical route module would define an event handler for its base route and also call this to declare it.

If it is more complex and has sub-sections, each sub-route needs the same registration and event handler.  For example:

```php
class MyClass { ... }

Router::register('mystuff');
on('route/mystuff', 'MyClass::myStaticMethod');

Router::register('mystuff+edit');
on('route/mystuff+edit', 'MyClass::myEditMethod');

Router::register('mystuff+delete');
on('route/mystuff+delete', 'MyClass::myDeleteMethod');
```


## Access Control

The access control module creates static class `ACL` with the following static methods:

### ACL::can(*$verb*[, *$object*[, *$objectId*]])

Returns true if the current user is allowed to perform the action named `$verb`, either by itself or acting upon an object of type `$object`, possibly a specific instance of it.


## Events

With only the above exceptions, PyriteView is event-driven to keep its structure completely modular.  It uses the simple and elegant [Sphido Events library](https://github.com/sphido/events) with a couple of handy helper functions thrown in:

#### on($event, $callable[, $priority = 10])

Declare a handler for event named `$event`, which will be called with possible arguments.  (See details in `trigger()`.)  The optional priority allows us to guarantee an order in which handlers for a same event are called.  In PyriteView, those are between 1 and 99.  Examples:

```php
on('foo', 'MyClass::myStaticMethod');

on('bar', function ($arg1, $arg2) { return $arg2; }, 1);
```

#### trigger($event[, ...])

Calls all handlers registered for `$event`, with any additional arguments optionally passed in.  Returns an array of all the handlers' return values, in the order in which they were called.

#### grab($event[, ...])

Wrapper around `trigger()` which returns the _last_ return value of the handler chain.  This helps cases where events are used for the purpose of returning information.

#### pass($event[, ...])

Wrapper around `trigger()` which tests for the non-falsehood of its _last_ return value.  In other words, if any of the triggered event handlers returned false, which also stops propagation, then `pass()` will return false instead of `trigger()`'s array of all results.  This is great for validation purposes where all handlers should agree.

#### add_filter($event, $callable) / filter($event, $value)

Sphido Events includes a WordPress-inspired filtering mechanism, where chains of handlers can modify input supplied to `filter()`.  This can be useful for formatting add-ons, currency conversion and such.  Quoting the example straight from Sphido's documentation:

```php
add_filter('price', function($price) {
  return (int)$price . ' USD';
});

add_filter('price', function($price) {
  return 'The price is: ' . $price ;
});

echo filter('price', 100); // print The price is: 100 USD
```


### Global Events

#### install

Invoked when `index.php` is executed from the command line.  Use this to confirm the existence of any database tables your module may need, for example.

#### startup

Invoked at the very start of the request process.  Used for initialization, for example session handling or loading any database data you are guaranteed to need regardless of request specifics.

#### shutdown

Invoked at the very end of the request process.  Used for proper clean-up.

#### newuser

Triggered when the current user's identity or credentials have changed.  Useful for cache and data invalidation.


### Templating Events

#### http_status (*$code*)

The router triggers this to notify the template when we have a 404 or 500 situation.  You may trigger it for other situations as necessary, although after the `startup` phase is complete, headers are already sent to the browser and this will no longer affect the HTTP status, only which template gets displayed for the body of the page.  (See "Templating / Files".)

#### title (*$prepend*[, *$sep*])

Prepends `$prepend` to the current title to be displayed.  If the title wasn't empty, uses `$sep` as separator (default: " - ").

#### render (*$template*[, *$args[]*])

Render the named `$template` with optional supplied associative `$args[]`.


### Form Events

These events require the `$form_name` argument be supplied, which should be unique to the entire application, possibly matching the form's HTML `ID`.

#### form_begin (*$form_name*)

Triggered by templates just after opening an HTML `FORM` block, to allow the injection of control values to the form.

#### form_validate (*$form_name*)

Triggered by form handling code *before* processing form data.  This allows the cleanup of control values and should be triggered with `pass()` so that processing stops and the form doesn't get acted upon if any validation handler fails.


### Filtering Events

#### body (*$html*)

After the main content area of the current page is buffered, it is passed through this filter.  This allows, for example, automated table of contents generation, link substitutions, etc.


### Session Events

#### login (*$email*, *$password*)

Attempts logging in with the `User` module and saves the credentials in the current session if successful.  This should be triggered with `pass()` when processing a login form.

#### whoam

Returns the associative array describing the currently authenticated user, normally triggered with `grab()`.


### Logging Events

The optional logging module is used to create an audit trail in the database.

#### log (*$args[]*)

This should be triggered *after* a noteworthy action has taken place successfully.  Possible arguments in the associative array (depending on transaction type):

##### action

Typically one of: `create`, `update`, `delete` or your custom verbs.

##### [objectType]

Class of object being acted upon (i.e. 'article').

##### [objectId]

Specific ID of the object being acted upon.

##### [fieldName]

Name of object's field being affected.

##### [oldValue]

Previous value, if any, of the field being modified.  Requires `fieldName`.

##### [newValue]

New value of the field being modified.  Requires `fieldName`.

