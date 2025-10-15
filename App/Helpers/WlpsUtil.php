<?php

namespace Wlps\App\Helpers;

use Wlr\App\Models\Users;

class WlpsUtil {
	public static $banned_user, $instance;

	public static function hasAdminPrivilege() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function create_nonce( $action = - 1 ) {
		return wp_create_nonce( $action );
	}

	public static function verify_nonce( $nonce, $action = - 1 ) {
		if ( wp_verify_nonce( $nonce, $action ) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function getInstance( array $config = array() ) {
		if ( ! self::$instance ) {
			self::$instance = new self( $config );
		}

		return self::$instance;
	}

	public static function renderTemplate( string $file, array $data = [], bool $display = true ) {
		$content = '';
		if ( file_exists( $file ) ) {
			ob_start();
			extract( $data );
			include $file;
			$content = ob_get_clean();
		}
		if ( $display ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $content;
		} else {
			return $content;
		}
	}

	/**
	 * Get the email of the current logged in user
	 *
	 * @return string
	 */
	public static function getLoginUserEmail() {
		$user       = get_user_by( 'id', get_current_user_id() );
		$user_email = '';
		if ( ! empty( $user ) ) {
			$user_email = $user->user_email;
		}

		return $user_email;
	}

	public static function isBannedUser( $user_email = "" ) {
		if ( empty( $user_email ) ) {
			$user_email = self::getLoginUserEmail();
			if ( empty( $user_email ) ) {
				return false;
			}
		}
		$user    = get_user_by( 'email', $user_email );
		$user_id = isset( $user->ID ) && ! empty( $user->ID ) ? $user->ID : 0;
		if ( ! apply_filters( 'wlr_before_add_to_loyalty_customer', true,
			$user_id, $user_email ) ) {
			return true;
		}
		if ( isset( static::$banned_user[ $user_email ] ) ) {
			return static::$banned_user[ $user_email ];
		}
		$user_modal = new Users();
		global $wpdb;
		$where = $wpdb->prepare( "user_email = %s AND is_banned_user = %d ", array( $user_email, 1 ) );
		$user  = $user_modal->getWhere( $where, "*", true );

		return static::$banned_user[ $user_email ] = ( ! empty( $user ) && is_object( $user ) && isset( $user->is_banned_user ) );
	}

	public static function beforeDisplayDate( $date, $format = '' ) {
		if ( empty( $format ) ) {
			$format = get_option( 'date_format', 'Y-m-d H:i:s' );
		}
		if ( empty( $date ) ) {
			return null;
		}
		if ( (int) $date != $date ) {
			return $date;
		}

		$converted_time = self::convert_utc_to_wp_time( gmdate( 'Y-m-d H:i:s', $date ), $format );
		if ( apply_filters( 'wlr_translate_display_date', true ) ) {
			$datetime = \DateTime::createFromFormat( $format, $converted_time );
			if ( $datetime !== false ) {
				$time = $datetime->getTimestamp();
			} else {
				$time = strtotime( $converted_time );
			}
			$converted_time = date_i18n( $format, $time );
		}

		return $converted_time;
	}

	public static function convert_utc_to_wp_time( $datetime, $format = 'Y-m-d H:i:s', $modify = '' ) {
		try {
			$timezone     = new \DateTimeZone( 'UTC' );
			$current_time = new \DateTime( $datetime, $timezone );
			if ( ! empty( $modify ) ) {
				$current_time->modify( $modify );
			}
			$wp_time_zone = new \DateTimeZone( self::get_wp_time_zone() );
			$current_time->setTimezone( $wp_time_zone );
			$converted_time = $current_time->format( $format );
		} catch ( \Exception $e ) {
			$converted_time = $datetime;
		}

		return $converted_time;
	}

	public static function get_wp_time_zone() {
		if ( ! function_exists( 'wp_timezone_string' ) ) {
			$timezone_string = get_option( 'timezone_string' );
			if ( $timezone_string ) {
				return $timezone_string;
			}
			$offset    = (float) get_option( 'gmt_offset' );
			$hours     = (int) $offset;
			$minutes   = ( $offset - $hours );
			$sign      = ( $offset < 0 ) ? '-' : '+';
			$abs_hour  = abs( $hours );
			$abs_mins  = abs( $minutes * 60 );
			$tz_offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );

			return $tz_offset;
		}

		return wp_timezone_string();
	}


}