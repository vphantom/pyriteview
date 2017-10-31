'use strict';

var $ = global.jQuery = require('jquery');

$().ready(function() {
  var $articleEditor = $('form#articles_edit');

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
    var $link = $('a#copyright_link');

    if (!$link.attr('baseHREF')) {
      $link.attr('baseHREF', $link.attr('href'));
    }
    $link.attr(
        'href',
        $link.attr('baseHREF')
        + '&t='
        + encodeURIComponent($articleEditor.find('#title').val())
        + '&aa='
        + encodeURIComponent($articleEditor.find('#authors\\[\\]').val())
    );
  }

  if ($articleEditor.length) {
    // Let page settle, then initialize copyright URL
    setTimeout(updateCopyright, 1000);

    // Update copyright URL when relevant fields are modified
    $articleEditor.find('#title').on('change', updateCopyright);
    $articleEditor.find('#authors\\[\\]').on('change', updateCopyright);

    // Only one of the two plagiarism inputs is actually required
    $articleEditor.find('#plagiarism_ok').on('change', function() {
      var $textarea = $('#plagiarism');

      if ($(this).prop('checked')) {
        $textarea.val('1').css('display', 'none');
      } else {
        $textarea.val('').css('display', 'block');
      }
    });
  }

  // Persistent dropdown: stay open if click was for an action.
  $('.dropdown.persistent a, .dropdown.persistent input, .dropdown.persistent button, .dropdown.persistent label').on(  // eslint-disable-line max-len
    'click',
    function(e) {
      e.stopPropagation();
    }
  );
});
