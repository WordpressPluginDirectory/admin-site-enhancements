<?php
/**
 * Contact form template.
 *
 * @package Admin_Site_Enhancements
 *
 * @var array<string, string> $settings Module settings.
 * @var array<string, string> $tokens   Anti-spam tokens.
 */

defined( 'ABSPATH' ) || exit;

$form_classes = array( 'asenha-contact-form' );
$form_classes[] = 'asenha-cf-layout--' . ( isset( $settings['layout'] ) ? $settings['layout'] : 'default' );
$form_classes[] = 'asenha-cf-field-style--' . ( isset( $settings['field_style'] ) ? $settings['field_style'] : 'box' );
$form_classes[] = 'asenha-cf-corners--' . ( isset( $settings['corner_style'] ) ? $settings['corner_style'] : 'rounded' );
$form_classes[] = 'asenha-cf-scheme--' . ( isset( $settings['color_scheme'] ) ? $settings['color_scheme'] : 'dark' );
$form_classes[] = 'asenha-cf-button--' . ( isset( $settings['submit_button_style'] ) ? $settings['submit_button_style'] : 'solid' );
$form_classes[] = 'asenha-cf-button-pos--' . ( isset( $settings['button_position'] ) ? $settings['button_position'] : 'left' );
if ( isset( $settings['layout'] ) && 'stacked' === $settings['layout'] ) {
	$form_classes[] = 'asenha-cf-label-pos--' . ( isset( $settings['label_position'] ) ? $settings['label_position'] : 'left' );
}
?>
<form class="<?php echo esc_attr( implode( ' ', $form_classes ) ); ?>" method="post" novalidate data-js-token="<?php echo esc_attr( $tokens['js_token'] ); ?>">
	<div class="asenha-cf-messages" aria-live="polite"></div>

	<div class="asenha-cf-field asenha-cf-field--full">
		<label for="asenha-cf-subject"><?php echo esc_html( $settings['subject_label'] ); ?></label>
		<input type="text" id="asenha-cf-subject" name="asenha_cf_subject" required />
	</div>

	<div class="asenha-cf-field asenha-cf-field--full">
		<label for="asenha-cf-message"><?php echo esc_html( $settings['message_label'] ); ?></label>
		<textarea id="asenha-cf-message" name="asenha_cf_message" rows="6" required></textarea>
	</div>

	<div class="asenha-cf-row">
		<div class="asenha-cf-field asenha-cf-field--half">
			<label for="asenha-cf-name"><?php echo esc_html( $settings['name_label'] ); ?></label>
			<input type="text" id="asenha-cf-name" name="asenha_cf_name" required />
		</div>

		<div class="asenha-cf-field asenha-cf-field--half">
			<label for="asenha-cf-email"><?php echo esc_html( $settings['email_label'] ); ?></label>
			<input type="email" id="asenha-cf-email" name="asenha_cf_email" required />
		</div>
	</div>

	<div class="asenha-cf-field asenha-cf-field--full asenha-cf-submit-wrap">
		<button type="submit" class="asenha-cf-submit-button"><?php echo esc_html( $settings['submit_button_label'] ); ?></button>
		<span class="asenha-cf-submit-spinner" aria-hidden="true"></span>
	</div>

	<input type="hidden" name="asenha_cf_payload" value="<?php echo esc_attr( $tokens['payload'] ); ?>" />
	<input type="hidden" name="asenha_cf_signature" value="<?php echo esc_attr( $tokens['signature'] ); ?>" />
	<input type="hidden" name="asenha_cf_js" value="" />
	<input type="hidden" name="asenha_cf_nonce" value="<?php echo esc_attr( wp_create_nonce( 'asenha_contact_form_submit' ) ); ?>" />

	<div class="asenha-cf-honeypot" aria-hidden="true">
		<label for="<?php echo esc_attr( $tokens['honeypot_name'] ); ?>"><?php esc_html_e( 'Leave this field empty', 'admin-site-enhancements' ); ?></label>
		<input type="text" id="<?php echo esc_attr( $tokens['honeypot_name'] ); ?>" name="<?php echo esc_attr( $tokens['honeypot_name'] ); ?>" tabindex="-1" autocomplete="off" />
	</div>
</form>
