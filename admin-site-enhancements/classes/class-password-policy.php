<?php

namespace ASENHA\Classes;

use WP_Error;
use WP_REST_Request;
use WP_User;

/**
 * Class for Password Policy module.
 *
 * Free rules: minimum length, character classes, unique characters, silent max-length cap.
 * Extra rules can be attached via asenha_password_policy_evaluate / asenha_password_policy_get_policy.
 *
 * @since 9.0.2
 */
class Password_Policy {

	/**
	 * Silent maximum password length to limit hashing DoS. Not shown in the settings UI.
	 *
	 * @since 9.0.2
	 */
	const SILENT_MAX_LENGTH = 4096;

	/**
	 * Plugin basenames that conflict with this module.
	 *
	 * @since 9.0.2
	 * @var string[]
	 */
	const CONFLICT_PLUGINS = array(
		'password-requirements/password-requirements.php',
		'cn-password-policy/cn-password-policy.php',
	);

	/**
	 * Allowed values for the complexity checkbox group.
	 *
	 * @since 9.0.2
	 * @var string[]
	 */
	const REQUIRE_KEYS = array( 'uppercase', 'lowercase', 'digit', 'special' );

	/**
	 * Resolve the active policy for a password-set context.
	 *
	 * @since 9.0.2
	 * @param array $context Evaluation context (username, user, requested role, etc.).
	 * @return array {
	 *     @type int  $min_length
	 *     @type int  $max_length
	 *     @type bool $require_uppercase
	 *     @type bool $require_lowercase
	 *     @type bool $require_digit
	 *     @type bool $require_special
	 *     @type int  $min_unique_chars
	 * }
	 */
	public function get_policy( $context = array() ) {
		$options = get_option( ASENHA_SLUG_U, array() );
		$require = ( isset( $options['password_policy_require'] ) && is_array( $options['password_policy_require'] ) )
			? $options['password_policy_require']
			: array();

		$min_length = isset( $options['password_policy_min_length'] ) ? absint( $options['password_policy_min_length'] ) : 8;
		if ( $min_length < 1 ) {
			$min_length = 1;
		}

		$policy = array(
			'min_length'         => $min_length,
			'max_length'         => self::SILENT_MAX_LENGTH,
			'require_uppercase'  => in_array( 'uppercase', $require, true ),
			'require_lowercase'  => in_array( 'lowercase', $require, true ),
			'require_digit'      => in_array( 'digit', $require, true ),
			'require_special'    => in_array( 'special', $require, true ),
			'min_unique_chars'   => isset( $options['password_policy_min_unique_chars'] ) ? absint( $options['password_policy_min_unique_chars'] ) : 0,
		);

		/**
		 * Filter the resolved password policy.
		 *
		 * Pro attaches per-role resolution and extra rule flags here.
		 *
		 * @since 9.0.2
		 * @param array $policy  Resolved policy.
		 * @param array $context Evaluation context.
		 * @param array $options ASE options.
		 */
		return apply_filters( 'asenha_password_policy_get_policy', $policy, $context, $options );
	}

	/**
	 * Evaluate a password against the active policy.
	 *
	 * @since 9.0.2
	 * @param string $password Password in plaintext.
	 * @param array  $context  Evaluation context.
	 * @return true|WP_Error True when the password passes. WP_Error with one message per failed rule otherwise.
	 */
	public function evaluate( $password, $context = array() ) {
		$errors  = new WP_Error();
		$policy  = $this->get_policy( $context );
		$length  = $this->get_password_length( $password );
		$max_len = isset( $policy['max_length'] ) ? absint( $policy['max_length'] ) : self::SILENT_MAX_LENGTH;

		if ( $max_len > 0 && $length > $max_len ) {
			$errors->add(
				'asenha_password_policy_max_length',
				sprintf(
					/* translators: %d: maximum password length */
					_n(
						'The password must not be longer than %d character.',
						'The password must not be longer than %d characters.',
						$max_len,
						'admin-site-enhancements'
					),
					$max_len
				)
			);
		}

		$min_length = isset( $policy['min_length'] ) ? absint( $policy['min_length'] ) : 0;
		if ( $min_length > 0 && $length < $min_length ) {
			$errors->add(
				'asenha_password_policy_min_length',
				sprintf(
					/* translators: %d: minimum password length */
					_n(
						'The password must be at least %d character long.',
						'The password must be at least %d characters long.',
						$min_length,
						'admin-site-enhancements'
					),
					$min_length
				)
			);
		}

		if ( ! empty( $policy['require_uppercase'] ) && ! preg_match( '/[A-Z]/', $password ) ) {
			$errors->add(
				'asenha_password_policy_uppercase',
				__( 'The password must include an uppercase letter.', 'admin-site-enhancements' )
			);
		}

		if ( ! empty( $policy['require_lowercase'] ) && ! preg_match( '/[a-z]/', $password ) ) {
			$errors->add(
				'asenha_password_policy_lowercase',
				__( 'The password must include a lowercase letter.', 'admin-site-enhancements' )
			);
		}

		if ( ! empty( $policy['require_digit'] ) && ! preg_match( '/[0-9]/', $password ) ) {
			$errors->add(
				'asenha_password_policy_digit',
				__( 'The password must include a digit.', 'admin-site-enhancements' )
			);
		}

		if ( ! empty( $policy['require_special'] ) && ! preg_match( '/[^a-zA-Z0-9]/', $password ) ) {
			$errors->add(
				'asenha_password_policy_special',
				__( 'The password must include a special character.', 'admin-site-enhancements' )
			);
		}

		$min_unique = isset( $policy['min_unique_chars'] ) ? absint( $policy['min_unique_chars'] ) : 0;
		if ( $min_unique > 0 && $this->get_unique_character_count( $password ) < $min_unique ) {
			$errors->add(
				'asenha_password_policy_unique_chars',
				sprintf(
					/* translators: %d: minimum unique character count */
					_n(
						'The password must contain at least %d unique character.',
						'The password must contain at least %d unique characters.',
						$min_unique,
						'admin-site-enhancements'
					),
					$min_unique
				)
			);
		}

		/**
		 * Filter evaluation errors so Pro can append extra rule failures.
		 *
		 * @since 9.0.2
		 * @param WP_Error $errors   Accumulated errors.
		 * @param string   $password Password in plaintext.
		 * @param array    $policy   Resolved policy.
		 * @param array    $context  Evaluation context.
		 */
		$errors = apply_filters( 'asenha_password_policy_evaluate', $errors, $password, $policy, $context );

		if ( ! is_wp_error( $errors ) ) {
			$errors = new WP_Error();
		}

		return $errors->has_errors() ? $errors : true;
	}

	/**
	 * Replace the default WordPress password hint with a policy-specific sentence.
	 *
	 * Used by the password_hint filter.
	 *
	 * @since 9.0.2
	 * @param string $hint Default WordPress hint.
	 * @return string
	 */
	public function filter_password_hint( $hint ) {
		$context = $this->get_hint_context();
		$built   = $this->build_hint( $context );

		return ( '' !== $built ) ? $built : $hint;
	}

	/**
	 * Build a policy-specific hint from active rules.
	 *
	 * @since 9.0.2
	 * @param array $context Evaluation context.
	 * @return string Empty string when no rules produce a hint.
	 */
	public function build_hint( $context = array() ) {
		$policy = $this->get_policy( $context );
		$parts  = $this->get_hint_parts( $policy );

		/**
		 * Filter hint fragments so Pro can append extra-rule phrases.
		 *
		 * @since 9.0.2
		 * @param string[] $parts   Sentence fragments (e.g. "be at least 8 characters long").
		 * @param array    $policy  Resolved policy.
		 * @param array    $context Evaluation context.
		 */
		$parts = apply_filters( 'asenha_password_policy_hint_parts', $parts, $policy, $context );

		if ( ! is_array( $parts ) ) {
			$parts = array();
		}

		$parts = array_values( array_filter( array_map( 'strval', $parts ) ) );

		if ( empty( $parts ) ) {
			return '';
		}

		return sprintf(
			/* translators: %s: joined list of password requirements, e.g. "be at least 8 characters long and include an uppercase letter" */
			__( 'The password must %s.', 'admin-site-enhancements' ),
			$this->join_list( $parts )
		);
	}

	/**
	 * Print the policy hint on admin password screens.
	 *
	 * Core only calls wp_get_password_hint() on the wp-login.php reset form.
	 *
	 * @since 9.0.2
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_password_hint( $hook ) {
		if ( ! in_array( $hook, array( 'user-new.php', 'user-edit.php', 'profile.php' ), true ) ) {
			return;
		}

		$hint = $this->build_hint( $this->get_hint_context() );

		if ( '' === $hint ) {
			return;
		}

		wp_enqueue_script( 'user-profile' );
		wp_enqueue_style( 'forms' );

		wp_add_inline_style(
			'forms',
			'.wp-pwd .asenha-password-policy-hint{display:block;clear:both;margin-top:0;}'
		);

		wp_localize_script(
			'user-profile',
			'asenhaPasswordPolicy',
			array(
				'hint' => $hint,
			)
		);

		wp_add_inline_script(
			'user-profile',
			$this->get_admin_password_hint_script(),
			'after'
		);
	}

	/**
	 * Inline JS to place the policy hint below the password input and buttons.
	 *
	 * @since 9.0.2
	 * @return string
	 */
	private function get_admin_password_hint_script() {
		return <<<'JS'
( function ( $ ) {
	'use strict';

	$( function () {
		if (
			typeof asenhaPasswordPolicy === 'undefined' ||
			! asenhaPasswordPolicy.hint
		) {
			return;
		}

		var $pwd = $( '.wp-pwd' ).first();
		if ( ! $pwd.length ) {
			return;
		}

		var hintId = 'asenha-password-policy-hint';
		if ( $( '#' + hintId ).length ) {
			return;
		}

		var $hint = $( '<p></p>' )
			.attr( 'id', hintId )
			.addClass( 'description asenha-password-policy-hint' )
			.text( asenhaPasswordPolicy.hint );

		$pwd.append( $hint );

		var $pass = $( '#pass1' );
		if ( ! $pass.length ) {
			return;
		}

		var describedBy = ( $pass.attr( 'aria-describedby' ) || '' )
			.split( /\s+/ )
			.filter( function ( id ) {
				return '' !== id;
			} );

		if ( -1 === describedBy.indexOf( hintId ) ) {
			describedBy.push( hintId );
			$pass.attr( 'aria-describedby', describedBy.join( ' ' ) );
		}
	} );
} )( jQuery );
JS;
	}

	/**
	 * Validate password on wp-login.php registration.
	 *
	 * @since 9.0.2
	 * @param WP_Error $errors               Registration errors.
	 * @param string   $sanitized_user_login Sanitized username.
	 * @param string   $user_email           User email.
	 * @return WP_Error
	 */
	public function validate_registration( $errors, $sanitized_user_login, $user_email ) {
		$password = $this->get_submitted_password();

		if ( '' === $password ) {
			return $errors;
		}

		if ( ! is_wp_error( $errors ) ) {
			$errors = new WP_Error();
		}

		return $this->merge_errors(
			$errors,
			$this->evaluate(
				$password,
				array(
					'username' => $sanitized_user_login,
					'email'    => $user_email,
					'source'   => 'registration',
				)
			)
		);
	}

	/**
	 * Validate password on profile.php, user-edit.php, and user-new.php.
	 *
	 * @since 9.0.2
	 * @param WP_Error $errors Errors object, passed by reference by WordPress.
	 * @param bool     $update Whether this is an existing user update.
	 * @param object   $user   User object being saved.
	 * @return void
	 */
	public function validate_profile_update( $errors, $update, $user ) {
		$password = $this->get_submitted_password();

		if ( '' === $password ) {
			return;
		}

		if ( ! is_wp_error( $errors ) ) {
			$errors = new WP_Error();
		}

		$username = '';
		if ( is_object( $user ) && ! empty( $user->user_login ) ) {
			$username = $user->user_login;
		}

		$wp_user = null;
		if ( is_object( $user ) && ! empty( $user->ID ) ) {
			$wp_user = get_userdata( (int) $user->ID );
		}

		$this->merge_errors(
			$errors,
			$this->evaluate(
				$password,
				array(
					'username' => $username,
					'user'     => $wp_user instanceof WP_User ? $wp_user : null,
					'source'   => $update ? 'profile' : 'user-new',
				)
			)
		);
	}

	/**
	 * Validate password on the reset form.
	 *
	 * @since 9.0.2
	 * @param WP_Error      $errors Errors object.
	 * @param WP_User|false $user   User whose password is being reset.
	 * @return void
	 */
	public function validate_password_reset( $errors, $user ) {
		$password = $this->get_submitted_password();

		if ( '' === $password ) {
			return;
		}

		if ( ! is_wp_error( $errors ) ) {
			return;
		}

		$username = ( $user instanceof WP_User ) ? $user->user_login : '';

		$this->merge_errors(
			$errors,
			$this->evaluate(
				$password,
				array(
					'username' => $username,
					'user'     => ( $user instanceof WP_User ) ? $user : null,
					'source'   => 'reset',
				)
			)
		);
	}

	/**
	 * Validate password on multisite signup.
	 *
	 * @since 9.0.2
	 * @param array $result {
	 *     @type string   $user_name
	 *     @type string   $orig_username
	 *     @type string   $user_email
	 *     @type WP_Error $errors
	 * }
	 * @return array
	 */
	public function validate_mu_signup( $result ) {
		if ( ! is_array( $result ) ) {
			return $result;
		}

		$password = $this->get_submitted_password();

		if ( '' === $password ) {
			return $result;
		}

		if ( ! isset( $result['errors'] ) || ! is_wp_error( $result['errors'] ) ) {
			$result['errors'] = new WP_Error();
		}

		$username = isset( $result['user_name'] ) ? $result['user_name'] : '';

		$this->merge_errors(
			$result['errors'],
			$this->evaluate(
				$password,
				array(
					'username' => $username,
					'email'    => isset( $result['user_email'] ) ? $result['user_email'] : '',
					'source'   => 'mu-signup',
				)
			)
		);

		return $result;
	}

	/**
	 * Validate password on REST user create/update.
	 *
	 * @since 9.0.2
	 * @param object|WP_Error     $prepared_user User data for insert/update.
	 * @param WP_REST_Request     $request       Request.
	 * @return object|WP_Error
	 */
	public function validate_rest_user( $prepared_user, $request ) {
		if ( is_wp_error( $prepared_user ) ) {
			return $prepared_user;
		}

		$password = '';

		if ( $request instanceof WP_REST_Request ) {
			$maybe_password = $request->get_param( 'password' );
			if ( is_string( $maybe_password ) ) {
				$password = $maybe_password;
			}
		}

		if ( '' === $password ) {
			return $prepared_user;
		}

		$username = '';
		if ( is_object( $prepared_user ) && ! empty( $prepared_user->user_login ) ) {
			$username = $prepared_user->user_login;
		} elseif ( $request instanceof WP_REST_Request ) {
			$username = (string) $request->get_param( 'username' );
		}

		$user = null;
		if ( is_object( $prepared_user ) && ! empty( $prepared_user->ID ) ) {
			$maybe_user = get_userdata( (int) $prepared_user->ID );
			if ( $maybe_user instanceof WP_User ) {
				$user = $maybe_user;
			}
		}

		$result = $this->evaluate(
			$password,
			array(
				'username' => $username,
				'user'     => $user,
				'source'   => 'rest',
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $prepared_user;
	}

	/**
	 * Admin notice when another password-policy plugin is active.
	 *
	 * @since 9.0.2
	 * @return void
	 */
	public function maybe_show_conflict_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->has_conflicting_plugin() ) {
			return;
		}

		$message = __( 'Admin and Site Enhancements: Password Policy is active alongside another password-policy plugin. Please deactivate one of them to avoid conflicting validation.', 'admin-site-enhancements' );

		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Whether a known conflicting plugin is active.
	 *
	 * @since 9.0.2
	 * @return bool
	 */
	public function has_conflicting_plugin() {
		$active_plugins = (array) get_option( 'active_plugins', array() );
		$network_active = (array) get_site_option( 'active_sitewide_plugins', array() );

		foreach ( self::CONFLICT_PLUGINS as $basename ) {
			if ( in_array( $basename, $active_plugins, true ) ) {
				return true;
			}

			if ( isset( $network_active[ $basename ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Submitted password from the current request, unsanitized except for unslashing.
	 *
	 * @since 9.0.2
	 * @return string
	 */
	public function get_submitted_password() {
		$candidates = array( 'pass1', 'password', 'user_pass', 'account_password' );

		foreach ( $candidates as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			if ( ! is_string( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}

			$password = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( '' !== $password ) {
				return $password;
			}
		}

		return '';
	}

	/**
	 * Context for hint generation on admin/login screens.
	 *
	 * @since 9.0.2
	 * @return array
	 */
	private function get_hint_context() {
		$context = array(
			'source' => 'hint',
		);

		$user_id = 0;

		if ( isset( $_GET['user_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$user_id = absint( $_GET['user_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( $user_id < 1 && function_exists( 'get_current_user_id' ) ) {
			$user_id = get_current_user_id();
		}

		if ( $user_id > 0 ) {
			$user = get_userdata( $user_id );
			if ( $user instanceof WP_User ) {
				$context['user']     = $user;
				$context['username'] = $user->user_login;
			}
		}

		return $context;
	}

	/**
	 * Hint fragments for free rules. Skip off/zero values. Do not include the silent max-length cap.
	 *
	 * @since 9.0.2
	 * @param array $policy Resolved policy.
	 * @return string[]
	 */
	private function get_hint_parts( $policy ) {
		$parts = array();

		$min_length = isset( $policy['min_length'] ) ? absint( $policy['min_length'] ) : 0;
		if ( $min_length > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: minimum password length */
				_n(
					'be at least %d character long',
					'be at least %d characters long',
					$min_length,
					'admin-site-enhancements'
				),
				$min_length
			);
		}

		$include = array();

		if ( ! empty( $policy['require_uppercase'] ) ) {
			$include[] = __( 'an uppercase letter', 'admin-site-enhancements' );
		}

		if ( ! empty( $policy['require_lowercase'] ) ) {
			$include[] = __( 'a lowercase letter', 'admin-site-enhancements' );
		}

		if ( ! empty( $policy['require_digit'] ) ) {
			$include[] = __( 'a digit', 'admin-site-enhancements' );
		}

		if ( ! empty( $policy['require_special'] ) ) {
			$include[] = __( 'a special character', 'admin-site-enhancements' );
		}

		if ( ! empty( $include ) ) {
			$parts[] = sprintf(
				/* translators: %s: joined list of character-class requirements */
				__( 'include %s', 'admin-site-enhancements' ),
				$this->join_list( $include )
			);
		}

		$min_unique = isset( $policy['min_unique_chars'] ) ? absint( $policy['min_unique_chars'] ) : 0;
		if ( $min_unique > 0 ) {
			$parts[] = sprintf(
				/* translators: %d: minimum unique character count */
				_n(
					'contain at least %d unique character',
					'contain at least %d unique characters',
					$min_unique,
					'admin-site-enhancements'
				),
				$min_unique
			);
		}

		return $parts;
	}

	/**
	 * Join a list of phrases with "and" for the last item.
	 *
	 * @since 9.0.2
	 * @param string[] $items Phrases.
	 * @return string
	 */
	private function join_list( $items ) {
		$items = array_values( $items );
		$count = count( $items );

		if ( 0 === $count ) {
			return '';
		}

		if ( 1 === $count ) {
			return $items[0];
		}

		if ( 2 === $count ) {
			return sprintf(
				/* translators: 1: first item, 2: second item */
				__( '%1$s and %2$s', 'admin-site-enhancements' ),
				$items[0],
				$items[1]
			);
		}

		$last = array_pop( $items );

		return sprintf(
			/* translators: 1: comma-separated items, 2: last item */
			__( '%1$s, and %2$s', 'admin-site-enhancements' ),
			implode( ', ', $items ),
			$last
		);
	}

	/**
	 * Merge WP_Error messages from evaluation into an existing errors object.
	 *
	 * @since 9.0.2
	 * @param WP_Error      $errors Existing errors.
	 * @param true|WP_Error $result Evaluation result.
	 * @return WP_Error
	 */
	private function merge_errors( $errors, $result ) {
		if ( ! is_wp_error( $result ) ) {
			return $errors;
		}

		foreach ( $result->get_error_codes() as $code ) {
			foreach ( $result->get_error_messages( $code ) as $message ) {
				$errors->add( $code, $message );
			}
		}

		return $errors;
	}

	/**
	 * Password length, Unicode-aware when mbstring is available.
	 *
	 * @since 9.0.2
	 * @param string $password Password.
	 * @return int
	 */
	private function get_password_length( $password ) {
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $password, 'UTF-8' );
		}

		return strlen( $password );
	}

	/**
	 * Count unique characters, Unicode-aware when possible.
	 *
	 * @since 9.0.2
	 * @param string $password Password.
	 * @return int
	 */
	private function get_unique_character_count( $password ) {
		if ( function_exists( 'mb_str_split' ) ) {
			$chars = mb_str_split( $password, 1, 'UTF-8' );
		} else {
			$chars = preg_split( '//u', $password, -1, PREG_SPLIT_NO_EMPTY );
			if ( ! is_array( $chars ) ) {
				$chars = str_split( $password );
			}
		}

		return count( array_unique( $chars ) );
	}

}
