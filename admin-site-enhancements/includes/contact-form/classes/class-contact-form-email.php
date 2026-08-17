<?php
/**
 * Contact Form email notifications.
 *
 * @package Admin_Site_Enhancements
 */

namespace ASENHA\Classes;

defined( 'ABSPATH' ) || exit;

/**
 * Sends contact form notification emails.
 */
class Contact_Form_Email {

	/**
	 * Send a notification email for a new submission.
	 *
	 * @param array<string, string> $submission Submission data.
	 * @return bool
	 */
	public static function send_notification( $submission ) {
		$settings = Contact_Form::get_settings();
		$emails   = Contact_Form::parse_notification_emails( $settings['notification_email'] );

		if ( empty( $emails ) ) {
			return false;
		}

		$to = implode( ', ', $emails );

		$subject = sprintf(
			/* translators: %s: contact form subject */
			__( 'New contact form submission: %s', 'admin-site-enhancements' ),
			$submission['subject']
		);

		$body = self::build_notification_body_html( $submission );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		if ( ! empty( $submission['email'] ) && is_email( $submission['email'] ) ) {
			$reply_name = ! empty( $submission['name'] ) ? $submission['name'] : $submission['email'];
			$headers[]  = 'Reply-To: ' . sprintf( '%s <%s>', $reply_name, $submission['email'] );
		}

		return (bool) wp_mail( $to, $subject, $body, $headers );
	}

	/**
	 * Build the HTML notification email body.
	 *
	 * @param array<string, string> $submission Submission data.
	 * @return string
	 */
	private static function build_notification_body_html( $submission ) {
		$date = Contact_Form_Submission::format_submission_date( $submission['created_at'] ?? '' );

		$body  = '<p>' . esc_html__( 'You have received a new contact form submission.', 'admin-site-enhancements' ) . '</p>';
		$body .= '<p>' . self::format_label( __( 'Subject', 'admin-site-enhancements' ) ) . '<br />' . esc_html( $submission['subject'] ) . '</p>';
		$body .= '<p>' . self::format_label( __( 'Message', 'admin-site-enhancements' ) ) . '<br />' . nl2br( esc_html( $submission['message'] ) ) . '</p>';
		$body .= '<p>' . self::format_label( __( 'Name', 'admin-site-enhancements' ) ) . '<br />' . esc_html( $submission['name'] ) . '</p>';
		$body .= '<p>' . self::format_label( __( 'Email', 'admin-site-enhancements' ) ) . '<br />' . esc_html( $submission['email'] ) . '</p>';
		$body .= '<p>' . self::format_label( __( 'Date', 'admin-site-enhancements' ) ) . '<br />' . esc_html( $date ) . '</p>';

		if ( ! empty( $submission['sent_from'] ) ) {
			$body .= '<p>' . self::format_label( __( 'Sent from', 'admin-site-enhancements' ) ) . '<br />'
				. '<a href="' . esc_url( $submission['sent_from'] ) . '">' . esc_html( $submission['sent_from'] ) . '</a></p>';
		}

		return $body;
	}

	/**
	 * Wrap a field label in bold markup for HTML emails.
	 *
	 * @param string $label Field label.
	 * @return string
	 */
	private static function format_label( $label ) {
		return '<strong>' . esc_html( $label ) . ':</strong>';
	}
}
