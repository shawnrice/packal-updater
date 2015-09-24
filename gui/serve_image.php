<?php
    $file = htmlspecialchars( $_GET[ 'file' ] );
    header('Content-Type: image/jpeg');
    readfile( "$file" );
?>