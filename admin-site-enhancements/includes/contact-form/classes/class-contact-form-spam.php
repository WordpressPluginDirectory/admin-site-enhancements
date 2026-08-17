<?php
/**
 * Contact Form passive anti-spam checks.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Validates submissions using layered passive anti-spam checks.
 */
class Contact_Form_Spam {

	/**
	 * Minimum seconds before a submission is accepted.
	 */
	const MIN_SUBMIT_SECONDS = 3;

	/**
	 * Maximum seconds before a form token expires.
	 */
	const MAX_FORM_AGE_SECONDS = 7200;

	/**
	 * Rate limit per hashed IP per hour.
	 */
	const RATE_LIMIT_IP = 5;

	/**
	 * Rate limit per hashed email per hour.
	 */
	const RATE_LIMIT_EMAIL = 3;

	/**
	 * Duplicate suppression window in seconds.
	 */
	const DUPLICATE_WINDOW = 300;

	/**
	 * Generate signed anti-spam metadata for a form render.
	 *
	 * @return array<string, string>
	 */
	public static function generate_form_tokens() {
		$rendered_at       = time();
		$honeypot_name     = 'asenha_cf_' . wp_generate_password( 10, false, false );
		$token_seed        = wp_generate_password( 20, false, false );
		$payload           = array(
			'rendered_at'   => $rendered_at,
			'honeypot_name' => $honeypot_name,
			'token_seed'    => $token_seed,
		);
		$encoded_payload   = base64_encode( wp_json_encode( $payload ) );
		$signature         = self::sign_payload( $encoded_payload );

		return array(
			'payload'       => $encoded_payload,
			'signature'     => $signature,
			'honeypot_name' => $honeypot_name,
			'js_token'      => self::derive_js_token( $token_seed ),
		);
	}

	/**
	 * Validate a submission against passive anti-spam rules.
	 *
	 * @param array<string, mixed> $post_data Raw POST data.
	 * @return array<string, mixed> Validation result.
	 */
	public static function validate_submission( $post_data ) {
		$settings = Contact_Form::get_settings();

		if ( ! empty( $settings['disable_antispam'] ) ) {
			return array(
				'passed' => true,
			);
		}

		$payload_data = self::decode_payload( $post_data );

		if ( is_wp_error( $payload_data ) ) {
			return self::bot_failure();
		}

		if ( self::honeypot_filled( $post_data, $payload_data['honeypot_name'] ) ) {
			return self::bot_failure();
		}

		if ( ! self::verify_js_token( $post_data, $payload_data['token_seed'] ) ) {
			return self::bot_failure();
		}

		if ( ! self::verify_timing( (int) $payload_data['rendered_at'] ) ) {
			return self::bot_failure();
		}

		if ( ! self::verify_origin() ) {
			return self::bot_failure();
		}

		$subject = sanitize_text_field( Contact_Form::get_post_string( $post_data, 'asenha_cf_subject' ) );
		$message = sanitize_textarea_field( Contact_Form::get_post_string( $post_data, 'asenha_cf_message' ) );
		$name    = sanitize_text_field( Contact_Form::get_post_string( $post_data, 'asenha_cf_name' ) );
		$email   = sanitize_email( Contact_Form::get_post_string( $post_data, 'asenha_cf_email' ) );

		if ( self::is_rate_limited( $email ) ) {
			return self::neutral_failure();
		}

		if ( self::is_duplicate_submission( $subject, $message, $email ) ) {
			return self::neutral_failure();
		}

		if ( self::fails_content_heuristics( $subject, $message, $name, $email ) ) {
			return self::neutral_failure();
		}

		if ( self::matches_disallowed_keys( $subject, $message, $name, $email ) ) {
			return self::neutral_failure();
		}

		if ( self::is_akismet_spam( $subject, $message, $name, $email ) ) {
			return self::neutral_failure();
		}

		self::record_rate_limit( $email );
		self::record_duplicate_submission( $subject, $message, $email );

		return array(
			'passed' => true,
		);
	}

	/**
	 * Decode and verify the signed anti-spam payload.
	 *
	 * @param array<string, mixed> $post_data Raw POST data.
	 * @return array<string, mixed>|\WP_Error
	 */
	private static function decode_payload( $post_data ) {
		$encoded_payload = sanitize_text_field( Contact_Form::get_post_string( $post_data, 'asenha_cf_payload' ) );
		$signature       = sanitize_text_field( Contact_Form::get_post_string( $post_data, 'asenha_cf_signature' ) );

		if ( empty( $encoded_payload ) || empty( $signature ) ) {
			return new \WP_Error( 'missing_payload', 'missing_payload' );
		}

		if ( ! hash_equals( self::sign_payload( $encoded_payload ), $signature ) ) {
			return new \WP_Error( 'invalid_signature', 'invalid_signature' );
		}

		$decoded = json_decode( base64_decode( $encoded_payload ), true );

		if ( ! is_array( $decoded ) || empty( $decoded['rendered_at'] ) || empty( $decoded['honeypot_name'] ) || empty( $decoded['token_seed'] ) ) {
			return new \WP_Error( 'invalid_payload', 'invalid_payload' );
		}

		return $decoded;
	}

	/**
	 * Sign an encoded payload.
	 *
	 * @param string $encoded_payload Base64 payload.
	 * @return string
	 */
	private static function sign_payload( $encoded_payload ) {
		return hash_hmac( 'sha256', $encoded_payload, wp_salt( 'auth' ) );
	}

	/**
	 * Derive the expected JS verification token.
	 *
	 * @param string $token_seed Token seed.
	 * @return string
	 */
	public static function derive_js_token( $token_seed ) {
		return hash_hmac( 'sha256', $token_seed, wp_salt( 'nonce' ) );
	}

	/**
	 * Check whether the honeypot field was filled.
	 *
	 * @param array<string, mixed> $post_data      Raw POST data.
	 * @param string               $honeypot_name  Honeypot field name.
	 * @return bool
	 */
	private static function honeypot_filled( $post_data, $honeypot_name ) {
		if ( empty( $honeypot_name ) || ! isset( $post_data[ $honeypot_name ] ) || ! is_string( $post_data[ $honeypot_name ] ) ) {
			return true;
		}

		return '' !== trim( sanitize_text_field( Contact_Form::get_post_string( $post_data, $honeypot_name ) ) );
	}

	/**
	 * Verify the JS token submitted by the browser.
	 *
	 * @param array<string, mixed> $post_data   Raw POST data.
	 * @param string               $token_seed  Token seed from payload.
	 * @return bool
	 */
	private static function verify_js_token( $post_data, $token_seed ) {
		$submitted_token = sanitize_text_field( Contact_Form::get_post_string( $post_data, 'asenha_cf_js' ) );
		$expected_token  = self::derive_js_token( $token_seed );

		return ! empty( $submitted_token ) && hash_equals( $expected_token, $submitted_token );
	}

	/**
	 * Verify minimum and maximum form age.
	 *
	 * @param int $rendered_at Unix timestamp when the form was rendered.
	 * @return bool
	 */
	private static function verify_timing( $rendered_at ) {
		$elapsed = time() - $rendered_at;

		return $elapsed >= self::MIN_SUBMIT_SECONDS && $elapsed <= self::MAX_FORM_AGE_SECONDS;
	}

	/**
	 * Soft same-site origin check.
	 *
	 * @return bool
	 */
	private static function verify_origin() {
		$origin  = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

		if ( empty( $origin ) && empty( $referer ) ) {
			return true;
		}

		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		if ( ! empty( $origin ) ) {
			$origin_host = wp_parse_url( $origin, PHP_URL_HOST );
			if ( ! empty( $origin_host ) && $origin_host !== $site_host ) {
				return false;
			}
		}

		if ( ! empty( $referer ) ) {
			$referer_host = wp_parse_url( $referer, PHP_URL_HOST );
			if ( ! empty( $referer_host ) && $referer_host !== $site_host ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check whether the request is rate limited.
	 *
	 * @param string $email Submitter email.
	 * @return bool
	 */
	private static function is_rate_limited( $email ) {
		$ip_hash    = self::get_ip_hash();
		$email_hash = self::hash_value( strtolower( $email ) );

		if ( ! empty( $ip_hash ) ) {
			$ip_count = (int) get_transient( 'asenha_cf_rl_ip_' . $ip_hash );
			if ( $ip_count >= self::RATE_LIMIT_IP ) {
				return true;
			}
		}

		if ( ! empty( $email_hash ) ) {
			$email_count = (int) get_transient( 'asenha_cf_rl_email_' . $email_hash );
			if ( $email_count >= self::RATE_LIMIT_EMAIL ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Record a successful submission for rate limiting.
	 *
	 * @param string $email Submitter email.
	 * @return void
	 */
	private static function record_rate_limit( $email ) {
		$ip_hash    = self::get_ip_hash();
		$email_hash = self::hash_value( strtolower( $email ) );

		if ( ! empty( $ip_hash ) ) {
			$key   = 'asenha_cf_rl_ip_' . $ip_hash;
			$count = (int) get_transient( $key );
			set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		}

		if ( ! empty( $email_hash ) ) {
			$key   = 'asenha_cf_rl_email_' . $email_hash;
			$count = (int) get_transient( $key );
			set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		}
	}

	/**
	 * Check whether an identical submission was recently sent.
	 *
	 * @param string $subject Submission subject.
	 * @param string $message Submission message.
	 * @param string $email   Submitter email.
	 * @return bool
	 */
	private static function is_duplicate_submission( $subject, $message, $email ) {
		$hash = self::hash_value( strtolower( trim( $subject . '|' . $message . '|' . $email ) ) );
		$key  = 'asenha_cf_dup_' . $hash;

		return (bool) get_transient( $key );
	}

	/**
	 * Record a submission fingerprint for duplicate suppression.
	 *
	 * @param string $subject Submission subject.
	 * @param string $message Submission message.
	 * @param string $email   Submitter email.
	 * @return void
	 */
	private static function record_duplicate_submission( $subject, $message, $email ) {
		$hash = self::hash_value( strtolower( trim( $subject . '|' . $message . '|' . $email ) ) );
		set_transient( 'asenha_cf_dup_' . $hash, 1, self::DUPLICATE_WINDOW );
	}

	/**
	 * Run conservative content heuristics.
	 *
	 * @param string $subject Submission subject.
	 * @param string $message Submission message.
	 * @param string $name    Submitter name.
	 * @param string $email   Submitter email.
	 * @return bool
	 */
	private static function fails_content_heuristics( $subject, $message, $name, $email ) {
		$combined = $subject . ' ' . $message . ' ' . $name . ' ' . $email;

		if ( strlen( $subject ) > 255 || strlen( $name ) > 255 || strlen( $message ) > 20000 ) {
			return true;
		}

		$url_count = preg_match_all( '#https?://#i', $combined );
		if ( $url_count > 5 ) {
			return true;
		}

		if ( preg_match( '/(.)\1{12,}/', $combined ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check WordPress disallowed comment keys.
	 *
	 * @param string $subject Submission subject.
	 * @param string $message Submission message.
	 * @param string $name    Submitter name.
	 * @param string $email   Submitter email.
	 * @return bool
	 */
	private static function matches_disallowed_keys( $subject, $message, $name, $email ) {
		if ( function_exists( 'wp_check_comment_disallowed_list' ) ) {
			return (bool) wp_check_comment_disallowed_list( $name, $email, '', $message . ' ' . $subject, wp_parse_url( home_url(), PHP_URL_HOST ), '' );
		}

		return false;
	}

	/**
	 * Check Akismet when available.
	 *
	 * @param string $subject Submission subject.
	 * @param string $message Submission message.
	 * @param string $name    Submitter name.
	 * @param string $email   Submitter email.
	 * @return bool
	 */
	private static function is_akismet_spam( $subject, $message, $name, $email ) {
		if ( ! function_exists( 'akismet_http_post' ) || ! class_exists( 'Akismet' ) ) {
			return false;
		}

		$api_key = \Akismet::get_api_key();
		if ( empty( $api_key ) ) {
			return false;
		}

		$request = array(
			'blog'                 => home_url(),
			'blog_lang'            => get_locale(),
			'blog_charset'         => get_bloginfo( 'charset' ),
			'comment_type'         => 'contact-form',
			'comment_author'       => $name,
			'comment_author_email' => $email,
			'comment_content'      => $message . "\n\n" . $subject,
			'user_ip'              => self::get_request_ip(),
			'referrer'             => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '',
		);

		$response = akismet_http_post( build_query( $request ), 'comment-check' );

		if ( is_array( $response ) && isset( $response[1] ) && 'true' === trim( $response[1] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get a hashed request IP for ephemeral rate limiting.
	 *
	 * @return string
	 */
	private static function get_ip_hash() {
		$ip = self::get_request_ip();

		if ( empty( $ip ) ) {
			return '';
		}

		return self::hash_value( $ip );
	}

	/**
	 * Get the request IP address.
	 *
	 * @return string
	 */
	private static function get_request_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Hash a value for transient keys.
	 *
	 * @param string $value Value to hash.
	 * @return string
	 */
	private static function hash_value( $value ) {
		return hash_hmac( 'sha256', $value, wp_salt( 'secure_auth' ) );
	}

	/**
	 * Build a hard bot failure response.
	 *
	 * @return array<string, mixed>
	 */
	private static function bot_failure() {
		return array(
			'passed'  => false,
			'neutral' => false,
		);
	}

	/**
	 * Build a neutral failure response.
	 *
	 * @return array<string, mixed>
	 */
	private static function neutral_failure() {
		return array(
			'passed'  => false,
			'neutral' => true,
		);
	}
}
