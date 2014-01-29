<?php

include('./plist-functions.php');

$dir = escapeshellarg("/Users/Sven/Dropbox/app-syncing/alfred2/Alfred.alfredpreferences/workflows/user.workflow.28127FC5-236A-4A9A-9FA5-EA2A3C67AA12");

echo getName($dir);

// try {
// 	$test = file_get_contents("https://raw.github.com/shawnrice/alfred-2-caffeinate-workflow/master/todo.txt");
// 	print_r($test);
// } catch (Exception $e) {
// 	print_r($e);
// }


?>
