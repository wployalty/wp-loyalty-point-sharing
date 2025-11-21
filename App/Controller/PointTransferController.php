<?php

namespace Wlps\App\Controller;

use Wlps\App\Helpers\Input;
use Wlps\App\Helpers\Validation;
use Wlps\App\Helpers\Util;
use Wlps\App\Models\PointTransfers;
use Wlr\App\Helpers\Base;

class PointTransferController {
	const TRANSFER_LINK_EXPIRY = 15;
	const TRANSFER_REQUEST_PER_MINUTE = 2;
	const COMPLETED = "completed";
	const FAILED = "failed";
	const EXPIRED = "expired";
	const PENDING = "pending";

	/**
	 * Transfer Points to another user.
	 *
	 * Handles the AJAX request to transfer points from one user to another.
	 * Validates input, creates a transfer record, and sends confirmation email.
	 *
	 * @hooked wp_ajax_wlps_transfer_points
	 *
	 * @return void
	 * @throws \Throwable
	 */
	public static function transferPoints() {
		$wlps_nonce = (string) Input::get( 'wlps_transfer_points_nonce', '', 'post' );
		if ( ! Util::verify_nonce( $wlps_nonce, 'wlps-transfer-points-nonce' ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Cannot Transfer Points nonce verification failed', 'wp-loyalty-point-sharing' ),
			] );
		}


		$sender          = wp_get_current_user();
		if ( ! $sender || empty( $sender->ID ) ) {
			wp_send_json_error([
				'message' => __( 'You must be logged in to transfer points.', 'wp-loyalty-point-sharing' ),
			]);
		}
		$sender_email    = $sender->user_email;
		$recipient_email = Input::get( 'transfer_email', '', 'post' );
		$transfer_points = (int)Input::get( 'transfer_points', '', 'post' );

		$validation_result = Validation::validateTransferPointsInput( $recipient_email, $transfer_points );

		if ( is_array( $validation_result ) && ! empty( $validation_result ) ) {
			$field_errors = [];
			foreach ( $validation_result as $key => $errors ) {
				$field_errors[ $key ] = implode( ', ', $errors );
			}

			wp_send_json_error( [
				'field_error' => $field_errors,
				'message'     => implode( ' ', $field_errors ),
			] );
		}

		self::validateTransferRequest( $sender, $recipient_email, $transfer_points );
		$rateLimitCheck = self::validateRateLimit( $sender );
		if ( ! $rateLimitCheck ) {
			wp_send_json_error( [ 'message' => __( 'Too many requests from this Email try again after a minute', 'wp-loyalty-point-sharing' ) ] );
		}
		$transfer = self::createTransferRecord( $sender_email, $recipient_email, $transfer_points );

		if ( ! $transfer ) {
			wp_send_json_error( [ 'message' => __( 'Failed to create transfer record. Please try again later.', 'wp-loyalty-point-sharing' ) ] );

		}
		self::sendSenderEmail( $transfer );

		wp_send_json_success( [ 'message' => __( 'Confirmation email sent. Please check your inbox.', 'wp-loyalty-point-sharing' ) ] );
	}

	/**
	 * Validate Rate limit allows only certain request per minute for an ip+email.
	 *
	 * @param $sender
	 * return bool
	 */

	public static function validateRateLimit( $sender ): bool {
		$ip           = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$email        = $sender->user_email;
		$ip_email_key = "wlps_rate_limit_" . md5( $ip . "_" . $email );
		$count        = get_transient( $ip_email_key );
		$max_requests = apply_filters( "wlps_rate_limit_max_requests", self::TRANSFER_REQUEST_PER_MINUTE, $sender );

		if ( $count && $count >= $max_requests ) {
			return false;
		}
		set_transient( $ip_email_key, ( $count ? $count + 1 : 1 ), 60 );

		return true;
	}

	/**
	 * Validate Transfer Request.
	 *
	 * Performs validation checks before allowing the transfer:
	 * - Sender and recipient emails must not match.
	 * - Sender must have email opt-in enabled.
	 * - Recipient must not be banned.
	 * - Sender must have enough points.
	 *
	 * @param WP_User $sender Current logged-in user object.
	 * @param string $recipient_email Email of the recipient.
	 * @param int $transfer_points Number of points to transfer.
	 *
	 * @return void
	 */
	private static function validateTransferRequest( $sender, $recipient_email, $transfer_points ) {
		if ( $recipient_email === $sender->user_email ) {
			wp_send_json_error( [ 'message' => __( 'Recipient email and logged in email must not be same.', 'wp-loyalty-point-sharing' ) ] );

		}

		$sender = Common::getLoyaltyUser( $sender->user_email );
		if ( is_object( $sender ) && $sender->is_allow_send_email < 1 ) {
			wp_send_json_error( [ 'message' => __( 'Please Turn on Email Opt-in to transfer points.', 'wp-loyalty-point-sharing' ) ] );

		}

		if ( Util::isBannedUser( $recipient_email ) ) {
			wp_send_json_error( [ 'message' => __( 'The recipient user is banned cannot transfer points.', 'wp-loyalty-point-sharing' ) ] );

		}
		$base_helper = new Base();
		$user_points = $base_helper->getPointBalanceByEmail( $sender->user_email );
		if ( $user_points < $transfer_points ) {
			wp_send_json_error( [ 'message' => __( 'Not enough Points.', 'wp-loyalty-point-sharing' ) ] );

		}
	}

	/**
	 * Handle Confirm transfer.
	 *
	 */
	public static function handleConfirmTransfer() {

		$action = Input::get( 'wlps_action', '', 'query' );
		if ( $action !== 'confirm_transfer' ) {
			return;
		}
		$id            = intval( Input::get( 'transfer_id', '', 'query' ) );
		$token_raw     = sanitize_text_field( Input::get( 'token', '', 'query' ) ?? '' );
		$token_hash    = hash( 'sha256', $token_raw );
		$transferModel = new PointTransfers();
		$transfer      = $transferModel->findByIdAndToken( $id, $token_hash );
		if ( ! $transfer ) {
			wc_add_notice( __( 'Invalid or expired transfer link.', 'wp-loyalty-point-sharing' ), 'error' );

			return;
		}
		if ( ! self::checkSharePointEnabled( $transferModel, $transfer ) ) {

			return;
		}

		if ( ! self::validateTransferToken( $transfer ) ) {
			return;
		}

		if ( ! self::validateAuthorizedSender( $transfer ) ) {
			return;
		}
		if ( ! self::validateSenderAndRecipient( $transfer, $transferModel ) ) {
			return;
		}

		$transfer_success = self::performPointTransfer( $transfer );

		if ( $transfer_success ) {
			$transferModel->updateStatus(
				$transfer->id,
				PointTransferController::COMPLETED,
				sprintf(
				/* translators: 1: transfer success */
					__( 'Transfer successful: %1$d points sent to %2$s.', 'wp-loyalty-point-sharing' ),
					$transfer->points,
					$transfer->recipient_email
				)
			);

			wc_add_notice( __( 'Points transfer successful!', 'wp-loyalty-point-sharing' ), 'success' );

			PointTransferController::sendRecieverEmail( $transfer );
		} else {
			$transferModel->updateStatus(
				$transfer->id,
				PointTransferController::FAILED,
				__( 'Point transfer failed.', 'wp-loyalty-point-sharing' )
			);

			wc_add_notice( __( 'Point transfer failed. No points were sent.', 'wp-loyalty-point-sharing' ), 'error' );
		}
	}

	/**
	 *Check whether the share points enabled in settings.
	 *
	 * @params transferModal, transfer
	 */
	private static function checkSharePointEnabled( $transferModel, $transfer ) {
		$settings            = get_option( "wlps_settings", [] );
		$isSharePointEnabled = isset( $settings['enable_share_point'] ) && $settings['enable_share_point'] === 'yes';
		if ( ! $isSharePointEnabled ) {
			$transferModel->updateStatus( $transfer->id, PointTransferController::FAILED
				/* translators: 1: transfer failed */
				, sprintf( __( 'Transfer failed: Share points feature is disabled in settings.', 'wp-loyalty-point-sharing' ) )
			);
			wc_add_notice( __( 'Share Points feature is currently disabled. Transfer not processed.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		return true;
	}


	/**
	 * Validate Transfer Token.
	 *
	 * Ensures that the provided transfer token is valid, unexpired, and still pending.
	 * Marks transfer as expired or invalid if token check fails.
	 *
	 * @param object $transfer Transfer record object.
	 *
	 * @return bool True if valid, false otherwise.
	 */

	private static function validateTransferToken( $transfer ) {
		if ( empty( $transfer->token ) || ! $transfer ) {
			wc_add_notice( __( 'Invalid or expired transfer link.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		if ( $transfer->status !== PointTransferController::PENDING ) {
			wc_add_notice( __( 'This link has already been used.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}
		$expiry_time_in_minutes = apply_filters( "wlps_transfer_link_expiry_minutes", self::TRANSFER_LINK_EXPIRY, $transfer );
		if ( strtotime( gmdate( "Y-m-d H:i:s" ) ) > intval( $transfer->created_at ) + $expiry_time_in_minutes * MINUTE_IN_SECONDS ) {
			$pointTransfers = new PointTransfers();
			$pointTransfers->updateStatus( $transfer->id, PointTransferController::EXPIRED, sprintf( __( 'Transfer failed due to expired confirmation link.', 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'This transfer link has expired.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Validate Authorized Sender.
	 *
	 * Ensures that the user confirming the transfer is the same person
	 * who initiated it. Prevents unauthorized users from confirming.
	 *
	 * @param object $transfer Transfer record object.
	 *
	 * @return bool True if authorized, false otherwise.
	 */

	private static function validateAuthorizedSender( $transfer ) {
		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			wc_add_notice( __( 'Please log in to confirm your point transfer.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		if ( $current_user->user_email !== $transfer->sender_email ) {
			$pointTransfers = new PointTransfers();
			$pointTransfers->updateStatus( $transfer->id, PointTransferController::FAILED, sprintf( __( "Transfer failed: unauthorized user tried to confirm.", 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'You are not authorized to confirm this transfer.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Validate Sender and Recipient before confirming the point transfer.
	 *
	 * Performs validation checks to ensure both sender and recipient accounts are valid:
	 * - Verifies that neither the sender nor the recipient is banned.
	 * - Confirms that the sender has sufficient points to complete the transfer.
	 * - Updates the transfer status and displays WooCommerce error notices for any failures.
	 *
	 * @param object $transfer Transfer record object containing sender, recipient, and point details.
	 * @param PointTransfers $transferModel Instance of the PointTransfers model used to update transfer status.
	 *
	 * @return bool True if both sender and recipient are valid and the sender has enough points, false otherwise.
	 */

	private static function validateSenderAndRecipient( $transfer, $transferModel ) {
		$base_helper = new Base();

		if ( Util::isBannedUser( $transfer->sender_email ) ) {
			$transferModel->updateStatus( $transfer->id, PointTransferController::FAILED, sprintf( __( 'Transfer failed: sender account is banned.', 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'Your account is banned due to security concerns.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		if ( Util::isBannedUser( $transfer->recipient_email ) ) {
			$transferModel->updateStatus( $transfer->id, PointTransferController::FAILED, sprintf( __( 'Transfer failed: recipient account is banned.', 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'This user is banned due to security concerns.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		$user_points = $base_helper->getPointBalanceByEmail( $transfer->sender_email );
		if ( $user_points < $transfer->points ) {
			$transferModel->updateStatus( $transfer->id, PointTransferController::FAILED, sprintf( __( 'Transfer failed: Not Enough Points.', 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'Not enough points available for this transfer.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * Perform the actual point transfer between sender and recipient.
	 *
	 * Handles the logic to debit points from the sender and credit the same
	 * amount to the recipient. This method ensures that both transactions
	 * are processed atomically using the `Base` helper’s `addExtraPointAction()` method.
	 *
	 * If any exception occurs during the process, an error notice is added
	 * and the method returns false.
	 *
	 * @param object $transfer Transfer record object containing sender, recipient, and point details.
	 *                         Expected properties:
	 *                         - sender_email (string)
	 *                         - recipient_email (string)
	 *                         - points (int)
	 *
	 * @return bool True if both debit and credit operations are successful, false otherwise.
	 */

	private static function performPointTransfer( $transfer ) {
		try {
			$base_helper     = new Base();
			$sender_email    = $transfer->sender_email;
			$recipient_email = $transfer->recipient_email;
			$points          = intval( $transfer->points );

			/* translators:  1: number of points transferred, 2: recipient email */
			$debit = $base_helper->addExtraPointAction(
				'share_point_debit',
				$points,
				[
					'user_email'          => $sender_email,
					'action_type'         => 'share_point_debit',
					'action_process_type' => 'reduce_point',

					'note' => sprintf(
					/* translators: 1: number of points transferred, 2: recipient email */
						__( 'Transferred %1$d points to %2$s', 'wp-loyalty-point-sharing' ),
						$points,
						$recipient_email
					),

					'customer_note' => sprintf(
					/* translators: 1: number of points sent, 2: recipient email */
						__( 'Sent %1$d points to %2$s', 'wp-loyalty-point-sharing' ),
						$points,
						$recipient_email
					),
				],
				'debit',
				true
			);

			// Credit
			/* translators:  1: number of points received, 2: sender email */
			$credit = $base_helper->addExtraPointAction(
				'share_point_credit',
				$points,
				[
					'user_email'          => $recipient_email,
					'action_type'         => 'share_point_credit',
					'action_process_type' => 'add_point',

					'note' => sprintf(
					/* translators: 1: number of points received, 2: sender email */
						__( 'Received %1$d points from %2$s', 'wp-loyalty-point-sharing' ),
						$points,
						$sender_email
					),

					'customer_note' => sprintf(
					/* translators: 1: number of points received, 2: sender email */
						__( 'Received %1$d points from %2$s', 'wp-loyalty-point-sharing' ),
						$points,
						$sender_email
					),
				],
				'credit',
				true
			);

			return $credit && $debit;
		} catch ( \Throwable $e ) {
			wc_add_notice( __( 'An unexpected error occurred during the point transfer. Please try again later.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}
	}

	/**
	 * Create a new point transfer record.
	 *
	 * This method creates a pending transfer entry between a sender and recipient.
	 * It generates a secure token for confirmation, saves the transfer data using
	 * the PointTransfers model, and returns a structured object containing the transfer details.
	 *
	 * @param string $sender_email The email address of the user sending the points.
	 * @param string $recipient_email The email address of the user receiving the points.
	 * @param int $transfer_points The number of points to be transferred.
	 *
	 * @return object|false Returns an object containing:
	 *                      - transfer_id (int)     The ID of the created transfer record.
	 *                      - sender_email (string) The sender's email address.
	 *                      - recipient_email (string) The recipient's email address.
	 *                      - points (int)          The amount of points transferred.
	 *                      - token (string)        The unhashed confirmation token.
	 *                      Returns false if the operation fails or the model class is missing.
	 *
	 * @throws \Throwable If any unhandled exception occurs during record creation.
	 * @since 1.0.0
	 *
	 */

	private static function createTransferRecord( $sender_email, $recipient_email, $transfer_points ) {
		if ( ! class_exists( "Wlps\App\Models\PointTransfers" ) ) {
			return false;
		}

		try {
			$timestamp  = strtotime( gmdate( "Y-m-d H:i:s" ) );
			$raw_token  = bin2hex( random_bytes( 32 ) );
			$token_hash = hash( 'sha256', $raw_token );

			$pointTransfers = new PointTransfers();
			$data           = $pointTransfers->saveData( [
				'sender_email'    => $sender_email,
				'recipient_email' => $recipient_email,
				'points'          => $transfer_points,
				'status'          => PointTransferController::PENDING,
				'notes'           => sprintf(
				/* translators: 1: recipient email address waiting for confirmation */
					__( 'Transfer initiated — waiting for confirmation by %1$s.', 'wp-loyalty-point-sharing' ),
					$sender_email
				),

				'token'      => $token_hash,
				'created_at' => $timestamp,
				'updated_at' => $timestamp,
			] );

			return (object) [
				'transfer_id'     => $data,
				'sender_email'    => $sender_email,
				'recipient_email' => $recipient_email,
				'points'          => $transfer_points,
				'token'           => $raw_token,
			];

		} catch ( \Throwable $e ) {
			return false;
		}
	}

	/**
	 * Send a point transfer confirmation email to the sender.
	 *
	 * This method triggers a WooCommerce email notification for the sender
	 * when a new point transfer is initiated. It builds a confirmation link
	 * containing the transfer ID and secure token, then fires the
	 * `wlr_send_point_transfer_sender_email` action to send the email.
	 *
	 * @param object $transfer The transfer data object containing:
	 *                         - transfer_id (int)     The ID of the transfer record.
	 *                         - sender_email (string) The sender's email address.
	 *                         - recipient_email (string) The recipient's email address.
	 *                         - points (int)          The number of points transferred.
	 *                         - token (string)        The unhashed confirmation token.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */

	private static function sendSenderEmail( $transfer ) {
		\WC_Emails::instance();

		$confirm_link = add_query_arg( [
			'wlps_action' => 'confirm_transfer',
			'transfer_id' => $transfer->transfer_id,
			'token'       => $transfer->token,
		], site_url() );

		do_action(
			"wlps_send_point_transfer_sender_email",
			$transfer,
			$confirm_link
		);
	}

	/**
	 * Send a point transfer email to the receiver
	 *
	 * This method triggers a WooCommerce email notification for the receiver
	 * when a new point transfer is confirmed. It Builds a email to the receiver.
	 *
	 * @param object $transfer The transfer data object containing:
	 *                         - sender_email (string) The sender's email address.
	 *                         - recipient_email (string) The recipient's email address.
	 *                         - points (int)          The number of points transferred.
	 *
	 * @return void
	 * @since 1.0.0
	 *
	 */


	public static function sendRecieverEmail( $transfer ) {
		if ( empty( $transfer->recipient_email ) ) {
			return;
		}
		\WC_Emails::instance();
		do_action( "wlps_send_point_transfer_reciever_email",
			$transfer->recipient_email,
			$transfer->sender_email,
			$transfer->points );
	}
}