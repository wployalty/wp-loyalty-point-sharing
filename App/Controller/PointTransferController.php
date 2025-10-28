<?php

namespace Wlps\App\Controller;

use Wlps\App\Helpers\Input;
use Wlps\App\Helpers\Validation;
use Wlps\App\Helpers\WlpsUtil;
use Wlps\App\Models\PointTransfers;
use Wlr\App\Helpers\Base;

class PointTransferController {
	const TRANSFER_LINK_EXPIRY = 15;
	const COMPLETED = "completed";
	const FAILED = "failed";
	const EXPIRED = "expired";
	const PENDING = "pending";

	public static function transferPoints() {
		$wlps_nonce = (string) Input::get( 'wlps_transfer_points_nonce', '', 'post' );
		if ( ! WlpsUtil::verify_nonce( $wlps_nonce, 'wlps-transfer-points-nonce' ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Cannot Transfer Points nonce verification failed', 'wp-loyalty-point-sharing' ),
			] );
		}


		$sender          = wp_get_current_user();
		$sender_email    = $sender->user_email;
		$recipient_email = Input::get( 'transfer_email', '', 'post' );
		$transfer_points = Input::get( 'transfer_points', '', 'post' );

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

		$transfer = self::createTransferRecord( $sender_email, $recipient_email, $transfer_points );

		if ( ! $transfer ) {
			wp_send_json_error( [ 'message' => __( 'Failed to create transfer record. Please try again later.', 'wp-loyalty-point-sharing' ) ] );

		}
		self::sendSenderEmail( $transfer );

		wp_send_json_success( [ 'message' => __( 'Confirmation email sent. Please check your inbox.', 'wp-loyalty-point-sharing' ) ] );
	}

	private static function validateTransferRequest( $sender, $recipient_email, $transfer_points ) {
		if ( $recipient_email === $sender->user_email ) {
			wp_send_json_error( [ 'message' => __( 'Recipient email and logged in email must not be same.', 'wp-loyalty-point-sharing' ) ] );

		}

		$sender = Common::getLoyaltyUser( $sender->user_email );
		if ( is_object( $sender ) && $sender->is_allow_send_email < 1 ) {
			wp_send_json_error( [ 'message' => __( 'Please Turn on Email Opt-in to transfer points.', 'wp-loyalty-point-sharing' ) ] );

		}

		if ( WlpsUtil::isBannedUser( $recipient_email ) ) {
			wp_send_json_error( [ 'message' => __( 'The recipient user is banned cannot transfer points.', 'wp-loyalty-point-sharing' ) ] );

		}
		$base_helper = new Base();
		$user_points = $base_helper->getPointBalanceByEmail( $sender->user_email );
		if ( $user_points < $transfer_points ) {
			wp_send_json_error( [ 'message' => __( 'Not enough Points.', 'wp-loyalty-point-sharing' ) ] );

		}
	}

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


	private static function validateTransferToken( $transfer ) {
		if ( empty( $transfer->token ) || ! $transfer ) {
			wc_add_notice( __( 'Invalid or expired transfer link.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		if ( $transfer->status !== PointTransferController::PENDING ) {
			wc_add_notice( __( 'This link has already been used.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		if ( strtotime( gmdate( "Y-m-d H:i:s" ) ) > intval( $transfer->created_at ) + PointTransferController::TRANSFER_LINK_EXPIRY * MINUTE_IN_SECONDS ) {
			$pointTransfers = new PointTransfers();
			$pointTransfers->updateStatus( $transfer->id, PointTransferController::EXPIRED, sprintf( __( 'Transfer failed due to expired confirmation link.', 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'This transfer link has expired.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		return true;
	}


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


	private static function validateSenderAndRecipient( $transfer, $transferModel ) {
		$base_helper = new Base();

		if ( WlpsUtil::isBannedUser( $transfer->sender_email ) ) {
			$transferModel->updateStatus( $transfer->id, PointTransferController::FAILED, sprintf( __( 'Transfer failed: sender account is banned.', 'wp-loyalty-point-sharing' ) ) );

			wc_add_notice( __( 'Your account is banned due to security concerns.', 'wp-loyalty-point-sharing' ), 'error' );

			return false;
		}

		if ( WlpsUtil::isBannedUser( $transfer->recipient_email ) ) {
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

	private static function sendSenderEmail( $transfer ) {
		\WC_Emails::instance();

		$confirm_link = add_query_arg( [
			'wlps_action' => 'confirm_transfer',
			'transfer_id' => $transfer->transfer_id,
			'token'       => $transfer->token,
		], site_url() );

		do_action(
			"wlr_send_point_transfer_sender_email",
			$transfer,
			$confirm_link
		);
	}


	public static function sendRecieverEmail( $transfer ) {
		if ( empty( $transfer->recipient_email ) ) {
			return;
		}
		\WC_Emails::instance();
		do_action( "wlr_send_point_transfer_reciever_email",
			$transfer->recipient_email,
			$transfer->sender_email,
			$transfer->points );
	}
}