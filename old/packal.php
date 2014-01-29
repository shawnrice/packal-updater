<?php

date_default_timezone_set("America/NEW_YORK");
ini_set("auto_detect_line_endings", "yes");
error_reporting(0);
$arg = $argv;



require_once('workflows.php');
$w = new Workflows();

$d = $w->data();
//$dir = str_replace(' ', '\ ', $dir);
if (! file_exists($w->data())) {
	mkdir($w->data());
}

if (! file_exists($dir . '/icons')) {
	mkdir($d . '/icons');
}

if (! file_exists($dir . '/metadata')) {
	mkdir($d . '/metadata');
}

// echo $w->data() . '\n';

// var_dump($argv);



// $json = $w->request( 'https://raw.github.com/packal/repo/master/manifest.json' );
$json = file_get_contents( 'data.json' );
$json = json_decode($json, $assoc = TRUE);
$results = array();
//$dir = str_replace(' ', '\ ', $dir);


/*
$dir = scandir('../');
if(($key = array_search('.', $dir)) !== false) {
    unset($dir[$key]);
}
if(($key = array_search('..', $dir)) !== false) {
    unset($dir[$key]);
}
*/

/*
$workflows = array();
foreach ($dir as $k => $d) {
	$workflows[$k]['name'] = $w->get('name', '../'.$d.'/info.plist');
	$workflows[$k]['bundle'] = $w->get('bundleid', '../'.$d.'/info.plist');
	$workflows[$k]['uuid'] = str_replace('user.workflow.', '', $d);

}
print_r($workflows);
*/

$total = count($json['nodes']);
$tmp = array(
			'uid' => 1,
			'arg' => 'update',
			'title' => 'Packal — the package repository',
			'subtitle' => "Found $total packages.",
			'icon' => 'icon.png',
			'valid' => 'yes',
			'autocomplete' => 'autocomplete'
		);		
array_push( $results, $tmp );

foreach ($json as $o) {
	foreach ($o as $obj) {

		$dir = getcwd();
		$icon = 'icon-' . $obj['node']['uuid'] . '.png';
		$tmp = array(
			'uid' => $obj['node']['uuid'] . rand(0, 90000),
			'arg' => 'itemarg',
			'title' => $obj['node']['title'] . ' v' .$obj['node']['Version'],
			'subtitle' => $obj['node']['desc'],
			'icon' => $d."/icons/".$icon,
			'valid' => 'yes',
			'autocomplete' => 'autocomplete'
		);		
		array_push( $results, $tmp );

		$title = str_replace(' ' , '-' , strtolower($obj['node']['title']));
		$file = 'https://github.com/packal/repo/raw/master/'. $obj['node']['uuid'] . '/icon.png';

		$dest = '"' . $d . '/icons/icon-' . $obj['node']['uuid'] . '.png' . '"';
		$remote = 'https://github.com/packal/repo/raw/master/'. $obj['node']['uuid'] . '/icon.png';
		if (! file_exists($dir.'/'.$icon) ) {
			shell_exec("php download.php $dest $remote 2>&1 > /dev/null");
		}
	}
}

echo $w->toxml( $results );

function array_find($needle, array $haystack)
{
    foreach ($haystack as $key => $value) {
        if (is_array($value)) {
            return $key . '->' . array_find($needle, $value);
        } else if (false !== stripos($needle, $value)) {
            return $key;
        }
    }
    return false;
}
