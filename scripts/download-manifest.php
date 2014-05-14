<?php

if ( file_put_contents( "manifest.xml", file_get_contents( "https://raw.github.com/packal/repository/master/manifest.xml" ) ) ) echo "true";
else echo "false";
