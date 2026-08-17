<?php
/**
 * Contact Form Gutenberg block.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Contact Form block.
 */
class Contact_Form_Block {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Register the dynamic block.
	 *
	 * @return void
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		Contact_Form::register_frontend_assets();

		wp_register_script(
			'asenha-contact-form-block-editor',
			CONTACT_FORM_URL . 'assets/js/contact-form-block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-server-side-render' ),
			CONTACT_FORM_VERSION,
			true
		);

		$settings = Contact_Form::get_settings();

		$editor_styles = array( 'asenha-contact-form-layout' );
		if ( empty( $settings['use_theme_styles'] ) ) {
			$editor_styles[] = 'asenha-contact-form-default';
		}

		register_block_type(
			'asenha/contact-form',
			array(
				'api_version'     => 3,
				'title'           => __( 'Contact Form', 'admin-site-enhancements' ),
				'description'     => __( 'Insert the ASE contact form.', 'admin-site-enhancements' ),
				'category'        => 'widgets',
				'icon'            => 'email',
				'editor_style'    => $editor_styles,
				'editor_script'   => 'asenha-contact-form-block-editor',
				'render_callback' => array( $this, 'render_block' ),
				'attributes'      => array(),
			)
		);
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script( 'asenha-contact-form-block-editor' );

		$settings = Contact_Form::get_settings();
		Contact_Form::register_frontend_assets();

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
		$inline_handle = empty( $settings['use_theme_styles'] ) ? 'asenha-contact-form-default' : 'asenha-contact-form-layout';
		wp_add_inline_style( $inline_handle, $inline_css );

		wp_localize_script(
			'asenha-contact-form-block-editor',
			'asenhaContactFormBlock',
			array(
				'i18n' => array(
					'title'       => __( 'Contact Form', 'admin-site-enhancements' ),
					'description' => __( 'Display the ASE contact form on this page.', 'admin-site-enhancements' ),
				),
			)
		);
	}

	/**
	 * Render the block on the frontend.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		Contact_Form::enqueue_frontend_assets();

		ob_start();
		Contact_Form_Render::render_form();

		return ob_get_clean();
	}
}
