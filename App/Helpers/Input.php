<?php

namespace Wlps\App\Helpers;

use http\Exception\UnexpectedValueException;

defined( 'ABSPATH' ) or die();

class Input {
	/**
	 * List of available input types.
	 *
	 * @var array
	 */
	protected static $input_types = [
		'params',
		'query',
		'post',
		'cookie',
	];
	/**
	 * Enable XSS flag
	 *
	 * Determines whether the XSS filter is always active when
	 * GET, POST or COOKIE data is encountered.
	 * Set automatically based on config setting.
	 *
	 * @var    bool
	 */
	protected $_enable_xss = true;
	/**
	 * List of available sanitize callbacks.
	 *
	 * @var array
	 */
	protected static $sanitize_callbacks = [
		'text'    => 'sanitize_text_field',
		'title'   => 'sanitize_title',
		'email'   => 'sanitize_email',
		'url'     => 'sanitize_url',
		'key'     => 'sanitize_key',
		'meta'    => 'sanitize_meta',
		'option'  => 'sanitize_option',
		'file'    => 'sanitize_file_name',
		'mime'    => 'sanitize_mime_type',
		'class'   => 'sanitize_html_class',
		'int'     => 'absint',
		'html'    => [ __CLASS__, 'sanitizeHtml' ],
		'content' => [ __CLASS__, 'sanitizeContent' ],
	];

	/**
	 * Fetch an item from the GET array
	 *
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return mixed
	 */
	function getQuery( $index = null, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $this->_fetch_from_array( $_GET, $index, $default, $xss_clean );
	}

	/**
	 * Get sanitized input form request.
	 *
	 * @param string $var Variable name.
	 * @param mixed $default Default value.
	 * @param string $type Input type.
	 * @param string|false $sanitize Sanitize type.
	 *
	 * @return mixed
	 */
	public static function get( string $var, $default = '', string $type = 'params', $sanitize = 'text' ) {
		if ( ! in_array( $type, self::$input_types ) ) {
			throw new UnexpectedValueException( 'Expected a valid type on get method' );
		}
		switch ( $type ) {
			case 'params':
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return isset( $_REQUEST[ $var ] ) ? self::sanitize( $_REQUEST[ $var ], $sanitize ) : $default;
			case 'query':
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return isset( $_GET[ $var ] ) ? self::sanitize( $_GET[ $var ], $sanitize ) : $default;
			case 'post':
				//phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return isset( $_POST[ $var ] ) ? self::sanitize( $_POST[ $var ], $sanitize ) : $default;
			case 'cookie':
				//phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				return isset( $_COOKIE[ $var ] ) ? self::sanitize( $_COOKIE[ $var ], $sanitize ) : $default;
			default:
				return $default;
		}
	}

	public static function sanitize( $value, $type = 'text' ) {
		if ( $type === false ) {
			return $value;
		}

		if ( ! array_key_exists( $type, self::$sanitize_callbacks ) ) {
			throw new \UnexpectedValueException( 'Expected a valid type on sanitize method' );
		}

		if ( is_array( $value ) ) {
			return self::sanitizeRecursively( $value, self::$sanitize_callbacks[ $type ] );
		}

		return self::filterXss( call_user_func( self::$sanitize_callbacks[ $type ], $value ) );
	}

	/**
	 * Sanitize recursively.
	 *
	 * @param array $array Input array.
	 * @param string $callback Sanitize callback.
	 *
	 * @return array
	 */
	public static function sanitizeRecursively( array &$array, string $callback ): array {
		foreach ( $array as &$value ) {
			if ( is_array( $value ) ) {
				$value = self::sanitizeRecursively( $value, $callback );
			} else {
				$value = self::filterXss( call_user_func( $callback, $value ) );
			}
		}

		return $array;
	}

	/**
	 * Filter XSS.
	 *
	 * @param string $data Input string.
	 *
	 * @return string
	 */
	public static function filterXss( string $data ): string {
		// Fix &entity\n;
		$data = str_replace( [ '&amp;', '&lt;', '&gt;' ], [ '&amp;amp;', '&amp;lt;', '&amp;gt;' ], $data );
		$data = preg_replace( '/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data );
		$data = preg_replace( '/(&#x*[0-9A-F]+);*/iu', '$1;', $data );
		$data = html_entity_decode( $data, ENT_COMPAT, 'UTF-8' );

		// Remove any attribute starting with "on" or xmlns
		$data = preg_replace( '#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data );

		// Remove javascript: and vbscript: protocols
		$data = preg_replace( '#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data );
		$data = preg_replace( '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data );
		$data = preg_replace( '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data );

		// Remove namespaced elements (we do not need them)
		$data = preg_replace( '#</*\w+:\w[^>]*+>#i', '', $data );

		// Remove really unwanted tags
		do {
			$old_data = $data;
			$data     = preg_replace( '#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data );
		} while ( $old_data !== $data );

		return is_string( $data ) ? $data : '';
	}

	/**
	 * Fetch an item from the POST array
	 *
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return mixed
	 */
	function post( $index = null, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $this->_fetch_from_array( $_POST, $index, $default, $xss_clean );
	}

	/**
	 * Fetch from array
	 *
	 * @param $array
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return array|string|null
	 */
	protected function _fetch_from_array( &$array, $index = null, $default = null, $xss_clean = null ) {
		is_bool( $xss_clean ) or $xss_clean = $this->_enable_xss;
		// If $index is NULL, it means that the whole $array is requested
		$index = ( ! isset( $index ) || is_null( $index ) ) ? array_keys( $array ) : $index;
		// allow fetching multiple keys at once
		if ( is_array( $index ) ) {
			$output = array();
			foreach ( $index as $key ) {
				$output[ $key ] = $this->_fetch_from_array( $array, $key, $default, $xss_clean );
			}

			return $output;
		}
		if ( isset( $array[ $index ] ) ) {
			$value = $array[ $index ];
		} elseif ( ( $count = preg_match_all( '/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches ) ) > 1 ) // Does the index contain array notation
		{
			$value = $array;
			for ( $i = 0; $i < $count; $i ++ ) {
				$key = trim( $matches[0][ $i ], '[]' );
				if ( $key === '' ) // Empty notation will return the value as array
				{
					break;
				}
				if ( isset( $value[ $key ] ) ) {
					$value = $value[ $key ];
				} else {
					return null;
				}
			}
		} else {
			return $default;
		}

		/*return ($xss_clean === TRUE) ? $this->xss_clean($value) : $value;*/

		return $value;
	}

	/**
	 * Fetch an item from POST data with fallback to GET
	 *
	 * @param $index
	 * @param null $xss_clean
	 * @param null $default
	 *
	 * @return mixed
	 */
	function post_get( $index, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $index ] ) ? $this->post( $index, $default, $xss_clean ) : $this->getQuery( $index, $default, $xss_clean );
	}
}