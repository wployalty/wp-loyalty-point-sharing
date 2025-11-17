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

	/**
	 * Create the point transfer database table if it does not already exist.
	 *
	 * This method constructs and executes a SQL query to create the table used
	 * for storing point transfer records. The table includes information such as
	 * sender and recipient emails, transfer points, status, token, notes, and timestamps.
	 *
	 * The method ensures:
	 * - The table is only created if it does not already exist.
	 * - The primary key is automatically set using the model's defined primary key field.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
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

	/**
	 * Perform post-table creation operations.
	 *
	 * This method is executed immediately after the point transfer table is created.
	 * It adds necessary database indexes on specific columns to improve query
	 * performance and lookup efficiency.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
	function afterTableCreation() {
		$index_fields = [ 'sender_email', 'recipient_email', 'status', 'token' ];
		$this->insertIndex( $index_fields );
	}

	/**
	 * Retrieve a point transfer record by its ID and token.
	 *
	 * This method securely fetches a single transfer record from the database
	 * using the provided transfer ID and token. The query is executed using a
	 * prepared statement to prevent SQL injection.
	 *
	 * @param int $id The unique ID of the transfer record.
	 * @param string $token The hashed or plain token associated with the transfer.
	 *
	 * @return object|false The transfer record as an object if found, or false if not found.
	 * @since 1.0.0
	 *
	 */
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

	/**
	 * Update the status and notes of a specific point transfer record.
	 *
	 * This method updates the transfer record in the database by changing its
	 * status, notes, and the `updated_at` timestamp. It ensures that all parameters
	 * are valid before performing the update.
	 *
	 * Common use cases:
	 * - Marking a transfer as `completed`, `failed`, or `expired`.
	 * - Adding contextual notes explaining the status update.
	 *
	 * @param int $transferId The unique ID of the transfer record to update.
	 * @param string $status The new status of the transfer (e.g., 'pending', 'completed', 'failed', 'expired').
	 * @param string $notes The note or message describing the reason for the status update.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */
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