<?php

namespace ASENHA\Classes;

/**
 * Class for Email Address Obfuscator module
 *
 * @since 6.9.5
 */
class Email_Address_Obfuscator {
    /**
     * Obfuscate email address on the frontend using antispambot() native WP function
     * 
     * @link: https://gist.github.com/eclarrrk/349360b52e8822b69cb6fc499722520f
     * @since 5.5.0
     */
    public function obfuscate_string( $atts ) {
        $atts = shortcode_atts( array(
            'email'   => '',
            'subject' => '',
            'display' => 'newline',
            'link'    => 'no',
            'class'   => '',
        ), $atts );
        $email = $atts['email'];
        if ( !is_email( $email ) ) {
            return;
        }
        // Reverse email address characters if not in Firefox, which has bug related to unicode-bidi CSS property
        $http_user_agent = ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : 'generic' );
        if ( false !== stripos( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ), 'firefox' ) ) {
            // Do nothing. Do not reverse characters.
            $email_reversed = $email;
            $email_rev_parts = explode( '@', $email_reversed );
            $email_rev_parts = array($email_rev_parts[0], $email_rev_parts[1]);
            $css_bidi_styles = '';
        } else {
            $email_reversed = strrev( $email );
            $email_rev_parts = explode( '@', $email_reversed );
            $css_bidi_styles = 'unicode-bidi:bidi-override;';
        }
        $display = $atts['display'];
        if ( 'newline' == $display ) {
            $display_css = 'display:flex;justify-content:flex-end;';
        } elseif ( 'inline' == $display ) {
            $display_css = 'display:inline;';
        }
        $subject = $atts['subject'];
        if ( !empty( $subject ) ) {
            $subject = '?subject=' . $subject;
        }
        $link = $atts['link'];
        $class = $atts['class'];
        return '<span style="' . esc_attr( $display_css ) . esc_attr( $css_bidi_styles ) . ';direction:rtl;" class="' . esc_attr( $class ) . '">' . esc_html( $email_rev_parts[0] ) . '<span style="display:none;">obfsctd</span>&#64;' . esc_html( $email_rev_parts[1] ) . '</span>';
    }

}
