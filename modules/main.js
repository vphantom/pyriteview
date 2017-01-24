'use strict';

var $ = global.jQuery = require('jquery');
var bootstrap = require('bootstrap');  // eslint-disable-line no-unused-vars
var parsley = require('parsleyjs');  // eslint-disable-line no-unused-vars
var timeago = require('timeago.js');  // eslint-disable-line no-unused-vars
var selectize = require('selectize');  // eslint-disable-line no-unused-vars

// Non-English locales for timeago.js
// (This is necessary so that Browserify bundles them all at build time.)
timeago.register('fr', require('timeago.js/locales/fr'));

// Add all useful non-English locales to Parsley
// (This is necessary so that Browserify bundles them all at build time.)
require('parsleyjs/dist/i18n/fr');

$().ready(function() {
  // Get language code from HTML tag
  // (Default is just in case we miss it.  It _should_ always be set.)
  var lang = $('html').attr('lang') || 'en';

  var excludedInputs = 'input[type=button], input[type=submit], input[type=reset], input[type=hidden], :hidden, .input-like input';  // eslint-disable-line max-len

  // Bootstrap-ize forms before enabling Parsley on them

  // LARGE FORMS
  //
  // leftright: labels on the left, one input/button per line, errors below
  // tight: labels and errors inside inputs, button on own line
  //
  $('form.form-leftright, form.form-tight').each(function() {
    $(this)
      .attr('name', $(this).attr('id'))
      .addClass('form-horizontal')
    ;
  });
  $('form.form-leftright input, form.form-leftright select, form.form-leftright textarea, form.form-leftright .input-like')  // eslint-disable-line max-len
    .not(excludedInputs)
    .each(function() {
      var id        = $(this).attr('id');
      var label     = $(this).attr('data-label');
      var fgClasses = '';
      var icon      = null;

      if ($(this).is('[class*="feedback-"]')) {
        fgClasses = ' has-feedback has-feedback-left';
        icon = $(this)
          .attr('class')
          .match(/\bfeedback-([a-zA-Z0-9_-]+)\b/)[1];
      }

      $(this)
        .attr('name', id)
        .addClass('form-control')
        .wrap('<div class="form-group' + fgClasses + '"></div>')
        .parent()
        .prepend(
          '<label for="'
          + id
          + '" class="col-sm-2 control-label">'
          + label
          + '</label>'
        )
      ;
      $(this).wrap('<div class="col-sm-10"></div>');
      if (icon) {
        $(this).after(
            '<span class="form-control-feedback glyphicon glyphicon-'
            + icon
            + '"></span>'
          );
      }
    }
  );
  $('form.form-tight input, form.form-tight select, form.form-tight textarea')
    .not(excludedInputs)
    .each(function() {
      var id      = $(this).attr('id');
      var label   = $(this).attr('data-label');
      var colsize = $(this).attr('data-colsize') || 6;
      var fgClasses = '';
      var icon      = null;

      if ($(this).is('[class*="feedback-"]')) {
        fgClasses = ' has-feedback has-feedback-left';
        icon = $(this)
          .attr('class')
          .match(/\bfeedback-([a-zA-Z0-9_-]+)\b/)[1];
      }

      $(this)
        .attr('name', id)
        .addClass('form-control')
        .wrap(
            '<div class="input-block col-sm-'
            + colsize
            + fgClasses
            + '"></div>'
          )
        .parent()
        .prepend('<label for="' + id + '">' + label + '</label>')
      ;
      if (icon) {
        $(this).after(
            '<span class="form-control-feedback glyphicon glyphicon-'
            + icon
            + '"></span>'
          );
      }
    }
  );
  $('form.form-leftright button, form.form-tight button[type=submit]')
    .not('.input-like button')
    .each(function() {
      $(this)
        .addClass('btn btn-default')
        .wrap('<div class="form-group"></div>')
        .wrap('<div class="col-sm-offset-2 col-sm-10"></div>')
      ;
    }
  );

  // INLINE FORMS
  //
  $('form.form-compact').each(function() {
    $(this)
      .attr('name', $(this).attr('id'))
      .addClass('form-inline')
    ;
  });
  $('form.form-compact input, form.form-compact select, form.form-compact textarea')  // eslint-disable-line max-len
    .not(excludedInputs)
    .each(function() {
      var id    = $(this).attr('id');
      var label = $(this).attr('data-label');

      $(this)
        .attr('name', id)
        .addClass('form-control')
        .wrap('<div class="form-group"></div>')
        .parent()
        .prepend(
          '<label for="'
          + id
          + '" class="sr-only">'
          + label
          + '</label>'
        )
      ;
    }
  );
  $('form.form-compact button').each(function() {
    $(this)
      .addClass('btn btn-default')
    ;
  });

  // Styled file inputs
  $('label input[type="file"]').each(function() {
    var id = $(this).attr('id');

    $('#' + id + '_name').hide();
    $(this).on('change', function() {
      $('#' + id + '_name')
        .text($(this).val())
        .show()
      ;
      $('#' + id + '_submit')
        .removeClass('disabled')
        .attr('disabled', false)
      ;
    });
  });

  // Selectize on advanced selects
  $('select.advanced').selectize({
    plugins  : ['remove_button'],
    highlight: false
  });

  // Set parsley to found language instead of last loaded
  parsley.setLocale(lang);

  // Integrate Parsley with Twitter Bootstrap
  // Initially inspired by https://gist.github.com/askehansen/6809825
  // ...and http://jimmybonney.com/articles/parsley_js_twitter_bootstrap/
  // CAUTION: $.fn.parsley.defaults({...}) was IGNORED.
  $('form').parsley({
    excluded    : excludedInputs + ', [disabled]',
    successClass: 'has-success',
    errorClass  : 'has-error',
    classHandler: function(el) {
      // This differs from all examples I could find!
      return $(el.$element).closest('.form-group');
    },
    errorsContainer: function() {},
    errorsWrapper  : '<span class="input-error"></span>',
    errorTemplate  : '<span></span>'
  });

  // Initialize timeago.js
  new timeago().render($('.timeago'), lang);  // eslint-disable-line new-cap
});

