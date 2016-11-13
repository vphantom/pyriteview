# Developers

PyriteView is completely modular at its core and thus easily modified or augmented.  Plugins can be added to `modules/` and handle any existing event (see below) as well as provide additional classes and functions.  Each such file should declare its own namespace to avoid polluting the global environment.

The build process compiles `modules/*.css` into a single resource, so add-on modules should supply their own CSS file rather than modify the project's `pyriteview.css`.

Similarly, the build compiles `modules/*.js` into a single script, so add-on modules should supply their own JS module rather than modify `pyriteview.js`. **FIXME:** Do we need to bootstrap them?

To summarize, a typical plugin involved with all aspects of PyriteView would consist of three new files added to `modules/`.  For example:

* `modules/example.php`
* `modules/example.css`
* `modules/example.js`

If your CSS refers to additional resources, they should be nested, which will be handled by the build process.  For example:

* `modules/example/image.png`
* `modules/example/font.ttf`

**FIXME:** Does `cleancss` actually handle this gracefully?

## Requirements

In addition to the run-time requirements, for building PyriteView you will also need:

* NodeJS for its package manager "NPM"

Run `make dev-init` to initially download the requirements for the build process.

Then, running `make distrib` any time will rebuild `client.css`, `client.css.gz`, `client.js` and `client.js.gz`.

## Database

Global variable `$db` is available with an instance of the `PDB` wrapper to `PDO`.  **FIXME:** Link to PDB documentation.

## Templating

The templating module creates static class `Templating` with the following static methods:

### Templating::title(*$prepend*[, *$sep*])

Prepends `$prepend` to the current title to be displayed.  If the title wasn't empty, uses `$sep` as separator (default: " - ").

### Templating::render(*$template*[, *$args[]*])

Render the named `$template` with optional supplied associative `$args[]`.

## Logging

The logging module is used to create an audit trail in the database.  It creates class `Log` with the following static methods:

### add(*$args[]*)

This should be called once a noteworthy action has taken place successfully.  Possible arguments (depending on transaction type):

#### action

Typically one of: `create`, `update`, `delete` or your custom verbs.

#### [objectType]

Class of object being acted upon (i.e. 'article').

#### [objectId]

Specific ID of the object being acted upon.

#### [fieldName]

Name of object's field being affected.

#### [oldValue]

Previous value, if any, of the field being modified.  Requires `fieldName`.

#### [newValue]

New value of the field being modified.  Requires `fieldName`.

## User

The user module creates static class `User` with the following static methods:

### User::whoami()

Returns the associative array describing the currently authenticated user.

## Session

The session module creates static class `Session` with the following static methods:

### login(*$email*, *$password*)

Attempts logging in with the `User` module and saves the credentials in the current session if successful.  This should be invoked when processing a login form.

## Access Control

The access control module creates static class `ACL` with the following static methods:

### ACL::can(*$verb*[, *$object*[, *$objectId*]])

Returns true if the current user is allowed to perform the action named `$verb`, either by itself or acting upon an object of type `$object`, possibly a specific instance of it.

## Events

PyriteView is somewhat event-driven using the simple and elegant [Sphido Events library](https://github.com/sphido/events).

In addition to its `on()`/`trigger()` and `add_filter()`/`filter()`, we add `pass()` which is a wrapper to `trigger()` which tests for the non-falsehood of its last return value.  In other words, if any of the triggered event handlers returned false, which also stops propagation, then `pass()` will return false instead of `trigger()`'s array of all results.  This is great for validation purposes where all handlers should agree.

### Global

#### install

Invoked when `index.php` is executed from the command line.  Use this to confirm the existence of any database tables your module may need, for example.

#### startup

Invoked at the very start of the request process.  Used for initialization, for example session handling.

#### shutdown

Invoked at the very end of the request process.  Used for proper clean-up.

#### login

Triggered when the current user's identity or credentials have changed.  Useful for cache invalidation.

### Forms

These events require the `$form_name` argument be supplied, which should be unique to the entire application, possibly matching the form's HTML `ID`.

#### form_begin (*$form_name*)

Triggered by templates just after opening an HTML `FORM` block, to allow the injection of control values to the form.

#### form_validate (*$form_name*)

Triggered by form handling code *before* processing form data.  This allows the cleanup of control values and should be triggered with `pass()` so that processing stops and the form doesn't get acted upon if any validation handler fails.

### Filters

Sphido Events also includes a filter mechanism, where chains of handlers can modify input supplied to `filter()`.  This can be useful for formatting add-ons, currency conversion and such.

#### body (*$html*)

After the main content area of the current page is built, it is passed through this filter.  This allows, for example, automated table of contents generation.
