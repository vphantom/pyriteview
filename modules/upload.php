<?php

namespace PyriteView\Upload;


/*
Instead of

if ($_FILES['some_name']['type'] == 'image/jpeg') {

try:

$finfo = new finfo(FILEINFO_MIME_TYPE);
$fileContents = file_get_contents($_FILES['some_name']['tmp_name']);
$mimeType = $finfo->buffer($fileContents);
*/

?>
