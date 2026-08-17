<?php
/**
 * Contact Form module orchestrator.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the Contact Form module.
 */
class Contact_Form {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'maybe_create_table' ) );
		// Register on init so the handle exists before block themes render content
		// (which can run before wp_enqueue_scripts and break wp_localize_script).
		add_action( 'init', array( $this, 'register_frontend_assets' ) );

		new Contact_Form_Shortcode();
		new Contact_Form_Block();
		new Contact_Form_Handler();
		new Contact_Form_Admin();
	}

	/**
	 * Ensure the submissions table exists after plugin updates.
	 *
	 * @return void
	 */
	public function maybe_create_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'asenha_contact_form_submissions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
			$activation = new Activation();
			$activation->create_contact_form_submissions_table();
		}
	}

	/**
	 * Register frontend scripts and styles for later enqueue.
	 *
	 * @return void
	 */
	public static function register_frontend_assets() {
		wp_register_style(
			'asenha-contact-form-layout',
			CONTACT_FORM_URL . 'assets/css/contact-form-layout.css',
			array(),
			CONTACT_FORM_VERSION
		);

		wp_register_style(
			'asenha-contact-form-default',
			CONTACT_FORM_URL . 'assets/css/contact-form-default.css',
			array(),
			CONTACT_FORM_VERSION
		);

		wp_register_script(
			'asenha-contact-form-frontend',
			CONTACT_FORM_URL . 'assets/js/contact-form-frontend.js',
			array( 'jquery' ),
			CONTACT_FORM_VERSION,
			true
		);
	}

	/**
	 * Get a string value from a request array.
	 *
	 * Rejecting arrays before unslashing and sanitizing prevents malformed
	 * public requests from reaching string-only WordPress functions.
	 *
	 * @param array<string, mixed> $request Request data.
	 * @param string               $key     Request key.
	 * @return string
	 */
	public static function get_post_string( $request, $key ) {
		if ( ! is_array( $request ) || ! isset( $request[ $key ] ) || ! is_string( $request[ $key ] ) ) {
			return '';
		}

		return wp_unslash( $request[ $key ] );
	}

	/**
	 * Check whether known request values are non-string.
	 *
	 * @param array<string, mixed> $request Request data.
	 * @param string[]             $keys    Request keys to check.
	 * @return bool
	 */
	public static function has_invalid_post_strings( $request, $keys ) {
		if ( ! is_array( $request ) ) {
			return true;
		}

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $request ) && ! is_string( $request[ $key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get module settings from the main ASE options array.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		$options = get_option( ASENHA_SLUG_U, array() );

		$label_defaults = array(
			'subject_label' => array(
				'option_key' => 'contact_form_label_subject',
				'default'    => __( 'Subject', 'admin-site-enhancements' ),
			),
			'message_label' => array(
				'option_key' => 'contact_form_label_message',
				'default'    => __( 'Message', 'admin-site-enhancements' ),
			),
			'name_label'    => array(
				'option_key' => 'contact_form_label_name',
				'default'    => __( 'Name', 'admin-site-enhancements' ),
			),
			'email_label'   => array(
				'option_key' => 'contact_form_label_email',
				'default'    => __( 'Email', 'admin-site-enhancements' ),
			),
		);

		$labels = array();
		foreach ( $label_defaults as $settings_key => $label_config ) {
			$value = isset( $options[ $label_config['option_key'] ] ) ? $options[ $label_config['option_key'] ] : '';
			$labels[ $settings_key ] = ! empty( $value ) ? $value : $label_config['default'];
		}

		$submit_label = isset( $options['contact_form_submit_button_label'] ) ? $options['contact_form_submit_button_label'] : '';
		if ( empty( $submit_label ) ) {
			$submit_label = __( 'Send Message', 'admin-site-enhancements' );
		}

		$success_message = isset( $options['contact_form_success_message'] ) ? $options['contact_form_success_message'] : '';
		if ( empty( $success_message ) ) {
			$success_message = __( 'Thank you. Your message has been sent.', 'admin-site-enhancements' );
		}

		$error_message = isset( $options['contact_form_error_message'] ) ? $options['contact_form_error_message'] : '';
		if ( empty( $error_message ) ) {
			$error_message = __( 'Unable to send your message. Please try again later.', 'admin-site-enhancements' );
		}

		$notification_email = isset( $options['contact_form_notification_email'] ) ? $options['contact_form_notification_email'] : '';
		$notification_emails = self::parse_notification_emails( $notification_email );

		if ( empty( $notification_emails ) ) {
			$notification_email = get_option( 'admin_email' );
		} else {
			$notification_email = implode( ', ', $notification_emails );
		}

		$layout               = isset( $options['contact_form_layout'] ) ? $options['contact_form_layout'] : 'default';
		$layout               = in_array( $layout, array( 'default', 'stacked' ), true ) ? $layout : 'default';
		$label_position       = isset( $options['contact_form_label_position'] ) ? $options['contact_form_label_position'] : 'left';
		$label_position       = in_array( $label_position, array( 'left', 'right' ), true ) ? $label_position : 'left';
		$field_style          = isset( $options['contact_form_field_style'] ) ? $options['contact_form_field_style'] : 'box';
		$field_style          = in_array( $field_style, array( 'box', 'underline' ), true ) ? $field_style : 'box';
		$corner_style         = isset( $options['contact_form_corner_style'] ) ? $options['contact_form_corner_style'] : 'rounded';
		$corner_style         = in_array( $corner_style, array( 'rounded', 'sharp' ), true ) ? $corner_style : 'rounded';
		$color_scheme         = isset( $options['contact_form_color_scheme'] ) ? $options['contact_form_color_scheme'] : 'dark';
		$color_scheme         = in_array( $color_scheme, array( 'dark', 'light' ), true ) ? $color_scheme : 'dark';
		$button_position      = isset( $options['contact_form_button_position'] ) ? $options['contact_form_button_position'] : 'left';
		$button_position      = in_array( $button_position, array( 'left', 'right' ), true ) ? $button_position : 'left';
		$submit_button_style  = isset( $options['contact_form_submit_button_style'] ) ? $options['contact_form_submit_button_style'] : 'solid';
		$submit_button_style  = in_array( $submit_button_style, array( 'solid', 'outline' ), true ) ? $submit_button_style : 'solid';
		$submit_button_color  = isset( $options['contact_form_submit_button_color'] ) ? sanitize_hex_color( $options['contact_form_submit_button_color'] ) : '';
		if ( null === $submit_button_color ) {
			$submit_button_color = '';
		}

		return array_merge(
			$labels,
			array(
				'submit_button_label'  => $submit_label,
				'success_message'      => $success_message,
				'error_message'        => $error_message,
				'notification_email'   => $notification_email,
				'disable_antispam'     => ! empty( $options['contact_form_disable_antispam'] ),
				'use_theme_styles'     => ! empty( $options['contact_form_use_theme_styles'] ),
				'layout'               => $layout,
				'label_position'       => $label_position,
				'field_style'          => $field_style,
				'corner_style'         => $corner_style,
				'color_scheme'         => $color_scheme,
				'button_position'      => $button_position,
				'submit_button_style'  => $submit_button_style,
				'submit_button_color'  => $submit_button_color,
			)
		);
	}

	/**
	 * Parse a comma-separated notification email string into valid addresses.
	 *
	 * @param string $value Raw email string, possibly comma-separated.
	 * @return array<int, string>
	 */
	public static function parse_notification_emails( $value ) {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return array();
		}

		$parts  = explode( ',', $value );
		$emails = array();

		foreach ( $parts as $part ) {
			$email = sanitize_email( trim( $part ) );

			if ( '' === $email || ! is_email( $email ) ) {
				continue;
			}

			if ( ! in_array( $email, $emails, true ) ) {
				$emails[] = $email;
			}
		}

		return $emails;
	}

	/**
	 * Enqueue frontend assets and localize script data.
	 *
	 * @return void
	 */
	public static function enqueue_frontend_assets() {
		// Ensure registration before localize (idempotent; covers unusual load order).
		self::register_frontend_assets();

		$settings = self::get_settings();

		wp_enqueue_style( 'asenha-contact-form-layout' );

		if ( empty( $settings['use_theme_styles'] ) ) {
			wp_enqueue_style( 'asenha-contact-form-default' );
		}

		$effective_btn_color = ! empty( $settings['submit_button_color'] ) ? $settings['submit_button_color'] : ( 'light' === $settings['color_scheme'] ? '#ffffff' : '#1e1e1e' );
		$common              = new Common_Methods();
		$is_dark             = $common->is_color_dark( $effective_btn_color );
		$btn_text_color      = $is_dark ? '#ffffff' : '#1e1e1e';
		$btn_hover_color     = $is_dark ? $common->adjust_bnrightness( $effective_btn_color, 0.15 ) : $common->adjust_bnrightness( $effective_btn_color, -0.15 );
		$btn_hover_text      = $common->is_color_dark( $btn_hover_color ) ? '#ffffff' : '#1e1e1e';
		$inline_css          = sprintf(
			'.asenha-contact-form{--asenha-cf-btn-color:%1$s;--asenha-cf-btn-text:%2$s;--asenha-cf-btn-hover:%3$s;--asenha-cf-btn-hover-text:%4$s;}',
			esc_html( $effective_btn_color ),
			esc_html( $btn_text_color ),
			esc_html( $btn_hover_color ),
			esc_html( $btn_hover_text )
		);
		if ( ! empty( $settings['use_theme_styles'] ) ) {
			wp_add_inline_style( 'asenha-contact-form-layout', $inline_css );
		} else {
			wp_add_inline_style( 'asenha-contact-form-default', $inline_css );
		}

		wp_enqueue_script( 'asenha-contact-form-frontend' );

		wp_localize_script(
			'asenha-contact-form-frontend',
			'asenhaContactForm',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'sending'       => __( 'Sending...', 'admin-site-enhancements' ),
					'success'       => $settings['success_message'],
					'generic_error' => $settings['error_message'],
					'subject'       => __( 'Please enter a subject.', 'admin-site-enhancements' ),
					'message'       => __( 'Please enter a message.', 'admin-site-enhancements' ),
					'name'          => __( 'Please enter your name.', 'admin-site-enhancements' ),
					'email'         => __( 'Please enter a valid email address.', 'admin-site-enhancements' ),
				),
			)
		);
	}
}
