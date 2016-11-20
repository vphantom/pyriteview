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

A single file is mandatory: `layout.html` which is divided into five blocks:

### init

Displayed and flushed to the browser as early as possible.  Should reference resources and *not* include a TITLE tag yet.

Variables available: `http_status`

### head / foot

If processing the request was successful, during event `shutdown` this is displayed for the rest of the document, including TITLE, closing HEAD, etc.

Variables available: `title`

### head_error / foot_error

When a non-200 HTTP status code was set, these are rendered instead and must thus provide the same structure.

Variables available: `http_status`, `title`

Typical status codes:

**404** When no module declared support for the base of the requested URL.

**500** When a module handled the requested URL, but returned `false`.

For debugging purposes, `foot` and `foot_error` also have special variable `body` available which contains the *escaped* output captured from all code that ran between `startup` and `shutdown` events.  Output by any code not using the `render` event to display templates safely ends up here.


## Database

Global variable `$db` is available with an instance of the `PDB` wrapper to `PDO`.  See [PDB documentation](lib/PDB.md).


## User

An associative array of the current user's information, if one is logged in, is available as  `$_SESSION['USER_INFO']`.

Note that the first three columns defined in our example `modules/user.php` should remain intact: we need a unique ID and we expect to identify users by e-mail address and password, which itself is stored as a one-way hash in the database.


## Router

A simple `PATH_INFO` based router dispatches the main processing of a URL to an event named "route/_basename_" or "route/_basename_+_sectionname_".  Processing of this event is expected to return a non-false value, or else the router will issue an HTTP 500 error.

For example, if "/index.php/foo/bar/baz" is requested, the base route is deemed to be `foo` and, if previously declared using the static method below, would trigger the event `route/foo` with the rest of the path as an array argument.  Further, if `foo+bar` was also declared, the event will instead be `route/foo+bar` with the shorter rest of that path as argument.

For example:

```php
class MyClass { ... }

// Handle '/mystuff'
on('route/mystuff', 'MyClass::myStaticMethod');

// Handle '/mystuff/edit'
on('route/mystuff+edit', 'MyClass::myEditMethod');

// Handle /mystuff/delete
on('route/mystuff+delete', 'MyClass::myDeleteMethod');
```

When an empty or root URL is processed, the route will resolve to `main`, therefore to event `route/main`.

Any URL not resolvable to an event handler will yield an HTTP 404 error.


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

#### filter($event, $value)

Sphido Events includes a WordPress-inspired filtering mechanism, where chains of handlers can modify input supplied to `filter()`.  This can be useful for formatting add-ons, currency conversion and such.  Note that `add_filter()` is just an alias for `on()`  Quoting the example straight from Sphido's documentation:

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

Generate unique form ID to guarantee one-time validity which expires with the current session.  Should be triggered with `grab()` within templates at the beginning of HTML `FORM` blocks.

#### form_validate (*$form_name*)

Trigger in your form handling code *before* processing form data.  This allows the cleanup of control values and should be triggered with `pass()` so that processing stops and the form doesn't get acted upon if any validation handler fails.


### Filtering Events

#### body (*$html*)

After the main content area of the current page is buffered, it is passed through this filter.  This allows, for example, automated table of contents generation, link substitutions, etc.


### Session Events

#### login (*$email*, *$password*)

Attempts logging in and saves the credentials in the current session if successful.  This should be triggered with `pass()` when processing a login or ID confirmation form.

#### can (*$verb*[, *$objectType*[, *$objectId*]])

Returns true if the current user is allowed to perform the action named `$verb`, either by itself or acting upon an object of type `$objectType`, possibly a specific instance `$objectId`.  This should be triggered with `pass()` to obtain a clean boolean result.


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

