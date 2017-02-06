'use strict';

var $ = global.jQuery = require('jquery');

// Non-English locales for timeago.js and ParsleyJS
// (This is necessary so that Browserify bundles them all at build time.)
global.__timeago.register('fr', require('timeago.js/locales/fr'));
require('parsleyjs/dist/i18n/fr');

$().ready(function() {
  // Your custom code here
});
