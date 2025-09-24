<?php

namespace Wlps\App\Helpers;

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
		$settings_validator->rule( 'required', [ 'enable_share_point' ] );
		$settings_validator->rule( 'in', [ 'enable_point_point' ], [ 'yes', 'no' ] );

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

}