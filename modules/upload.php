<?php

namespace PyriteView\Upload;

on('startup', function () {
    // TODO: Process any incoming files
    // I guess determine their MIME type and not much else?
}, 99);

/*
Instead of

if ($_FILES['some_name']['type'] == 'image/jpeg') {

try:

$finfo = new finfo(FILEINFO_MIME_TYPE);
$fileContents = file_get_contents($_FILES['some_name']['tmp_name']);
$mimeType = $finfo->buffer($fileContents);


I guess it's too early to implement this...

*/

?>
