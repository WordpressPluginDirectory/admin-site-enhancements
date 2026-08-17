<?php
/**
 * Contact Form renderer.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the contact form markup.
 */
class Contact_Form_Render {

	/**
	 * Output the contact form HTML.
	 *
	 * @return void
	 */
	public static function render_form() {
		$settings = Contact_Form::get_settings();
		$tokens   = Contact_Form_Spam::generate_form_tokens();

		$template = CONTACT_FORM_PATH . 'templates/form.php';

		if ( file_exists( $template ) ) {
			include $template;
		}
	}
}
