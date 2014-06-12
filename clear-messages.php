<?php

require_once( 'alfred.bundler.php');

$tn=__load('terminal-notifier', 'default', 'utility');

sleep(15);

exec( "$tn -remove 'packal-updater-settings'" );
