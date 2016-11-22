<?php

echo "This is a prompt:\n\n";
echo "|\n";

// $steps = [ "/", "—", "\\", "|", "/", "—", "\\", "|" ];

// $counter = 0;
// for ( $i = 0; $i < 10; $i++ ) :
// 	if ( $counter >= count( $steps ) ) {
// 		$counter = 0;
// 	}
// 	echo "\033[1A" . $steps[ $counter ] . "\n";
// 	$counter++;
// 	usleep( 100000 );
// endfor;
// echo "\nDone.\n";

$progress = 0;
function upper() {
	global $progress;
	return $progress++;
}

$progress = 0;
function progress() {
	global $progress;
	return $progress++;
}

$spinner = 0;
function spinner() {
	global $spinner;
	if ( $spinner < 100 ) {
		$spinner++;
		return true;
	}
	return false;
}

// ProgressBars::bar( 80, 100, 'upper' );
// ProgressBars::percent( 'progress' );
ProgressBars::spinner( 'spinner' );



class ProgressBars {

	static public function bar( $steps, $time, $callback ) {
		echo "Progress:\n\n";
		while ( $time > 0 ) :
			$progress = call_user_func( $callback );
			echo "\033[1A";
			for ( $i = 0; $i < $steps; $i ++ ) :
				if ( $i < $progress ) {
					echo '|';
				} else {
					echo '.';
				}
			endfor;
			echo "\n";
			usleep( 50000 );
			$time--;
		endwhile;
	}

	static public function percent( $callback ) {
		echo "Progress:\n\n";
		$progress = call_user_func( $callback );
		while ( $progress < 101 ) :
			echo "\033[1A" . $progress . "%\n";
			$progress = call_user_func( $callback );
			usleep( 50000 );
		endwhile;
		echo 'Done!';
	}

	static public function spinner( $callback ) {
		$steps = [ '/', '—', '\\', '|', '/', '—', '\\', '|' ];
		$spin = true;
		$counter = 0;
		while ( $spin ) :
			if ( $counter >= count( $steps ) ) {
				$counter = 0;
			}
			echo "\033[1A" . $steps[ $counter ] . "\n";
			$counter++;
			$spin = call_user_func( $callback );
			usleep( 100000 );
		endwhile;
		echo "\033[1A" . "Done!\n";
	}

}
