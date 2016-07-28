<?php

$version = array_shift( explode( '.', $_SERVER['alfred_version'] ) );
$alfred_version = ( 3 === $version ) ? 'Alfred 3' : 'Alfred 2';
define( 'ALFRED_VERSION', $alfred_version );
define( 'CACHE_DIR', $_SERVER['alfred_workflow_cache'] );
define( 'DATA_DIR', $_SERVER['alfred_workflow_data'] );
