<?php

require_once( __DIR__ . '/Libraries/Alphred.phar' );
require_once( __DIR__ . '/Libraries/CFPropertyList/classes/CFPropertyList/CFPropertyList.php' );

use CFPropertyList\CFPropertyList as CFPropertyList;
use Alphred\Ini as Ini;

$a = new Alphred;

$plist = new CFPropertyList( __DIR__ . '/info.plist', CFPropertyList::FORMAT_XML);
$plist = $plist->toArray();
$ini = Ini::read_ini( __DIR__ . '/workflow.ini' );
$valid = true;

$short = null;

// Find the appropriate icon; and set error warnings if necessary
$icon = false;
if ( isset( $ini['packal']['icon'] ) ) {
	if ( file_exists( $ini['packal']['icon'] ) ) {
		$icon = $ini['packal']['icon'];
		$icon_error = false;
	} else {
		$icon_error = 'ERROR: Icon specified in `workflow.ini` file (' . $ini['packal']['icon'] . ') does not exist';
	}
}
if ( false === $icon ) {
	if ( file_exists( 'icon.png' ) ) {
		$icon = 'icon.png';
		if ( $icon_error ) {
			$icon_error .= '; using `icon.png`';
		}
	}
}
if ( $icon_error ) {
	$icon_error .= '.';
}

// Set the description file
if ( isset( $ini['packal']['description'] ) ) {
	if ( file_exists( $ini['packal']['description'] ) ) {
		$description = $ini['packal']['description'];
		$description_error = false;
	} else {
		$description_error = 'ERROR: Description file specified in `workflow.ini` file (' . $ini['packal']['description'] . ') does not exist';
		$valid = false;
	}
} else if ( empty( $plist['readme'] ) ) {
	$description_error = 'ERROR: No description in the readme or file specified.';
} else {
	$description = $plist['readme'];
	$description_error = false;
}


$a->add_result( [ 'title' => 'Submit workflow', 'valid' => $valid ]);
$a->add_result( [ 'title' => "Name: " . $plist['name'], 'valid' => false ]);
$a->add_result( [ 'title' => "Bundle: " . $plist['bundleid'], 'valid' => false ]);
$a->add_result( [ 'title' => "Author: " . $plist['createdby'], 'valid' => false ]);
$a->add_result( [ 'title' => "Short: " . $plist['description'], 'valid' => false ]);
$a->add_result( [ 'title' => "Description: " . $description, 'subtitle' => $description_error, 'valid' => false ]);
$a->add_result( [ 'title' => "Version: " . $ini['global']['version'], 'valid' => false ]);
$a->add_result( [ 'title' => "Categories: " . $ini['packal']['categories'], 'valid' => false ]);
$a->add_result( [ 'title' => "Tags: " . $ini['packal']['tags'], 'valid' => false ]);
$a->add_result( [ 'title' => "Icon: " . $icon, 'subtitle' => $icon_error, 'valid' => false ]);

$a->to_xml();