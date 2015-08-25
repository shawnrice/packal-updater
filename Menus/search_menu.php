<?php

function create_search_menu( $possible ) {
	global $alphred, $separator, $icon_suffix;
	$alphred->add_result([
  	'title' => 'Search Themes',
  	'uid' => 'packal-search-theme',
  	'autocomplete' => "search{$separator}theme{$separator}",
  	'valid' => false,
	]);
	$alphred->add_result([
  	'title' => 'Search Workflows',
  	'uid' => 'packal-search-workflow',
  	'autocomplete' => "search{$separator}workflow{$separator}",
  	'valid' => false,

	]);
}


function render_workflows( $workflows, $query ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	// print_r( $workflows );
	$workflows = json_decode( $workflows, true );
	$workflows = $workflows['workflows'];

	if ( ! empty( $query ) ) {
		$singular = false;
		foreach( $workflows as $workflow ) :
			if ( strtolower( $query ) == strtolower( $workflow['name'] ) ) {
				unset( $workflows );
				$workflows[] = $workflow;
				$singular = true;
				break;
			}
		endforeach;
		if ( ! $singular ) {
			$workflows = $alphred->filter(
			  $workflows,
			  $query,
			  'name',
			  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
			);
		}
	}
	if ( isset( $singular ) && $singular ) {
		if ( $api_available ) {
			$alphred->add_result([
				'title'    => "{$workflow['name']} (v{$workflow['version']}) by {$workflow['author']}",
				'icon' 		 => get( $workflow['icon'], 604800 + rand(100000, 200000) )[1],
				'subtitle' => $workflow['description'],
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "Download workflow to `~/Downloads`",
				'icon' 		 => 'assets/images/icons/download' . $icon_suffix,
				'valid'    => true,
				'arg'			 => json_encode([
	                      'action' => 'download',
	                      'target' => $workflow['file'],
	                      'workflow' => $workflow,
	                    ]),
			]);
			$alphred->add_result([
				'title'    => "Install workflow {$workflow['name']}",
				'icon' 		 => 'assets/images/icons/install' . $icon_suffix,
				'valid'    => true,
				'arg'			 => json_encode([
	                      'action' => 'install',
	                      'target' => $workflow['file'],
	                      'workflow' => $workflow,
	                    ]),
			]);
			$alphred->add_result([
				'title'    => "View workflow page on Packal.org",
				'icon' 		 => 'assets/images/icons/packal' . $icon_suffix,
				'valid'    => true,
				'arg'			 => json_encode([
	                      'action' => 'open',
	                      'target' => $workflow['url'],
	                      'workflow' => $workflow,
	                    ]),
			]);
			$alphred->add_result([
				'title'    => "View author page on Packal.org",
				'icon' 		 => 'assets/images/icons/user' . $icon_suffix,
				'valid'    => true,
				'arg'			 => json_encode([
	                      'action' => 'open',
	                      'target' => $workflow['author_url'],
	                      'workflow' => $workflow,
	                    ]),
			]);
			if ( isset( $workflow['github'] ) && ! empty( $workflow['github'] ) ) {
				$alphred->add_result([
					'title'    => "Open Github Repo Page",
					'icon' 		 => 'assets/images/icons/github' . $icon_suffix,
					'valid'    => true,
					'arg'			 => json_encode([
	                      'action' => 'open',
	                      'target' => "https://github.com/{$workflow['github']}",
	                      'workflow' => $workflow,
	                    ]),
				]);
			}
			$alphred->add_result([
				'title'    => "Report Workflow",
				'icon' 		 => 'assets/images/icons/report' . $icon_suffix,
				'valid'    => true,
				'arg'			 => json_encode([
	                      'action' => 'report',
	                      'workflow' => $workflow,
	                    ]),
			]);
		} else {
			$alphred->add_result([
				'title'    => "{$workflow['name']} (v{$workflow['version']}) by {$workflow['author']}",
				'icon' 		 => get( $workflow['icon'], 604800 + rand(100000, 200000) )[1],
				'subtitle' => $workflow['description'],
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "Download workflow to `~/Downloads`",
				'subtitle' => 'Download unavailable because we cannot connect to the server.',
				'icon' 		 => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "Install workflow {$workflow['name']}",
				'subtitle' => 'Installation unavailable because we cannot connect to the server.',
				'icon' 		 => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "View workflow page on Packal.org",
				'subtitle' => 'Page unavailable because we cannot connect to the server.',
				'icon' 		 => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
				'valid'    => false,
			]);
			$alphred->add_result([
				'title'    => "View author page on Packal.org",
				'subtitle' => 'Page unavailable because we cannot connect to the server.',
				'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
				'valid'    => false,
			]);
			if ( isset( $workflow['github'] ) && ! empty( $workflow['github'] ) ) {
				$alphred->add_result([
					'title'    => "Open Github Repo Page",
					'icon' 		 => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
					'valid'    => false,
					'subtitle' => 'Page unavailable because we cannot connect to the server.',
				]);
			}
			$alphred->add_result([
				'title'    => "Report Workflow",
				'icon' 		 => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
				'valid'    => false,
				'subtitle' => 'Report unavailable because we cannot connect to the server.',
			]);
		}
	} else {
		$alphred->add_result([
			'title' => 'Showing ' . count( $workflows ) . ' workflows.',
			'valid' => false,
		]);
		foreach ( $workflows as $workflow ) :
			$request = get( $workflow['icon'], 86400 );
			$alphred->add_result([
		    'icon' => $request[1],
		    'title' => "{$workflow['name']} (v{$workflow['version']})",
		    'subtitle' => substr( "{$workflow['description']}", 0, 120 )
		    				. ' (last updated: ' . $alphred->fuzzy_time_diff( strtotime( $workflow['updated'] ) ) . ')',
		    'autocomplete' => "search{$separator}workflow{$separator}{$workflow['name']}",
		    'valid' => false,
			]);
		endforeach;
	}
}

function render_themes( $themes, $query ) {
	global $alphred, $separator, $icon_suffix, $api_available;
	$themes = json_decode( $themes, true );
	$themes = $themes['themes'];
	if ( ! empty( $query ) ) {
		$themes = $alphred->filter(
		  $themes,
		  $query,
		  'name',
		  //  && MATCH_ATOM && MATCH_INITIALS  &&
		  [ 'match_type' => MATCH_SUBSTRING | MATCH_ALLCHARS | MATCH_STARTSWITH | MATCH_ATOM ]
		);
	}

	$singular = false;
	foreach( $themes as $theme ) :
		if ( strtolower( $query ) == strtolower( $theme['name'] ) ) {
			unset( $themes );
			$themes[] = $theme;
			$singular = true;
			break;
		}
	endforeach;
	if ( ! $singular ) {
		$alphred->add_result([
			'title' => 'Showing ' . count( $themes ) . ' themes.',
			'valid' => false,
		]);
	}
	if ( ! $singular ) {
		foreach ( $themes as $theme ) :
			// I need to do something to get the filepath instead
			// $icon = md5( json_encode( $alphred->get( $workflow['icon'], 3600 ) ) );
			$subtitle = scrub(urldecode($theme['description']));
			if ( strlen( $subtitle ) > 120 ) {
				$subtitle = substr( $subtitle, 0, 120) . '...';
			} else {
				$subtitle = substr( $subtitle, 0, 120);
			}
			$alphred->add_result([
		    'title' => "{$theme['name']}",
		    'subtitle' => $subtitle,
		    'icon' => 'assets/images/icons/bullet' . $icon_suffix,
		    'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
		    'valid' => false,
			]);
		endforeach;
	} else {
		$subtitle = scrub(urldecode($theme['description']));
		if ( strlen( $subtitle ) > 120 ) {
			$subtitle = substr( $subtitle, 0, 120) . '...';
		} else {
			$subtitle = substr( $subtitle, 0, 120);
		}
		$alphred->add_result([
	    'title' => "{$theme['name']}",
	    'subtitle' => $subtitle,
	    'icon' => 'assets/images/icons/bullet' . $icon_suffix,
	    'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
	    'valid' => false,
		]);
		if ( $api_available ) {
				$alphred->add_result([
			    'title' => "View `{$theme['name']}` on Packal.org",
			    'icon' => 'assets/images/icons/bullet' . $icon_suffix,
			    'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
			    'valid' => true,
					'arg' => json_encode([
		        'action' => 'open',
		        'target' => $theme['url'],
		        'theme' => $theme,
		      ]),
				]);
		} else {
			$alphred->add_result([
			    'title' => "View `{$theme['name']}` on Packal.org",
    	  	'subtitle' => 'Cannot connect to server, and so we cannot view theme page',
			  	'icon' => '/System/Library/CoreServices/CoreTypes.bundle/Contents/Resources/Unsupported.icns',
			    'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
			    'valid' => false,
				]);
		}
		$alphred->add_result([
	    'title' => "Install `{$theme['name']}`",
	    'icon' => 'assets/images/icons/bullet' . $icon_suffix,
	    'autocomplete' => "search{$separator}theme{$separator}{$theme['name']}",
	    'valid' => true,
			'arg' => json_encode([
        'action' => 'open',
        'target' => "'{$theme['uri']}'",
        'theme' => $theme,
      ]),
		]);
	}
}