<?php

namespace Wlps\App\Emails\Helpers;

use Valitron\Validator;

class Validation {
	static function validateInputAlpha( $input ) {
		return preg_replace( '/[^A-Za-z0-9_\-]/', '', $input );
	}

	static function validateSettingsTab( $post ) {
		$settings_validator = new Validator( $post );

		// Labels
		$labels_array = array(
			'enable_share_point'  => __( "Enable Expire Point", "wp-loyalty-rules" ),
			'max_transfer_points' => __( "Maximum Transfer Points", "wp-loyalty-rules" ),
		);
		$settings_validator->labels( $labels_array );
		$settings_validator->stopOnFirstFail( false );

		// Validation rules
		$settings_validator->rule( 'in', [ 'enable_share_point' ], [ 'yes', 'no' ] );

		$settings_validator->rule( 'required', [ 'max_transfer_points' ] );
		$settings_validator->rule( 'numeric', [ 'max_transfer_points' ] );
		$settings_validator->rule( 'min', [ 'max_transfer_points' ], 1 );

		// Validate
		if ( $settings_validator->validate() ) {
			return true;
		} else {
			return $settings_validator->errors();
		}
	}

	public static function validateTransferPointsInput( $recipient_email, $transfer_points ) {
		$v = new Validator( [
			'recipient_email' => $recipient_email,
			'transfer_points' => $transfer_points,
		] );

		$v->labels( [
			'recipient_email' => __( 'Recipient Email', 'wp-loyalty-point-sharing' ),
			'transfer_points' => __( 'Points to Transfer', 'wp-loyalty-point-sharing' ),
		] );

		$v->rules( [
			'required' => [ 'recipient_email', 'transfer_points' ],
			'email'    => [ 'recipient_email' ],
			'integer'  => [ 'transfer_points' ],
			'min'      => [ [ 'transfer_points', 1 ] ],
		] );

		return $v->validate() ? true : $v->errors();
	}

}