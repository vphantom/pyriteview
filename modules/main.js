'use strict';

var $ = global.jQuery = require('jquery');
var bootstrap = require('bootstrap');  // eslint-disable-line no-unused-vars
var parsley = require('parsleyjs');  // eslint-disable-line no-unused-vars
var timeago = require('timeago.js');  // eslint-disable-line no-unused-vars
var selectize = require('selectize');  // eslint-disable-line no-unused-vars
var selectizeRender = {};

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

  var excludedInputs = 'input[type=button], input[type=submit], input[type=reset], input[type=hidden], .input-like input';  // eslint-disable-line max-len

  // A valid e-mail for us has: nospaces, '@', nospaces, '.', nospaces
  // Actual RFC822 implementations are pages long and wouldn't benefit us much.
  var regexEMail = /^[^@ ]+@[^@ .]+\.[^@ ]+$/;
  var regexNameEMail = /^([^<]+)<([^@ ]+@[^@ .]+\.[^@ ]+)>$/;

  // Hide-away fields
  $('.hideaway-focus').on('focus', function() {
    $(this)
      .closest('.hideaway')
      .css({
        position: 'relative',
        left    : 0
      });
  });

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
  $('form.form-leftright > input, form.form-leftright > .btn-group, form.form-leftright > select, form.form-leftright > textarea, form.form-leftright > .input-like')  // eslint-disable-line max-len
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

      if (!$(this).hasClass('btn-group') && !$(this).hasClass('input-group')) {
        $(this).addClass('form-control');
      }

      $(this)
        .attr('name', id)
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

  // Selectize some advanced selects
  //
  selectizeRender = {
    option_create: function(data, escape) {  // eslint-disable-line camelcase
      return '<div class="create">+ <strong>'
        + escape(data.input)
        + '</strong>&hellip;</div>'
      ;
    }
  };
  $('select.advanced').selectize({
    plugins  : ['remove_button'],
    highlight: false
  });
  $('select.keywords').selectize({
    plugins     : ['remove_button'],
    highlight   : false,
    delimiter   : ';',
    create      : true,
    createOnBlur: true,
    openOnFocus : false,
    maxItems    : 6,
    // Work around default template including the English word "Add"
    render      : selectizeRender
  });

  /**
   * Clean up raw item into expected "user" structure
   *
   * Input item is expected to have 'text' and 'value' properties from which
   * 'name' and 'email' properties will be derived if 'email' is missing.
   * Conversely if 'email' is present but 'value' is not, then 'value' gets
   * the e-mail address.
   *
   * @param {Object} item Data
   *
   * @return {Object} Formatted item
   */
  function itemToUser(item) {
    var match;

    item.text = item.text.trim();
    if (item['email'] === undefined) {
      match = item.text.trim().match(regexNameEMail);
      if (match !== null) {
        item.email = match[2];
        item.name = match[1];
      } else {
        item.email = item.text;
        item.name = null;
      }
    }
    if (item['value'] === undefined
        || !Number.isInteger(Number(item['value']))
       ) {
      item.value = item.email;
    }
    return item;
  }
  selectizeRender['item'] = function(item, escape) {
    item = itemToUser(item);
    return '<div class="name_email_tag">'
      + (item.name ? '<span class="name">' + escape(item.name) + '</span>' : '')
      + '<span class="email">' + escape(item.email) + '</span>'
      + '</div>';
  };
  selectizeRender['option'] = function(item, escape) {
    var label;
    var caption;

    item = itemToUser(item);
    label = item.name || item.email;
    caption = item.name ? item.email : null;
    return '<div class="name_email_label">'
      + '<span class="name">' + escape(label) + '</span>'
      + (caption ? '<span class="email">' + escape(caption) + '</span>' : '')
      + '</div>';
  };
  $('select.users').selectize({
    plugins  : ['remove_button'],
    highlight: false,
    persist  : false,
    render   : selectizeRender
  });

  // Creating users
  $('select.users-create').selectize({
    plugins     : ['remove_button'],
    highlight   : false,
    createOnBlur: true,
    persist     : false,
    render      : selectizeRender,
    createFilter: function(input) {
      return regexEMail.test(input) || input.match(regexNameEMail) !== null;
    },
    create: function(input) {
      var match = input.match(regexNameEMail);
      var opt = false;

      if (match !== null) {
        opt = {
          text : input,
          value: match[2],
          email: match[2],
          name : match[1]
        };
      }
      if (regexEMail.test(input)) {
        opt = {
          text : input,
          value: input,
          email: input,
          name : null
        };
      }

      return opt;
    },
    onOptionAdd: function(value, data) {
      var sel = this;
      var form = $('#myModal form');

      // Not much works unless we let Selectize finish first.
      setTimeout(function() {
        form.find('input').val(null);
        form.find('select').prop('selectedIndex', 0);
        form.parsley().reset();

        $('#myModal .modal-title .text').text(value);
        $('#myModal input[name=email]').val(value);

        $('#myModal').modal({
          backdrop: 'static',
          keyboard: false,
          show    : true
        });
        setTimeout(
          function() {
            form
              .find('input:visible, select:visible')
              .first()
              .focus();
          },
          350
        );

        $('#myModal .modal-footer button').on('click', function() {
          var outform = $($('#myModal').attr('data-appends'));
          var outbase = $('#myModal').attr('data-append-base');
          var outkey  = $('#myModal').attr('data-append-key');
          var outdata = {};

          if (form.parsley().validate()) {
            $(this).off('click');
            form.serializeArray().forEach(function(item) {
              outdata[item.name] = item.value;
            });
            outkey = outdata[outkey];

            data['name'] = outdata['name'];

            Object.keys(outdata).forEach(function(key) {
              outform.append(
                $('<input />')
                  .attr('type', 'hidden')
                  .attr('name', outbase + '[' + outkey + '][' + key + ']')
                  .attr('value', outdata[key])
              );
            });

            // Destroy the popover before its element disappears.
            // tag.popover('destroy');
            $('#myModal').modal('hide');

            // Force a refresh of our items where refreshItems() won't.
            // Thanks to: https://github.com/selectize/selectize.js/issues/1162
            // sel.clearCache();
            sel.updateOption(value, sel.options[value]);

            // Work around strange bug which reopens the select after creation
            sel.close();
          }
        });
      });
    }
  });

  // Set parsley to found language instead of last loaded
  parsley.setLocale(lang);

  // Integrate Parsley with Twitter Bootstrap
  // Initially inspired by https://gist.github.com/askehansen/6809825
  // ...and http://jimmybonney.com/articles/parsley_js_twitter_bootstrap/
  // CAUTION: $.fn.parsley.defaults({...}) was IGNORED.
  $('form').parsley({
    // exclude :hidden for non-modal forms?
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

