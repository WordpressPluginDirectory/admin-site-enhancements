<?php

namespace ASENHA\Classes;

use WP_Error;
use ASENHA\EmailDelivery\Email_Log_Table;
/**
 * Class for Email Delivery module
 *
 * @since 6.9.5
 */
class Email_Delivery {
    private $log_entry_id;

    /**
     * Send emails using external SMTP service
     *
     * @since 4.6.0
     */
    public function deliver_email_via_smtp( $phpmailer ) {
        $options = get_option( ASENHA_SLUG_U, array() );
        $smtp_host = $options['smtp_host'];
        $smtp_port = $options['smtp_port'];
        $smtp_security = $options['smtp_security'];
        $smtp_username = $options['smtp_username'];
        $smtp_password = $options['smtp_password'];
        $smtp_default_from_name = $options['smtp_default_from_name'];
        $smtp_default_from_email = $options['smtp_default_from_email'];
        $smtp_force_from = $options['smtp_force_from'];
        $smtp_bypass_ssl_verification = $options['smtp_bypass_ssl_verification'];
        $smtp_debug = $options['smtp_debug'];
        // Do nothing if host or password is empty
        // if ( empty( $smtp_host ) || empty( $smtp_password ) ) {
        //  return;
        // }
        // Maybe override FROM email and/or name if the sender is "WordPress <wordpress@sitedomain.com>", the default from WordPress core and not yet overridden by another plugin.
        $from_name = $phpmailer->FromName;
        $from_email_beginning = substr( $phpmailer->From, 0, 9 );
        // Get the first 9 characters of the current FROM email
        if ( $smtp_force_from ) {
            $phpmailer->FromName = $smtp_default_from_name;
            $phpmailer->From = $smtp_default_from_email;
        } else {
            if ( 'WordPress' === $from_name && !empty( $smtp_default_from_name ) ) {
                $phpmailer->FromName = $smtp_default_from_name;
            }
            if ( 'wordpress' === $from_email_beginning && !empty( $smtp_default_from_email ) ) {
                $phpmailer->From = $smtp_default_from_email;
            }
        }
        // Only attempt to send via SMTP if all the required info is present. Otherwise, use default PHP Mailer settings as set by wp_mail()
        if ( !empty( $smtp_host ) && !empty( $smtp_port ) && !empty( $smtp_security ) && !empty( $smtp_username ) && !empty( $smtp_password ) ) {
            // Send using SMTP
            $phpmailer->isSMTP();
            // phpcs:ignore
            // Enanble SMTP authentication
            $phpmailer->SMTPAuth = true;
            // phpcs:ignore
            // Set some other defaults
            // $phpmailer->CharSet  = 'utf-8'; // phpcs:ignore
            $phpmailer->XMailer = 'Admin and Site Enhancements v' . ASENHA_VERSION . ' - a WordPress plugin';
            // phpcs:ignore
            $phpmailer->Host = $smtp_host;
            // phpcs:ignore
            $phpmailer->Port = $smtp_port;
            // phpcs:ignore
            $phpmailer->SMTPSecure = $smtp_security;
            // phpcs:ignore
            $phpmailer->Username = trim( $smtp_username );
            // phpcs:ignore
            $phpmailer->Password = trim( $smtp_password );
            // phpcs:ignore
        }
        // If verification of SSL certificate is bypassed
        // Reference: https://www.php.net/manual/en/context.ssl.php & https://stackoverflow.com/a/30803024
        if ( $smtp_bypass_ssl_verification ) {
            $phpmailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }
        // If debug mode is enabled, send debug info (SMTP::DEBUG_CONNECTION) to WordPress debug.log file set in wp-config.php
        // Reference: https://github.com/PHPMailer/PHPMailer/wiki/SMTP-Debugging
        if ( $smtp_debug ) {
            $phpmailer->SMTPDebug = 4;
            //phpcs:ignore
            $phpmailer->Debugoutput = 'error_log';
            //phpcs:ignore
        }
    }

    /**
     * Send a test email and use SMTP host if defined in settings
     * 
     * @since 5.3.0
     */
    public function send_test_email() {
        if ( isset( $_REQUEST ) ) {
            $content = array(
                array(
                    'title' => 'Hey... are you getting this?',
                    'body'  => '<p><strong>Looks like you did!</strong></p>',
                ),
                array(
                    'title' => 'There\'s a message for you...',
                    'body'  => '<p><strong>Here it is:</strong></p>',
                ),
                array(
                    'title' => 'Is it working?',
                    'body'  => '<p><strong>Yes, it\'s working!</strong></p>',
                ),
                array(
                    'title' => 'Hope you\'re getting this...',
                    'body'  => '<p><strong>Looks like this was sent out just fine and you got it.</strong></p>',
                ),
                array(
                    'title' => 'Testing delivery configuration...',
                    'body'  => '<p><strong>Everything looks good!</strong></p>',
                ),
                array(
                    'title' => 'Testing email delivery',
                    'body'  => '<p><strong>Looks good!</strong></p>',
                ),
                array(
                    'title' => 'Config is looking good',
                    'body'  => '<p><strong>Seems like everything has been set up properly!</strong></p>',
                ),
                array(
                    'title' => 'All set up',
                    'body'  => '<p><strong>Your configuration is working properly.</strong></p>',
                ),
                array(
                    'title' => 'Good to go',
                    'body'  => '<p><strong>Config is working great.</strong></p>',
                ),
                array(
                    'title' => 'Good job',
                    'body'  => '<p><strong>Everything is set.</strong></p>',
                )
            );
            $random_number = rand( 0, count( $content ) - 1 );
            $to = $_REQUEST['email_to'];
            $title = $content[$random_number]['title'];
            $body = $content[$random_number]['body'] . '<p>This message was sent from <a href="' . get_bloginfo( 'url' ) . '">' . get_bloginfo( 'url' ) . '</a> on ' . wp_date( 'F j, Y' ) . ' at ' . wp_date( 'H:i:s' ) . ' via ASE.</p>';
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $success = wp_mail(
                $to,
                $title,
                $body,
                $headers
            );
            if ( $success ) {
                $response = array(
                    'status' => 'success',
                );
            } else {
                $response = array(
                    'status' => 'failed',
                );
            }
            echo json_encode( $response );
        }
    }

    /**
     * Perform additional logging for data that are not properly logged via wp_mail hook
     * 
     * @since 7.1.0
     */
    public function additional_logging__premium_onlly( $phpmailer ) {
        // Set custom Message-ID header, e.g. // e.g. <message-3@subdomain.domain.com>>
        // The number here is the ID of the message in the email delivery log table
        $message_id = '<message-' . $this->log_entry_id . '@' . parse_url( get_site_url(), PHP_URL_HOST ) . '>';
        $phpmailer->MessageID = $message_id;
        // Get headers
        // Reference: https://plugins.trac.wordpress.org/browser/mailarchiver/tags/4.0.0/includes/listeners/class-corelistener.php#L216
        if ( method_exists( $phpmailer, 'createHeader' ) ) {
            $headers = $phpmailer->createHeader();
            if ( !is_array( $headers ) ) {
                $headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
            }
            // Remove empty elements and get sender info (name and email)
            $sender = '';
            $headers_array = array();
            $sender = '';
            $reply_to = '';
            if ( !empty( $headers ) ) {
                foreach ( $headers as $header ) {
                    if ( '' !== $header ) {
                        $headers_array[] = $header;
                    }
                    if ( false !== strpos( $header, 'From:' ) ) {
                        $sender_array = explode( ': ', $header );
                        $sender = ( isset( $sender_array[1] ) ? $sender_array[1] : '' );
                    }
                    if ( false !== strpos( $header, 'Reply-To:' ) ) {
                        $reply_to_array = explode( ': ', $header );
                        $reply_to = ( isset( $reply_to_array[1] ) ? $reply_to_array[1] : '' );
                    }
                    if ( false !== strpos( $header, 'Content-Type:' ) ) {
                        $content_type_array = explode( ': ', $header );
                        $content_type_maybe_with_charset = ( isset( $content_type_array[1] ) ? $content_type_array[1] : '' );
                        // e.g. text/html; charset=UTF-8
                        if ( false !== strpos( $content_type_maybe_with_charset, 'charset' ) ) {
                            $content_type_array = explode( '; ', $content_type_maybe_with_charset );
                            $content_type = ( isset( $content_type_array[0] ) ? $content_type_array[0] : '' );
                        } else {
                            $content_type = trim( $content_type_maybe_with_charset );
                        }
                    }
                }
            }
            // Add sender and headers info to the existing log entry for the mail
            // https://developer.wordpress.org/reference/classes/wpdb/update/
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'asenha_email_delivery',
                // Log table name
                array(
                    'sender'       => sanitize_text_field( str_replace( array('<', '>'), array('(', ')'), $sender ) ),
                    'reply_to'     => sanitize_text_field( str_replace( array('<', '>'), array('(', ')'), $reply_to ) ),
                    'content_type' => sanitize_text_field( $content_type ),
                    'headers'      => serialize( $headers_array ),
                ),
                array(
                    'id' => $this->log_entry_id,
                ),
                array(
                    '%s',
                    // string - sender
                    '%s',
                ),
                array('%d')
            );
        }
    }

    /**
     * Trigger scheduling of email delivery log clean up event
     * 
     * @since 7.1.1
     */
    public function trigger_clear_or_schedule_log_clean_up_by_amount( $option_name ) {
        if ( 'smtp_email_log_schedule_cleanup_by_amount' == $option_name ) {
            $this->clear_or_schedule_log_clean_up_by_amount();
        }
    }

    /**
     * Schedule email delivery log clean up event
     * 
     * @link https://plugins.trac.wordpress.org/browser/lana-email-logger/tags/1.1.0/lana-email-logger.php#L750
     * @since 7.1.1
     */
    public function clear_or_schedule_log_clean_up_by_amount() {
        $options = get_option( ASENHA_SLUG_U, array() );
        $smtp_email_log_schedule_cleanup_by_amount = ( isset( $options['smtp_email_log_schedule_cleanup_by_amount'] ) ? $options['smtp_email_log_schedule_cleanup_by_amount'] : false );
        // If scheduled clean up is not enabled, let's clear the schedule
        if ( !$smtp_email_log_schedule_cleanup_by_amount ) {
            wp_clear_scheduled_hook( 'asenha_email_log_cleanup_by_amount' );
            return;
        }
        // If there's no next scheduled clean up event, let's schedule one
        if ( !wp_next_scheduled( 'asenha_email_log_cleanup_by_amount' ) ) {
            wp_schedule_event( time(), 'hourly', 'asenha_email_log_cleanup_by_amount' );
        }
    }

    /**
     * Perform clean up of email delivery log by the amount of entries to keep
     * 
     * @link https://plugins.trac.wordpress.org/browser/lana-email-logger/tags/1.1.0/lana-email-logger.php#L768
     * @since 7.1.1
     */
    public function perform_email_log_clean_up_by_amount() {
        global $wpdb;
        $options = get_option( ASENHA_SLUG_U, array() );
        $smtp_email_log_schedule_cleanup_by_amount = ( isset( $options['smtp_email_log_schedule_cleanup_by_amount'] ) ? $options['smtp_email_log_schedule_cleanup_by_amount'] : false );
        $smtp_email_log_entries_amount_to_keep = ( isset( $options['smtp_email_log_entries_amount_to_keep'] ) ? $options['smtp_email_log_entries_amount_to_keep'] : 1000 );
        // Bail if scheduled clean up by amount is not enabled
        if ( !$smtp_email_log_schedule_cleanup_by_amount ) {
            return;
        }
        $table_name = $wpdb->prefix . 'asenha_email_delivery';
        $wpdb->query( "DELETE email_log_entries FROM " . $table_name . " \n                        AS email_log_entries JOIN ( SELECT id FROM " . $table_name . " ORDER BY id DESC LIMIT 1 OFFSET " . $smtp_email_log_entries_amount_to_keep . " ) \n                        AS email_log_entries_limit ON email_log_entries.id <= email_log_entries_limit.id;" );
    }

}
