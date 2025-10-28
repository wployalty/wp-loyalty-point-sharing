<?php

namespace Wlps\App\Models;

use Wlr\App\Models\Base;

defined( 'ABSPATH' ) or die();

class PointTransfers extends Base {
	function __construct() {
		parent::__construct();
		$this->table       = self::$db->prefix . 'wlr_point_transfers';
		$this->primary_key = 'id';
		$this->fields      = [
			'sender_email'    => '%s',
			'recipient_email' => '%s',
			'points'          => '%d',
			'status'          => '%s',
			'token'           => '%s',
			'notes'           => '%s',
			'created_at'      => '%d',
			'updated_at'      => '%d',
		];
	}

	function beforeTableCreation() {
	}

	function runTableCreation() {
		$create_table_query = "CREATE TABLE IF NOT EXISTS {$this->table} (
                `{$this->getPrimaryKey()}` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `sender_email` VARCHAR(255) NOT NULL,
    			`recipient_email` VARCHAR(255) NOT NULL,
				`points` INT NOT NULL,
    			`status` ENUM('pending', 'completed', 'failed', 'expired') NOT NULL DEFAULT 'pending',
    			`token` VARCHAR(255) NOT NULL,
    			`notes` TEXT DEFAULT NULL,
    			`created_at` BIGINT DEFAULT 0,
                `updated_at` BIGINT DEFAULT 0,
                PRIMARY KEY (`{$this->getPrimaryKey()}`)
        )";
		$this->createTable( $create_table_query );
	}

	function afterTableCreation() {
		$index_fields = [ 'sender_email', 'recipient_email', 'status', 'token' ];
		$this->insertIndex( $index_fields );
	}

	//Find Transfer by token
	public function findByIdAndToken( $id, $token ) {
		$id    = intval( $id );
		$token = sanitize_text_field( $token );

		// Use prepared statement for safety
		return $this->getWhere(
			self::$db->prepare(
				"id = %d AND token = %s",
				$id,
				$token
			),
			'*',
			true
		);
	}

	public function updateStatus( $transferId, string $status, string $notes ) {
		if ( empty( $transferId ) || empty( $status ) || empty( $notes ) ) {
			return;
		}
		$this->updateRow( [
			'status'     => $status,
			'notes'      => $notes,
			'updated_at' => strtotime( gmdate( "Y-m-d H:i:s" ) ),
		], [
			'id' => $transferId,
		] );
	}


}