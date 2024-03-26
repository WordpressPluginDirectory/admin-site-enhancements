<?php

namespace ASENHA\Classes;

/**
 * Class for External Permalinks module
 *
 * @since 6.9.5
 */
class External_Permalinks {
    
    /**
     * Add external permalink meta box for enabled post types
     * 
     * @since 3.9.0
     */
    public function add_external_permalink_meta_box( $post_type, $post ) {

        $options = get_option( ASENHA_SLUG_U, array() );
        $enable_external_permalinks_for = $options['enable_external_permalinks_for'];

        foreach ( $enable_external_permalinks_for as $post_type_slug => $is_external_permalink_enabled ) {
            if ( ( get_post_type() == $post_type_slug ) && $is_external_permalink_enabled ) {

                // Skip adding meta box for post types where Gutenberg is enabled
                // if ( 
                //  function_exists( 'use_block_editor_for_post_type' ) 
                //  && use_block_editor_for_post_type( $post_type_slug ) 
                // ) {
                //  continue; // go to the beginning of next iteration
                // }

                add_meta_box(
                    'asenha-external-permalink', // ID of meta box
                    'External Permalink', // Title of meta box
                    [ $this, 'output_external_permalink_meta_box' ], // Callback function
                    $post_type_slug, // The screen on which the meta box should be output to
                    'normal', // context
                    'high' // priority
                    // array(), // $args to pass to callback function. Ref: https://developer.wordpress.org/reference/functions/add_meta_box/#comment-342
                );

            }
        }

    }

    /**
     * Render External Permalink meta box
     *
     * @since 3.9.0
     */
    public function output_external_permalink_meta_box( $post ) {
        ?>
        <div class="external-permalink-input">
            <input name="<?php echo esc_attr( 'external_permalink' ); ?>" class="large-text" id="<?php echo esc_attr( 'external_permalink' ); ?>" type="text" value="<?php echo esc_url( get_post_meta( $post->ID, '_links_to', true ) ); ?>" placeholder="https://" />
            <div class="external-permalink-input-description">Keep empty to use the default WordPress permalink. External permalink will open in a new browser tab.</div>
            <?php wp_nonce_field( 'external_permalink_' . $post->ID, 'external_permalink_nonce', false, true ); ?>
        </div>
        <?php
    }

    /**
     * Save external permalink input
     *
     * @since 3.9.0
     */
    public function save_external_permalink( $post_id ) {

        // Only proceed if nonce is verified
        if ( isset( $_POST['external_permalink_nonce'] ) && wp_verify_nonce( $_POST['external_permalink_nonce'], 'external_permalink_' . $post_id ) ) {

            // Get the value of external permalink from input field
            $external_permalink = isset( $_POST['external_permalink'] ) ? esc_url_raw( trim( $_POST['external_permalink'] ) ) : '';

            // Update or delete external permalink post meta
            if ( ! empty( $external_permalink ) ) {
                update_post_meta( $post_id, '_links_to', $external_permalink );
            } else {
                delete_post_meta( $post_id, '_links_to' );
            }

        }

    }

    /**
     * Change WordPress default permalink into external permalink for pages
     *
     * @since 3.9.0
     */
    public function use_external_permalink_for_pages( $permalink, $post_id ) {

        $external_permalink = get_post_meta( $post_id, '_links_to', true );

        if ( ! empty( $external_permalink ) ) {
            $permalink = $external_permalink;
        }

        return $permalink;

    }

    /**
     * Change WordPress default permalink into external permalink for posts and custom post types
     *
     * @since 3.9.0
     */
    public function use_external_permalink_for_posts( $permalink, $post ) {

        $external_permalink = get_post_meta( $post->ID, '_links_to', true );

        if ( ! empty( $external_permalink ) ) {
            $permalink = $external_permalink;

            if ( ! is_admin() ) { 
                $permalink = $permalink . '#new_tab';
            }
        }

        return $permalink;

    }

    /** 
     * Redirect page/post to external permalink if it's loaded directly from the WP default permalink
     *
     * @since 3.9.0
     */
    public function redirect_to_external_permalink() {

        global $post;

        // If not on/loading the single page/post URL, do nothing
        if ( ! is_singular() ) {
            return;
        }

        $external_permalink = get_post_meta( $post->ID, '_links_to', true );

        if ( ! empty( $external_permalink ) ) {
            wp_redirect( $external_permalink, 302 ); // temporary redirect
            exit;
        }

    }
    
}