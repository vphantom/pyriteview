'use strict';

var $ = global.jQuery = require('jquery');

$().ready(function() {
  var articleEditForm = $('form#articles_edit');
  var copyrightLink = $('a#copyright_link');

  /**
   * Update copyright URL
   *
   * The base found in the document in its initial state is appended 't' and
   * 'aa' CGI variables containing respectively the title and a
   * semicolon-delimited list of author user IDs.
   *
   * @return {void}
   */
  function updateCopyright() {
    copyrightLink.attr(
        'href',
        copyrightLink.attr('baseHREF')
        + '&t='
        + encodeURIComponent(articleEditForm.find('#title').val())
        + '&aa='
        + encodeURIComponent(articleEditForm.find('#authors\\[\\]').val())
    );
  }

  if (articleEditForm.length) {
    copyrightLink.attr('baseHREF', copyrightLink.attr('href'));

    // Let things settle, then initialize copyright URL
    setTimeout(updateCopyright, 1000);

    // Update copyright URL when relevant fields are modified
    articleEditForm.find('#title').on('change', updateCopyright);
    articleEditForm.find('#authors\\[\\]').on('change', updateCopyright);
  }
});
