# Developers

PyriteView is completely modular at its core and thus easily modified or augmented.  Plugins can be added to `modules/` and handle any existing event (see below) as well as provide additional classes and functions.  Each such file should declare its own namespace to avoid polluting the global environment.

The build process compiles `modules/*.css` into a single resource, so add-on modules should supply their own CSS file rather than modify the project's `pyriteview.css`.

Similarly, the build compiles `modules/*.js` into a single script, so add-on modules should supply their own JS module rather than modify `pyriteview.js`. **FIXME:** Do we need to bootstrap them?

## Events

PyriteView is somewhat event-driven using the simple and elegant [Sphido Events library](https://github.com/sphido/events).

In addition to its `on()`/`trigger()` and `add_filter()`/`filter()`, we add `pass()` which is a wrapper to `trigger()` which tests for the non-falsehood of its last return value.  In other words, if any of the triggered event handlers returned false, which also stops propagation, then `pass()` will return false instead of `trigger()`'s array of all results.  This is great for validation purposes where all handlers should agree.

### Global

#### startup

Invoked at the very start of the request process.  Used for initialization, for example session handling.

#### shutdown

Invoked at the very end of the request process.  Used for proper clean-up.

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
