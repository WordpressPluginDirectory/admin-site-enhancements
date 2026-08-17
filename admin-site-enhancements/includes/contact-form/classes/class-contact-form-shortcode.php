<?php
/**
 * Contact Form shortcode.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the contact form shortcode.
 */
class Contact_Form_Shortcode {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'asenha_contact_form', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Render the contact form shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		Contact_Form::enqueue_frontend_assets();

		ob_start();
		Contact_Form_Render::render_form();

		return ob_get_clean();
	}
}
