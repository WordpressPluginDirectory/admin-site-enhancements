<?php
/**
 * Contact Form module loader.
 *
 * @package Admin_Site_Enhancements
 */

defined( 'ABSPATH' ) || exit;

define( 'CONTACT_FORM_PATH', ASENHA_PATH . 'includes/contact-form/' );
define( 'CONTACT_FORM_URL', ASENHA_URL . 'includes/contact-form/' );
define( 'CONTACT_FORM_VERSION', ASENHA_VERSION );

require_once CONTACT_FORM_PATH . 'classes/class-contact-form-submission.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-spam.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-email.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-render.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-handler.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-submissions-table.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-admin.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-shortcode.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form-block.php';
require_once CONTACT_FORM_PATH . 'classes/class-contact-form.php';

new ASENHA\Classes\Contact_Form();
