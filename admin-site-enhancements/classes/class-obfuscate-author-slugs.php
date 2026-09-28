<?php

namespace ASENHA\Classes;

/**
 * Class for Obfuscate Author Slugs module
 *
 * @since 6.9.5
 */
class Obfuscate_Author_Slugs {

    /**
     * If an author name is queried, decrypt it. Used by pre_get_posts action.
     * 
     * @link https://plugins.trac.wordpress.org/browser/smart-user-slug-hider/tags/4.0.2/inc/class-smart-user-slug-hider.php
     * @since 2.1.0
     */
    function alter_author_query( $query ) {

        // Check if it's a query for author data, and that 'author_name' is not empty
        if ( $query->is_author() && $query->query_vars['author_name'] != '' ) {

            // Check for character(s) representing a hexadecimal digit
            if ( ctype_xdigit( $query->query_vars['author_name'] ) ) {

            // Get user by the decrypted user ID
            $decrypted_user_id = $this->decrypt( $query->query_vars['author_name'] );
            $user = ( false !== $decrypted_user_id ) ? get_user_by( 'id', $decrypted_user_id ) : false;

                if ( $user ) {

                    $query->set( 'author_name', $user->user_nicename );

                } else {

                    // No user found
                    $query->is_404 = true;
                    $query->is_author = false;
                    $query->is_archive = false;

                }

            } else {

                // No hexadecimal digit detected in URL, i.e. someone is trying to access URL with original author slug
                $query->is_404 = true;
                $query->is_author = false;
                $query->is_archive = false;

            }

        }
        
        return;
    }

    /**
     * Replace author slug in author link to encrypted value. Used by author_link filter.
     * 
     * @link https://plugins.trac.wordpress.org/browser/smart-user-slug-hider/tags/4.0.2/inc/class-smart-user-slug-hider.php
     * @since 2.1.0
     */
    function alter_author_link( $link, $user_id, $author_slug ) {

        $encrypted_author_slug = $this->encrypt( $user_id );

        return str_replace ( '/' . $author_slug, '/' . $encrypted_author_slug, $link );

    }

    /**
     * Replace author slug in REST API /users/ endpoint to encrypted value. Used by rest_prepare_user filter.
     *
     * @link https://plugins.trac.wordpress.org/browser/smart-user-slug-hider/tags/4.0.2/inc/class-smart-user-slug-hider.php
     * @since 2.1.0
     */
    function alter_json_users($response, $user, $request) {

        $data = $response->get_data();
        $data['slug'] = $this->encrypt($data['id']);
        $response->set_data($data);

        return $response;

    }

    /**
     * Helper function to return an encrypted user ID, which will then be used to replace the author slug.
     * 
     * @link https://plugins.trac.wordpress.org/browser/smart-user-slug-hider/trunk/inc/class-smart-user-slug-hider.php
     * @since 2.1.0
     */
    private function encrypt( $user_id ) {

        // Returns encrypted author slug from user ID, e.g. encrypt user ID 3 to a 32-char hexadecimal slug.
        // Deterministic by design (ECB, single block) so author URLs stay stable across requests.
        return bin2hex( openssl_encrypt( base_convert( $user_id, 10, 36 ), 'aes-256-ecb', self::encryption_key(), OPENSSL_RAW_DATA ) );
    }

    /**
     * Encryption key derived from site salts (never from public data).
     * 
     * @since 9.1.2
     * @return string 32-byte binary key for AES-256.
     */
    private static function encryption_key() {

        return hash( 'sha256', 'asenha_author_slug|' . wp_salt( 'auth' ), true );
    }

    
    /**
     * Helper function to decrypt an (encrypted) author slug and returns the user ID
     * 
     * @link https://plugins.trac.wordpress.org/browser/smart-user-slug-hider/trunk/inc/class-smart-user-slug-hider.php
     * @since 2.1.0
     */
    private function decrypt( $encrypted_author_slug ) {

        // Returns user ID, e.g. decrypts a 32-char hexadecimal author slug into user ID 3
        $user_id = $this->decrypt_current( $encrypted_author_slug );

        if ( false === $user_id ) {
            $user_id = $this->decrypt_legacy( $encrypted_author_slug );
        }

        return $user_id; // false when undecryptable; caller 404s via get_user_by()

    }

    /**
     * Decrypt an author slug produced by the current AES-256 scheme.
     * 
     * @since 9.1.2
     * @param string $encrypted_author_slug Hexadecimal encrypted author slug.
     * @return string|false User ID as numeric string, false on failure.
     */
    private function decrypt_current( $encrypted_author_slug ) {

        $decrypted = openssl_decrypt( pack( 'H*', $encrypted_author_slug ), 'aes-256-ecb', self::encryption_key(), OPENSSL_RAW_DATA );

        return $this->validated_user_id( $decrypted );
    }

    /**
     * Decrypt an author slug produced by the pre-9.1.2 3DES/md5(ASENHA_URL) scheme.
     * 
     * Deprecated: kept only so previously published obfuscated URLs keep working.
     * The legacy key is public knowledge; do not use for new slugs.
     * 
     * @since 9.1.2
     * @param string $encrypted_author_slug Hexadecimal encrypted author slug.
     * @return string|false User ID as numeric string, false on failure.
     */
    private function decrypt_legacy( $encrypted_author_slug ) {

        $decrypted = openssl_decrypt( pack( 'H*', $encrypted_author_slug ), 'DES-EDE3', md5( ASENHA_URL ), OPENSSL_RAW_DATA );

        return $this->validated_user_id( $decrypted );
    }

    /**
     * Base36-decode and validate a decrypted user ID.
     * 
     * @since 9.1.2
     * @param string|false $decrypted Raw openssl_decrypt() output.
     * @return string|false User ID as numeric string, false on failure.
     */
    private function validated_user_id( $decrypted ) {

        if ( false === $decrypted || '' === $decrypted ) {
            return false;
        }

        $user_id = base_convert( $decrypted, 36, 10 );

        return ( ctype_digit( $user_id ) && (int) $user_id > 0 ) ? $user_id : false;
    }

}