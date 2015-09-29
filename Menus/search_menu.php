<?php

/**
 * Creates the default search menu
 *
 * @param  [type] $possible [description]
 */
function create_search_menu( $possible ) {
	global $alphred, $separator, $icon_suffix;
	$alphred->add_result([
		'title'        => 'Search Themes',
		'uid'          => 'packal-search-theme',
		'autocomplete' => "search{$separator}theme{$separator}",
		'valid'        => false,
	]);
	$alphred->add_result([
		'title'        => 'Search Workflows',
		'uid'          => 'packal-search-workflow',
		'autocomplete' => "search{$separator}workflow{$separator}",
		'valid'        => false,
	]);
}

function render_workflows( $workflows, $query ) {

	global $alphred, $separator, $icon_suffix, $api_available;

	$workflows = json_decode( $workflows, true )['workflows'];
	$workflows = filter_resource( $workflows, $query );
	$singular = ( isset( $workflows['name'] ) ) ? true : false;

	if ( $singular ) {
		render_singlular_workflow( $workflows );
	} else {
		$alphred->add_result([
			'title' => 'Showing ' . count( $workflows ) . ' workflows.',
			'valid' => false,
		]);
		foreach ( $workflows as $workflow ) :
			render_workflow_stub( $workflow );
		endforeach;
	}
	$alphred->background( __DIR__ . '/../download_queue.php' );
}

function render_themes( $themes, $query ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	$themes = json_decode( $themes, true )['themes'];
	$themes = filter_resource( $themes, $query );
	$singular = ( isset( $themes['name'] ) ) ? true : false;

	if ( $singular ) {
		render_singular_theme( $themes );
	} else {
		$alphred->add_result([
			'title' => 'Showing ' . count( $themes ) . ' themes.',
			'valid' => false,
		]);
		foreach ( $themes as $theme ) :
			render_theme_stub( $theme );
		endforeach;
	}
}

function filter_resource( $resources, $query ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	if ( empty( $query ) ) {
		return $resources;
	}
	$query = strtolower( $query );
	// Check for singular
	foreach( $resources as $resource ) :
		if ( $query == strtolower( $resource['name'] ) ) {
				return $resource;
				break;
		}
	endforeach;
	return $alphred->filter( $resources, $query, 'name',
					[ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
				);
}

function render_workflow_stub( $workflow ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	$alphred->add_result([
		'icon'     => get_icon( $workflow['icon'] ),
		'title'    => "{$workflow['name']} (v{$workflow['version']})",
		'subtitle' => substr( "{$workflow['description']}", 0, 120 ). ' (last updated: ' . $alphred->fuzzy_time_diff( strtotime( $workflow['updated'] ) ) . ')',
    'autocomplete' => "search{$separator}workflow{$separator}{$workflow['name']}",
    'valid' => false,
	]);
}

function render_singlular_workflow( $workflow ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	$unsupported_icon = '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns';

	if ( $api_available ) {
		$alphred->add_result([
			'title'    => "{$workflow['name']} (v{$workflow['version']}) by {$workflow['author']}",
			'icon'     => get_icon( $workflow['icon'] ),
			'subtitle' => $workflow['description'],
			'valid'    => false,
		]);
		$alphred->add_result([
			'title'    => "Download workflow to `~/Downloads`",
			'icon' 		 => 'assets/images/icons/download' . $icon_suffix,
			'valid'    => true,
			'arg'			 => json_encode([
											'action'   => 'download',
											'target'   => $workflow['file'],
											'type'     => 'workflow',
											'resource' => $workflow,
                    ]),
		]);
		$alphred->add_result([
			'title'    => "Install workflow {$workflow['name']}",
			'icon' 		 => 'assets/images/icons/install' . $icon_suffix,
			'valid'    => true,
			'arg'			 => json_encode([
											'action'   => 'install',
											'type'     => 'workflow',
											'target'   => $workflow['file'],
											'resource' => $workflow,
                    ]),
		]);
		$alphred->add_result([
			'title'    => "View workflow page on Packal.org",
			'icon' 		 => 'assets/images/icons/packal' . $icon_suffix,
			'valid'    => true,
			'arg'			 => json_encode([
											'action'   => 'open',
											'target'   => $workflow['url'],
											'workflow' => $workflow,
                    ]),
		]);
		$alphred->add_result([
			'title'    => "View author page on Packal.org",
			'icon' 		 => 'assets/images/icons/user' . $icon_suffix,
			'valid'    => true,
			'arg'			 => json_encode([
											'action'   => 'open',
											'target'   => $workflow['author_url'],
											'workflow' => $workflow,
                    ]),
		]);
		if ( isset( $workflow['github'] ) && ! empty( $workflow['github'] ) ) {
			$alphred->add_result([
				'title'    => "Open Github Repo Page",
				'icon' 		 => 'assets/images/icons/github' . $icon_suffix,
				'valid'    => true,
				'arg'			 => json_encode([
												'action'   => 'open',
												'target'   => "https://github.com/{$workflow['github']}",
												'resource' => $workflow,
	                    ]),
			]);
		}
		$alphred->add_result([
			'title'    => "Report Workflow",
			'icon' 		 => 'assets/images/icons/report' . $icon_suffix,
			'valid'    => true,
			'arg'			 => json_encode([
											'action'   => 'report',
											'type'     => 'workflow',
											'resource' => $workflow,
                    ]),
		]);
	} else {
			$alphred->add_result([
				'title'    => "{$workflow['name']} (v{$workflow['version']}) by {$workflow['author']}",
				// 'icon' 		 => get_icon( $workflow['icon'], 604800 + rand(100000, 900000) ),
				'subtitle' => $workflow['description'],
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "Download workflow to `~/Downloads`",
				'subtitle' => 'Download unavailable because we cannot connect to the server.',
				'icon' 		 => $unsupported_icon,
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "Install workflow {$workflow['name']}",
				'subtitle' => 'Installation unavailable because we cannot connect to the server.',
				'icon' 		 => $unsupported_icon,
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "View workflow page on Packal.org",
				'subtitle' => 'Page unavailable because we cannot connect to the server.',
				'icon' 		 => $unsupported_icon,
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "View author page on Packal.org",
				'subtitle' => 'Page unavailable because we cannot connect to the server.',
				'icon'     => $unsupported_icon,
				'valid'    => false,
			]);
			if ( isset( $workflow['github'] ) && ! empty( $workflow['github'] ) ) {
				$alphred->add_result([
					'title'    => "Open Github Repo Page",
					'icon' 		 => $unsupported_icon,
					'valid'    => false,
					'subtitle' => 'Page unavailable because we cannot connect to the server.',
				]);
			}
			$alphred->add_result([
				'title'    => "Report Workflow",
				'icon' 		 => $unsupported_icon,
				'valid'    => false,
				'subtitle' => 'Report unavailable because we cannot connect to the server.',
			]);
		}
}

function render_singular_theme( $theme ) {
	global $alphred, $separator, $icon_suffix, $api_available;

	$subtitle = scrub(urldecode($theme['description']));
	if ( strlen( $subtitle ) > 120 ) {
		$subtitle = substr( $subtitle, 0, 120) . '...';
	} else {
		$subtitle = substr( $subtitle, 0, 120);
	}
	$alphred->add_result([
		'title'        => "{$theme['name']}",
		'subtitle'     => $subtitle,
		'icon'         => 'assets/images/icons/bullet' . $icon_suffix,
		'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
		'valid'        => false,
	]);
	if ( $api_available ) {
		$alphred->add_result([
			'title'        => "View `{$theme['name']}` on Packal.org",
			'icon'         => 'assets/images/icons/bullet' . $icon_suffix,
			'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
			'valid'        => true,
			'arg'          => json_encode([
													'action'   => 'open',
													'target'   => $theme['url'],
													'resource' => $theme,
									      ]),
		]);
	} else {
		$alphred->add_result([
			'title'        => "View `{$theme['name']}` on Packal.org",
			'subtitle'     => 'Cannot connect to server, and so we cannot view theme page',
			'icon'         => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
			'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
			'valid'        => false,
		]);
	}
	$alphred->add_result([
		'title'        => "Install `{$theme['name']}`",
		'icon'         => 'assets/images/icons/bullet' . $icon_suffix,
		'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
		'valid'        => true,
		'arg'          => json_encode([
												'action'   => 'install',
												'type'     => 'theme',
												'resource' => $theme,
								      ]),
	]);
}

function render_theme_stub( $theme ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	// I need to do something to get the filepath instead
	// $icon = md5( json_encode( $alphred->get( $workflow['icon'], 3600 ) ) );
	$subtitle = scrub( urldecode( $theme['description'] ) );
	if ( strlen( $subtitle ) > 120 ) {
		$subtitle = substr( $subtitle, 0, 120) . '...';
	} else {
		$subtitle = substr( $subtitle, 0, 120);
	}
	$alphred->add_result([
		'title'        => $theme['name'],
		'subtitle'     => $subtitle,
		'icon'         => 'assets/images/icons/bullet' . $icon_suffix,
		'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
		'valid'        => false,
	]);
}