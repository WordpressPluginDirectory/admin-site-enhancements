<?php
/**
 * Contact Form AJAX submission handler.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX form submissions.
 */
class Contact_Form_Handler {

	/**
	 * Maximum length for the subject and name fields.
	 */
	const MAX_SHORT_FIELD_LENGTH = 255;

	/**
	 * Maximum length for the message field.
	 */
	const MAX_MESSAGE_LENGTH = 20000;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_asenha_contact_form_submit', array( $this, 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_asenha_contact_form_submit', array( $this, 'handle_submit' ) );
	}

	/**
	 * Process a contact form submission.
	 *
	 * @return void
	 */
	public function handle_submit() {
		$nonce = sanitize_text_field( Contact_Form::get_post_string( $_POST, 'asenha_cf_nonce' ) );

		if ( ! wp_verify_nonce( $nonce, 'asenha_contact_form_submit' ) ) {
			$this->send_generic_error();
			return;
		}

		if ( Contact_Form::has_invalid_post_strings(
			$_POST,
			array(
				'asenha_cf_subject',
				'asenha_cf_message',
				'asenha_cf_name',
				'asenha_cf_email',
				'asenha_cf_payload',
				'asenha_cf_signature',
				'asenha_cf_js',
				'asenha_cf_sent_from',
			)
		) ) {
			$this->send_generic_error();
			return;
		}

		$spam_result = Contact_Form_Spam::validate_submission( $_POST );

		if ( empty( $spam_result['passed'] ) ) {
			if ( ! empty( $spam_result['neutral'] ) ) {
				$this->send_neutral_success();
				return;
			}

			$this->send_generic_error();
			return;
		}

		$subject = sanitize_text_field( Contact_Form::get_post_string( $_POST, 'asenha_cf_subject' ) );
		$message = sanitize_textarea_field( Contact_Form::get_post_string( $_POST, 'asenha_cf_message' ) );
		$name    = sanitize_text_field( Contact_Form::get_post_string( $_POST, 'asenha_cf_name' ) );
		$email   = sanitize_email( Contact_Form::get_post_string( $_POST, 'asenha_cf_email' ) );

		$errors = array();

		if ( '' === $subject ) {
			$errors['asenha_cf_subject'] = __( 'Please enter a subject.', 'admin-site-enhancements' );
		}

		if ( '' === $message ) {
			$errors['asenha_cf_message'] = __( 'Please enter a message.', 'admin-site-enhancements' );
		}

		if ( '' === $name ) {
			$errors['asenha_cf_name'] = __( 'Please enter your name.', 'admin-site-enhancements' );
		}

		if ( '' === $email || ! is_email( $email ) ) {
			$errors['asenha_cf_email'] = __( 'Please enter a valid email address.', 'admin-site-enhancements' );
		}

		if ( mb_strlen( $subject ) > self::MAX_SHORT_FIELD_LENGTH ) {
			$errors['asenha_cf_subject'] = sprintf(
				/* translators: %d: maximum number of characters allowed */
				__( 'Subject must be %d characters or fewer.', 'admin-site-enhancements' ),
				self::MAX_SHORT_FIELD_LENGTH
			);
		}

		if ( mb_strlen( $name ) > self::MAX_SHORT_FIELD_LENGTH ) {
			$errors['asenha_cf_name'] = sprintf(
				/* translators: %d: maximum number of characters allowed */
				__( 'Name must be %d characters or fewer.', 'admin-site-enhancements' ),
				self::MAX_SHORT_FIELD_LENGTH
			);
		}

		if ( mb_strlen( $message ) > self::MAX_MESSAGE_LENGTH ) {
			$errors['asenha_cf_message'] = sprintf(
				/* translators: %d: maximum number of characters allowed */
				__( 'Message must be %d characters or fewer.', 'admin-site-enhancements' ),
				self::MAX_MESSAGE_LENGTH
			);
		}

		if ( ! empty( $errors ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please correct the highlighted fields.', 'admin-site-enhancements' ),
					'errors'  => $errors,
				)
			);
		}

		$submission = array(
			'subject' => $subject,
			'message' => $message,
			'name'    => $name,
			'email'   => $email,
		);

		$insert_id = Contact_Form_Submission::insert( $submission );

		if ( false === $insert_id ) {
			$this->send_generic_error();
			return;
		}

		$submission_record = Contact_Form_Submission::get( $insert_id );

		if ( ! empty( $submission_record ) ) {
			$sent_from = esc_url_raw( Contact_Form::get_post_string( $_POST, 'asenha_cf_sent_from' ) );

			if ( self::is_valid_sent_from_url( $sent_from ) ) {
				$submission_record['sent_from'] = $sent_from;
			}

			Contact_Form_Email::send_notification( $submission_record );
		}

		wp_send_json_success(
			array(
				'message' => Contact_Form::get_settings()['success_message'],
			)
		);
	}

	/**
	 * Send a generic error response.
	 *
	 * @return void
	 */
	private function send_generic_error() {
		wp_send_json_error(
			array(
				'message' => Contact_Form::get_settings()['error_message'],
			)
		);
	}

	/**
	 * Send a neutral success response for spam-like submissions.
	 *
	 * @return void
	 */
	private function send_neutral_success() {
		wp_send_json_success(
			array(
				'message' => Contact_Form::get_settings()['success_message'],
			)
		);
	}

	/**
	 * Validate that a sent-from URL is a same-site absolute URL.
	 *
	 * @param string $url Candidate page URL from the browser.
	 * @return bool
	 */
	private static function is_valid_sent_from_url( $url ) {
		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return false;
		}

		$url_host  = wp_parse_url( $url, PHP_URL_HOST );
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( empty( $url_host ) || empty( $site_host ) ) {
			return false;
		}

		return strtolower( $url_host ) === strtolower( $site_host );
	}
}
