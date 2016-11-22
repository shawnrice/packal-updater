<?php
/**
 * Simple static class to check and fix Semantic Versioning
 *
 * PHP version 5
 *
 * @copyright  Shawn Patrick Rice 2015
 * @license    http://opensource.org/licenses/MIT  MIT
 * @version    1.0.0
 * @author     Shawn Patrick Rice <rice@shawnrice.org>
 * @since      File available since Release 1.0.0
 *
 */

/**
 * Simple static class to check and fix Semantic Versioning
 *
 * @author Shawn Patrick Rice
 * @license MIT
 *
 * Example usage:
 * 	$fixed_semver = SemVer::fix( '1.0' );
 * 	$gt = SemVer::gt( '1.0.1', '1.0.0' );
 *
 */
class SemVer {

	/**
	 * Checks whether a string is a semantic version (ish).
	 *
	 * @todo fix to actually check for numbers. Follow the logic
	 *       	of the `split` function more.
	 *
	 * @param  string $version a semantic version
	 * @return bool          whether it is a version or not
	 */
	public static function check( $version ) {
		if ( ! is_string( $version ) ) {
			return false;
		}
		if ( ! strpos( $version, '.' ) ) {
			return false;
		}
		if ( 3 > count( explode( '.', $version ) ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Checks if v1 is greater than v2
	 *
	 * @param  string $v1 a semantic version
	 * @param  string $v2 a semantic version
	 * @return boolean	true for true; false for false
	 */
	public static function gt( $v1, $v2 ) {
		return ( 1 === self::compare( $v1, $v2 ) ) ? true : false;
	}

	/**
	 * Checks if v1 is greater than or equal to v2
	 *
	 * @param  string $v1 a semantic version
	 * @param  string $v2 a semantic version
	 * @return boolean	true for true; false for false
	 */
	public static function gte( $v1, $v2 ) {
		$product = self::compare( $v1, $v2 );
		return ( 1 === $product || 0 === $product ) ? true : false;
	}

	/**
	 * Checks if v1 is less than v2
	 *
	 * @param  string $v1 a semantic version
	 * @param  string $v2 a semantic version
	 * @return boolean	true for true; false for false
	 */
	public static function lt( $v1, $v2 ) {
		return ( -1 === self::compare( $v1, $v2 ) ) ? true : false;
	}

	/**
	 * Checks if v1 is less than or equal to v2
	 *
	 * @param  string $v1 a semantic version
	 * @param  string $v2 a semantic version
	 * @return boolean	true for true; false for false
	 */
	public static function lte( $v1, $v2 ) {
		$product = self::compare( $v1, $v2 );
		return ( -1 === $product || 0 === $product ) ? true : false;
	}

	/**
	 * Checks if v1 is equal to v2
	 *
	 * @param  string $v1 a semantic version
	 * @param  string $v2 a semantic version
	 * @return boolean	true for true; false for false
	 */
	public static function eq( $v1, $v2 ) {
		return ( 0 === self::compare( $v1, $v2 ) ) ? true : false;
	}

	/**
	 * Fixes a string into a Semantic Version
	 * @param  string $version a malformed semantic version
	 * @return string          a semantic version
	 */
	public static function fix( $version ) {
		return str_replace( '.-', '-', implode( '.', self::split( $version, true ) ) );
	}

	/**
	 * Splits a semantic version into its parts and fixes it into a semantic version if requested
	 *
	 * @todo Push these out into two different functions. Right now it has two uses rather than one.
	 *
	 * @param  string  $version a semantic version
	 * @param  boolean $force   whether or not to coerce the string into a semantic version
	 * @return array            an array of each part of the semantic version
	 */
	private static function split( $version, $force = false ) {
		preg_match( '/^([0-9]{1,})\.([0-9]{1,})\.([0-9]{1,})(-*[A-Za-z0-9.]*)(\+*[A-Za-z0-9.]*)$/', $version, $matches );

		// Check if it isn't a valid SemVer
		if ( 4 > count( $matches ) ) {
			if ( ! $force ) {
				// We don't have instructions to force the version into a SemVer, so we'll
				// return null.
				return null;
			} else {
				// We'll force it into a SemVer
				preg_match_all( '/([0-9]{1,})/', $version, $matches );
				$matches = array_shift( $matches );
			}
		} else {
			array_shift( $matches );
		}

		foreach ( [ 'major', 'minor', 'patch', 'extra', 'build' ] as $key => $val ) :
			if ( isset( $matches[ $key ] ) ) {
				$return[ $val ] = $matches[ $key ];
			} elseif ( $force ) {
				$return[ $val ] = 0;
			}
			if ( in_array( $val, [ 'major', 'minor', 'patch' ] ) ) {
				// If they're one of the integer parts, then force the integer.
				// This also strips leading 0's.
				$return[ $val ] = (int) $return[ $val ];
			}
		endforeach;

		// Strip out the "extra" and "build" if they are empty
		if ( 0 === $return['extra'] || empty( $return['extra'] ) ) {
			unset( $return['extra'] );
		}
		if ( 0 === $return['build'] || empty( $return['build'] ) ) {
			unset( $return['build'] );
		}
		return $return;
	}

	/**
	 * Compares two semantic versions
	 *
	 * @todo double-check the logic for comparing the "extra" part
	 *
	 * @param  string $v1 a Semantic Version
	 * @param  string $v2 a Semantic Version
	 * @return int     0 is equal, -1 is less than, 1 is greater than
	 */
	private static function compare( $v1, $v2 ) {
		// Make sure that both are valid, if not, return null
		if ( ! ( self::check( $v1 ) && self::check( $v2 ) ) ) {
			return null;
		}
		$v1 = self::split( $v1 );
		$v2 = self::split( $v2 );
		foreach ( [ 'major', 'minor', 'patch', 'extra' ] as $part ) :
			// This next one should be invoked __only__ if we're on the "extra"
			// part of the SemVer and one or both aren't set.
			if ( ! ( isset( $v1[ $part ] ) && isset( $v2[ $part ] ) ) ) {
				if ( ! isset( $v1[ $part ] ) && isset( $v2[ $part ] ) ) {
					return 1;
				}
				if ( isset( $v1[ $part ] ) && ! isset( $v2[ $part ] ) ) {
					return -1;
				}
				return 0;
			}
			// The string function comparisons work out so that even the "extra"
			// part that is some of the build information can be compared accurately
			// with no added logic.
			if ( $v1[ $part ] === $v2[ $part ] ) {
				// They are equal, so keep looking
				continue;
			}
			if ( $v1[ $part ] > $v2[ $part ] ) {
				// The first is greater, so return 1
				return 1;
			}
			if ( $v1[ $part ] < $v2[ $part ] ) {
				// The second is less than, so return -1
				return -1;
			}
		endforeach;
		// All parts were equal, so return 0
		return 0;
	}

}
