<?php

namespace ASENHA\Classes;

/**
 * Class related to rendering of settings fields on the admin page
 *
 * @since 2.2.0
 */
class Settings_Fields_Render {
    /**
     * Render checkbox field as a toggle/switcher
     *
     * @since 1.0.0
     */
    function render_checkbox_toggle( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_name = $args['field_name'];
        $field_description = $args['field_description'];
        $field_option_value = ( array_key_exists( $args['field_id'], $options ) ? $options[$args['field_id']] : false );
        echo '<input type="checkbox" id="' . esc_attr( $field_name ) . '" class="asenha-field-checkbox" name="' . esc_attr( $field_name ) . '" ' . checked( $field_option_value, true, false ) . '>';
        echo '<label for="' . esc_attr( $field_name ) . '"></label>';
        // For field with additional options / sub-fields, we add a wrapper to enclose field descriptions
        if ( array_key_exists( 'field_options_wrapper', $args ) && $args['field_options_wrapper'] ) {
            // For when the options / sub-fields occupy lengthy vertical space, we add show all / less toggler
            if ( array_key_exists( 'field_options_moreless', $args ) && $args['field_options_moreless'] ) {
                echo '<div class="asenha-field-with-options field-show-more">';
                echo '<a id="' . esc_attr( $args['field_slug'] ) . '-show-moreless" class="show-more-less show-more" href="#">' . __( 'Expand', 'admin-site-enhancements' ) . ' &#9660;</a>';
                echo '<div class="asenha-field-options-wrapper wrapper-show-more">';
            } else {
                echo '<div class="asenha-field-with-options">';
                echo '<div class="asenha-field-options-wrapper">';
            }
        }
        echo '<div class="asenha-field-description">' . wp_kses_post( $field_description ) . '</div>';
        // For field with additional options / sub-fields, we add wrapper for them
        if ( array_key_exists( 'field_options_wrapper', $args ) && $args['field_options_wrapper'] ) {
            echo '<div class="asenha-subfields" style="display:none"></div>';
        }
        // For field with additional options / sub-fields, we add a wrapper to enclose field descriptions
        if ( array_key_exists( 'field_options_wrapper', $args ) && $args['field_options_wrapper'] ) {
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Render checkbox field as sub-field of a toggle/switcher checkbox
     *
     * @since 1.9.0
     */
    function render_checkbox_plain( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_name = $args['field_name'];
        $field_label = $args['field_label'];
        $default_value = false;
        switch ( $args['field_id'] ) {
            case 'login_page_disable_registration':
                $default_value = ( 1 == get_option( 'users_can_register' ) ? false : true );
                break;
        }
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : $default_value );
        echo '<input type="checkbox" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-checkbox" name="' . esc_attr( $field_name ) . '" ' . checked( $field_option_value, true, false ) . '>';
        echo '<label for="' . esc_attr( $field_name ) . '" class="asenha-subfield-checkbox-label">' . wp_kses_post( $field_label ) . '</label>';
    }

    /**
     * Render checkbox field as sub-field of a toggle/switcher checkbox
     *
     * @since 1.3.0
     */
    function render_checkbox_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_name = $args['field_name'];
        $field_label = $args['field_label'];
        if ( 'enable_duplication_for' == $args['parent_field_id'] ) {
            // Default is true/enabled. Usually for options introduced at a later date where the previous default is true/enabled.
            $default_value = true;
        } else {
            // Default is false / checked
            $default_value = false;
        }
        $field_option_value = ( isset( $options[$args['parent_field_id']][$args['field_id']] ) ? $options[$args['parent_field_id']][$args['field_id']] : $default_value );
        echo '<input type="checkbox" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-checkbox" name="' . esc_attr( $field_name ) . '" ' . checked( $field_option_value, true, false ) . '>';
        echo '<label for="' . esc_attr( $field_name ) . '" class="asenha-subfield-checkbox-label">' . wp_kses_post( $field_label ) . '</label>';
    }

    /**
     * Render radio buttons field as sub-field of a toggle/switcher checkbox
     *
     * @since 1.3.0
     */
    function render_radio_buttons_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_radios = $args['field_radios'];
        if ( !empty( $args['field_default'] ) ) {
            $default_value = $args['field_default'];
        } else {
            $default_value = false;
        }
        $field_description = ( isset( $args['field_description'] ) ? $args['field_description'] : '' );
        $field_option_value = ( isset( $options[$field_id] ) ? $options[$field_id] : $default_value );
        echo '<div class="asenha-subfield-radio-button-wrapper">';
        foreach ( $field_radios as $radio_label => $radio_value ) {
            echo '<input type="radio" id="' . esc_attr( $field_id . '_' . $radio_value ) . '" class="asenha-subfield-radio-button" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $radio_value ) . '" ' . checked( $radio_value, $field_option_value, false ) . '>';
            echo '<label for="' . esc_attr( $field_id . '_' . $radio_value ) . '" class="asenha-subfield-radio-button-label">' . wp_kses_post( $radio_label ) . '</label>';
        }
        echo '</div>';
        if ( !empty( $field_description ) ) {
            echo '<div class="asenha-subfield-description">' . wp_kses_post( $field_description ) . '</div>';
        }
    }

    /**
     * Render checkboxes field as sub-field of a toggle/switcher checkbox
     *
     * @since 6.9.2
     */
    function render_checkboxes_subfield( $args ) {
        $options = get_option( ASENHA_SLUG_U, array() );
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_options = $args['field_options'];
        $layout = ( !empty( $args['layout'] ) ? $args['layout'] : 'horizontal' );
        $default_value = ( !empty( $args['field_default'] ) ? $args['field_default'] : array() );
        $field_option_value = ( isset( $options[$field_id] ) ? (array) $options[$field_id] : $default_value );
        echo '<div class="wrapper-for-checkboxes ' . esc_attr( $layout ) . '">';
        foreach ( $field_options as $option_label => $option_value ) {
            echo '<div>';
            echo '<input type="checkbox" id="' . esc_attr( $field_id . '_' . $option_value ) . '" class="asenha-subfield-radio-button" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $option_value ) . '" ' . checked( in_array( $option_value, $field_option_value ), 1, false ) . '>';
            echo '<label for="' . esc_attr( $field_id . '_' . $option_value ) . '" class="asenha-subfield-radio-button-label">' . wp_kses_post( $option_label ) . '</label>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Render text field as sub-field of a toggle/switcher checkbox
     *
     * @since 1.4.0
     */
    function render_text_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_width_classname = ( isset( $args['field_width_classname'] ) ? $args['field_width_classname'] : '' );
        $field_type = $args['field_type'];
        $field_prefix = $args['field_prefix'];
        $field_suffix = $args['field_suffix'];
        $field_placeholder = ( isset( $args['field_placeholder'] ) ? $args['field_placeholder'] : '' );
        $field_description = $args['field_description'];
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        if ( $field_id == 'live_site_url' ) {
            $field_option_value = ( isset( $options[$args['field_id']] ) ? base64_decode( $options[$args['field_id']] ) : '' );
        }
        if ( !empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-prefix with-suffix';
        } elseif ( !empty( $field_prefix ) && empty( $field_suffix ) ) {
            $field_classname = ' with-prefix';
        } elseif ( empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-suffix';
        } else {
            $field_classname = '';
        }
        if ( !empty( $field_width_classname ) ) {
            $field_classname .= ' ' . $field_width_classname;
        }
        echo wp_kses_post( $field_prefix ) . '<input type="text" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-text' . esc_attr( $field_classname ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $field_placeholder ) . '" value="' . esc_attr( $field_option_value ) . '">' . wp_kses_post( $field_suffix );
        echo '<label for="' . esc_attr( $field_name ) . '" class="asenha-subfield-checkbox-label">' . esc_html( $field_description ) . '</label>';
    }

    /**
     * Render description field as sub-field of a toggle/switcher checkbox
     *
     * @since 4.6.0
     */
    function render_description_subfield( $args ) {
        $field_description = $args['field_description'];
        echo '<div class="asenha-subfield-description">' . wp_kses( $field_description, get_kses_with_custom_html_ruleset() ) . '</div>';
    }

    /**
     * Render heading for sub-fields of a toggle/switcher checkbox
     *
     * @since 5.0.0
     */
    function render_subfields_heading( $args ) {
        $subfields_heading = $args['subfields_heading'];
        echo '<div class="asenha-subfields-heading">' . wp_kses_post( $subfields_heading ) . '</div>';
    }

    /**
     * Render password field as sub-field of a toggle/switcher checkbox
     *
     * @since 4.1.0
     */
    function render_password_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_type = $args['field_type'];
        $field_prefix = $args['field_prefix'];
        $field_suffix = $args['field_suffix'];
        $field_description = $args['field_description'];
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        if ( !empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-prefix with-suffix';
        } elseif ( !empty( $field_prefix ) && empty( $field_suffix ) ) {
            $field_classname = ' with-prefix';
        } elseif ( empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-suffix';
        } else {
            $field_classname = '';
        }
        $placeholder = '';
        echo wp_kses_post( $field_prefix ) . '<input type="password" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-password' . esc_attr( $field_classname ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" size="24" autocomplete="off" value="' . esc_attr( $field_option_value ) . '">' . wp_kses_post( $field_suffix );
        echo '<label for="' . esc_attr( $field_name ) . '" class="asenha-subfield-checkbox-label">' . esc_html( $field_description ) . '</label>';
    }

    /**
     * Render number field as sub-field of a toggle/switcher checkbox
     *
     * @since 1.4.0
     */
    function render_number_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_type = $args['field_type'];
        $field_prefix = $args['field_prefix'];
        $field_suffix = $args['field_suffix'];
        $field_intro = $args['field_intro'];
        $field_placeholder = ( isset( $args['field_placeholder'] ) ? $args['field_placeholder'] : '' );
        $field_description = ( isset( $args['field_description'] ) ? $args['field_description'] : '' );
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        if ( !empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-prefix with-suffix';
        } elseif ( !empty( $field_prefix ) && empty( $field_suffix ) ) {
            $field_classname = ' with-prefix';
        } elseif ( empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-suffix';
        } else {
            $field_classname = '';
        }
        echo '<div class="asenha-subfield-number-wrapper">';
        if ( !empty( $field_intro ) ) {
            echo '<div class="asenha-subfield-number-intro">' . wp_kses_post( $field_intro ) . '</div>';
        }
        echo '<div>' . wp_kses_post( $field_prefix ) . '<input type="number" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-number' . esc_attr( $field_classname ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $field_placeholder ) . '" value="' . esc_attr( $field_option_value ) . '">' . wp_kses_post( $field_suffix ) . '</div>';
        if ( !empty( $field_description ) ) {
            echo '<div class="asenha-subfield-number-description">' . wp_kses_post( $field_description ) . '</div>';
        }
        echo '</div>';
    }

    /**
     * Render select field as sub-field of a toggle/switcher checkbox
     *
     * @since 1.4.0
     */
    function render_select_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_type = $args['field_type'];
        $field_prefix = $args['field_prefix'];
        $field_suffix = $args['field_suffix'];
        $field_select_options = $args['field_select_options'];
        $field_select_default = $args['field_select_default'];
        $field_intro = $args['field_intro'];
        $field_description = $args['field_description'];
        if ( !empty( $field_select_default ) ) {
            $default_value = $field_select_default;
        } else {
            $default_value = false;
        }
        $field_option_value = ( isset( $options[$field_id] ) ? $options[$field_id] : $default_value );
        if ( !empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-prefix with-suffix';
        } elseif ( !empty( $field_prefix ) && empty( $field_suffix ) ) {
            $field_classname = ' with-prefix';
        } elseif ( empty( $field_prefix ) && !empty( $field_suffix ) ) {
            $field_classname = ' with-suffix';
        } else {
            $field_classname = '';
        }
        if ( $args['display_none_on_load'] ) {
            $inline_style = ' style="display:none;"';
        } else {
            $inline_style = '';
        }
        echo '<div class="asenha-subfield-select-wrapper">';
        if ( !empty( $field_intro ) ) {
            echo '<div class="asenha-subfield-select-intro">' . wp_kses_post( $field_intro ) . '</div>';
        }
        echo '<div' . esc_attr( $inline_style ) . ' class="asenha-subfield-select-inner">' . wp_kses_post( $field_prefix );
        echo '<select name="' . esc_attr( $field_name ) . '" class="asenha-subfield-select' . esc_attr( $field_classname ) . '">';
        foreach ( $field_select_options as $label => $value ) {
            echo '<option value="' . esc_attr( $value ) . '" ' . selected( $value, $field_option_value, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
        echo wp_kses_post( $field_suffix ) . '</div>';
        if ( !empty( $field_description ) ) {
            echo '<div class="asenha-subfield-select-description">' . wp_kses_post( $field_description ) . '</div>';
        }
        echo '</div>';
    }

    /**
     * Render textarea field as sub-field of a toggle/switcher checkbox
     *
     * @since 2.3.0
     */
    function render_textarea_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_slug = $args['field_slug'];
        $field_name = $args['field_name'];
        $field_type = $args['field_type'];
        $field_rows = $args['field_rows'];
        $field_intro = $args['field_intro'];
        $field_description = $args['field_description'];
        $field_placeholder = ( isset( $args['field_placeholder'] ) ? $args['field_placeholder'] : '' );
        // Always load textarea content from robots.txt URL, whether it's a real, custom-made robots.txt file or a virtual one generated by WordPress
        if ( 'robots_txt_content' == $field_id ) {
            if ( array_key_exists( 'manage_robots_txt', $options ) ) {
                if ( !$options['manage_robots_txt'] ) {
                    // Manage robots.txt feature is NOT enabled
                    if ( array_key_exists( 'robots_txt_content', $options ) && $options['robots_txt_content'] ) {
                        $field_option_value = $options['robots_txt_content'];
                    } else {
                        $robots_txt_content = wp_remote_get( get_site_url() . '/robots.txt' );
                        $robots_txt_content = esc_textarea( trim( wp_remote_retrieve_body( $robots_txt_content ) ) );
                        $field_option_value = $robots_txt_content;
                    }
                } else {
                    // Manage robots.txt feature is enabled
                    if ( array_key_exists( 'robots_txt_content', $options ) && $options['robots_txt_content'] ) {
                        $field_option_value = $options['robots_txt_content'];
                    } else {
                        $robots_txt_content = wp_remote_get( get_site_url() . '/robots.txt' );
                        $robots_txt_content = esc_textarea( trim( wp_remote_retrieve_body( $robots_txt_content ) ) );
                        $field_option_value = $robots_txt_content;
                    }
                }
            } else {
                // Manage robots.txt feature has never been enabled yet
                $robots_txt_content = wp_remote_get( get_site_url() . '/robots.txt' );
                $robots_txt_content = esc_textarea( trim( wp_remote_retrieve_body( $robots_txt_content ) ) );
                $field_option_value = $robots_txt_content;
            }
        } else {
            $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        }
        echo '<div class="asenha-subfield-textarea-wrapper">';
        if ( !empty( $field_intro ) ) {
            echo '<div class="asenha-subfield-textarea-intro">' . wp_kses_post( $field_intro ) . '</div>';
        }
        echo '<textarea rows="' . esc_attr( $field_rows ) . '" class="asenha-subfield-textarea" id="' . esc_attr( $field_name ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $field_placeholder ) . '">' . esc_textarea( $field_option_value ) . '</textarea>';
        if ( !empty( $field_description ) ) {
            echo '<div class="asenha-subfield-textarea-description">' . wp_kses_post( $field_description ) . '</div>';
        }
        echo '</div>';
    }

    /**
     * Render textarea field as sub-field of a toggle/switcher checkbox
     *
     * @since 2.3.0
     */
    function render_wpeditor_subfield( $args ) {
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_slug = $args['field_slug'];
        $field_name = $args['field_name'];
        $field_type = $args['field_type'];
        $field_intro = $args['field_intro'];
        $field_description = $args['field_description'];
        $field_placeholder = ( isset( $args['field_placeholder'] ) ? $args['field_placeholder'] : '' );
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        $editor_settings = $args['editor_settings'];
        // https://developer.wordpress.org/reference/classes/_wp_editors/parse_settings/
        echo '<div class="asenha-subfield-wpeditor-wrapper">';
        if ( !empty( $field_intro ) ) {
            echo '<div class="asenha-subfield-wpeditor-intro">' . wp_kses_post( $field_intro ) . '</div>';
        }
        $content = $field_option_value;
        $editor_id = str_replace( array('[', ']'), array('--', ''), $field_name );
        // vi( $editor_id, '', 'for ' . $field_name );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wp_editor( $content, $editor_id, $editor_settings );
        if ( !empty( $field_description ) ) {
            echo '<div class="asenha-subfield-wpeditor-description">' . wp_kses_post( $field_description ) . '</div>';
        }
        echo '</div>';
    }

    /**
     * Render custom HTML subfield
     *
     * @since 5.3.0
     */
    function render_custom_html( $args ) {
        echo wp_kses( $args['html'], get_kses_with_custom_html_ruleset() );
    }

    /**
     * Render media subfield
     * 
     * @since 6.2.2
     */
    function render_media_subfield( $args ) {
        $field_id = $args['field_id'];
        $field_slug = $args['field_slug'];
        $field_name = $args['field_name'];
        $field_media_frame_title = $args['field_media_frame_title'];
        $field_media_frame_multiple = $args['field_media_frame_multiple'];
        $field_media_frame_library_type = $args['field_media_frame_library_type'];
        $field_media_frame_button_text = $args['field_media_frame_button_text'];
        $field_intro = $args['field_intro'];
        $field_description = $args['field_description'];
        $options = get_option( $args['option_name'], array() );
        $field_option_value = ( isset( $options[$field_id] ) ? $options[$field_id] : '' );
        ?>
		<div class="media-subfield-wrapper">
			<input id="<?php 
        echo esc_attr( $field_slug );
        ?>" class="image-picker" type="text" size="40" name="<?php 
        echo esc_attr( $field_name );
        ?>" value="<?php 
        echo esc_url( $field_option_value );
        ?>" />
			<button id="<?php 
        echo esc_attr( $field_slug );
        ?>-button" class="image-picker-button button-secondary"><?php 
        echo __( 'Select an Image', 'admin-site-enhancements' );
        ?></button>
			<?php 
        if ( !empty( $field_description ) ) {
            echo '<div class="asenha-subfield-description media-subfield">' . wp_kses_post( $field_description ) . '</div>';
        }
        ?>
		</div>
		<?php 
    }

    /**
     * Render media subfield
     * 
     * @since 6.2.2
     */
    function render_color_picker_subfield( $args ) {
        $common_methods = new Common_Methods();
        $field_id = $args['field_id'];
        $field_slug = $args['field_slug'];
        $field_name = $args['field_name'];
        $field_intro = $args['field_intro'];
        $field_description = $args['field_description'];
        $field_default_value = $args['field_default_value'];
        $options = get_option( $args['option_name'], array() );
        $field_option_value = ( isset( $options[$field_id] ) ? $options[$field_id] : $field_default_value );
        ?>
		<div class="color-subfield-wrapper">
			<input type="text" id="<?php 
        echo esc_attr( $field_slug );
        ?>" name="<?php 
        echo esc_attr( $field_name );
        ?>" value="<?php 
        echo esc_attr( $common_methods->sanitize_hex_color( $field_option_value ) );
        ?>" data-default-color="<?php 
        echo esc_attr( $common_methods->sanitize_hex_color( $field_default_value ) );
        ?>" class="color-picker"/>
		</div>
		<?php 
    }

    /**
     * Render sortable menu field
     *
     * @since 2.0.0
     */
    function render_sortable_menu( $args ) {
        $triangle_right_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 16 16"><path fill="currentColor" d="M14.222 6.687a1.5 1.5 0 0 1 0 2.629l-10 5.499A1.5 1.5 0 0 1 2 13.5V2.502a1.5 1.5 0 0 1 2.223-1.314z"/></svg>';
        ?>
			<div class="subfield-description"><?php 
        echo esc_html__( 'Drag and drop menu items to the desired position. Optionally change 3rd party plugin/theme\'s menu item titles or hide some items until toggled by clicking "Show All" at the bottom of the admin menu.', 'admin-site-enhancements' );
        ?></div>
			<?php 
        ?>
		<ul id="custom-admin-menu" class="menu ui-sortable">
		<?php 
        global $menu, $submenu;
        $common_methods = new Common_Methods();
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        // Set menu items to be excluded from title renaming. These are from WordPress core.
        $renaming_not_allowed = array(
            'menu-dashboard',
            'menu-pages',
            // 'menu-posts',
            'menu-media',
            'menu-comments',
            'menu-appearance',
            'menu-plugins',
            'menu-users',
            'menu-tools',
            'menu-settings',
        );
        // Get custom menu item titles
        if ( array_key_exists( 'custom_menu_titles', $options ) ) {
            $custom_menu_titles = $options['custom_menu_titles'];
            $custom_menu_titles = explode( ',', $custom_menu_titles );
        } else {
            $custom_menu_titles = array();
        }
        // Get menu items hidden by toggle
        $menu_hidden_by_toggle = $common_methods->get_menu_hidden_by_toggle();
        $i = 1;
        // Check if there's an existing custom menu order data stored in options
        if ( array_key_exists( 'custom_menu_order', $options ) ) {
            $custom_menu = $options['custom_menu_order'];
            $custom_menu = explode( ',', $custom_menu );
            $menu_key_in_use = array();
            // Render sortables with data in custom menu order
            foreach ( $custom_menu as $custom_menu_item ) {
                foreach ( $menu as $menu_key => $menu_info ) {
                    if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                        $menu_item_title = $menu_info[2];
                        $menu_item_id = $menu_info[2];
                    } else {
                        $menu_item_title = $menu_info[0];
                        $menu_item_id = $menu_info[5];
                    }
                    $menu_url_fragment = '';
                    if ( $custom_menu_item == $menu_item_id ) {
                        $menu_item_id_transformed = $common_methods->transform_menu_item_id( $menu_item_id );
                        $is_custom_menu = 'no';
                        ?>
						<li id="<?php 
                        echo esc_attr( $menu_item_id );
                        ?>" class="menu-item parent-menu-item menu-item-depth-0" data-custom-menu-item="<?php 
                        echo esc_attr( $is_custom_menu );
                        ?>">
							<div class="menu-item-bar">
								<div class="menu-item-handle">
									<span class="dashicons dashicons-menu"></span>
									<div class="item-title">
										<div class="title-wrapper">
											<span class="menu-item-title">
											<?php 
                        if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                            $separator_name_ori = $menu_info[2];
                            $separator_name = str_replace( 'separator', 'Separator-', $separator_name_ori );
                            $separator_name = str_replace( '--last', '-Last', $separator_name );
                            $separator_name = str_replace( '--woocommerce', '--WooCommerce', $separator_name );
                            echo '~~ ' . esc_html( $separator_name ) . ' ~~';
                        } else {
                            if ( in_array( $menu_item_id, $renaming_not_allowed ) ) {
                                $menu_item_title = $menu_info[0];
                                echo wp_kses_post( $common_methods->strip_html_tags_and_content( $menu_item_title ) );
                            } else {
                                // Get defaul/custom menu item title
                                foreach ( $custom_menu_titles as $custom_menu_title ) {
                                    // At this point, $custom_menu_title value looks like toplevel_page_snippets__Code Snippets
                                    $custom_menu_title = explode( '__', $custom_menu_title );
                                    if ( $custom_menu_title[0] == $menu_item_id ) {
                                        $menu_item_title = $common_methods->strip_html_tags_and_content( $custom_menu_title[1] );
                                        // e.g. Code Snippets
                                        break;
                                        // stop foreach loop so $menu_item_title is not overwritten in the next iteration
                                    } else {
                                        $menu_item_title = $common_methods->strip_html_tags_and_content( $menu_info[0] );
                                    }
                                }
                                ?>
													<input type="text" value="<?php 
                                echo wp_kses_post( $menu_item_title );
                                ?>" class="menu-item-custom-title" data-menu-item-id="<?php 
                                echo esc_attr( $menu_item_id );
                                ?>">
													<?php 
                            }
                        }
                        ?>
											</span><!-- end of .menu-item-title -->
										<?php 
                        ?>
										</div><!-- end of .title-wrapper -->
										<div class="options-for-hiding">
											<?php 
                        $hide_text = __( 'Hide until toggled', 'admin-site-enhancements' );
                        $checkbox_class = 'parent-menu-hide-checkbox';
                        ?>
											<label class="menu-item-checkbox-label">
												<?php 
                        if ( in_array( $custom_menu_item, $menu_hidden_by_toggle ) ) {
                            ?>
												<input type="checkbox" id="hide-status-for-<?php 
                            echo esc_attr( $menu_item_id_transformed );
                            ?>" class="<?php 
                            echo esc_attr( $checkbox_class );
                            ?>" data-menu-item-title="<?php 
                            echo esc_attr( $common_methods->strip_html_tags_and_content( $menu_item_title ) );
                            ?>" data-menu-item-id="<?php 
                            echo esc_attr( $menu_item_id_transformed );
                            ?>" data-menu-item-id-ori="<?php 
                            echo esc_attr( $menu_item_id );
                            ?>" data-menu-url-fragment="<?php 
                            echo esc_attr( $menu_url_fragment );
                            ?>" checked>
												<span><?php 
                            echo esc_html( $hide_text );
                            ?></span>
													<?php 
                        } else {
                            ?>
												<input type="checkbox" id="hide-status-for-<?php 
                            echo esc_attr( $menu_item_id_transformed );
                            ?>" class="<?php 
                            echo esc_attr( $checkbox_class );
                            ?>" data-menu-item-title="<?php 
                            echo esc_attr( $common_methods->strip_html_tags_and_content( $menu_item_title ) );
                            ?>" data-menu-item-id="<?php 
                            echo esc_attr( $menu_item_id_transformed );
                            ?>" data-menu-item-id-ori="<?php 
                            echo esc_attr( $menu_item_id );
                            ?>" data-menu-url-fragment="<?php 
                            echo esc_attr( $menu_url_fragment );
                            ?>">
												<span><?php 
                            echo esc_html( $hide_text );
                            ?></span>
													<?php 
                        }
                        ?>
											</label>
											<?php 
                        ?>
										</div><!-- end of .options-for-hiding -->
									</div><!-- end of .item-title -->
								</div><!-- end of .menu-item-handle -->
							</div><!-- end of .menu-item-bar -->
							<?php 
                        $i = 1;
                        ?>
							<div class="remove-menu-item"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="#bbbbbb" d="M24 2.4L21.6 0L12 9.6L2.4 0L0 2.4L9.6 12L0 21.6L2.4 24l9.6-9.6l9.6 9.6l2.4-2.4l-9.6-9.6z"/></svg></div>
						</li>
						<?php 
                        $menu_key_in_use[] = $menu_key;
                    }
                }
            }
            // Render the rest of the current menu towards the end of the sortables
            foreach ( $menu as $menu_key => $menu_info ) {
                if ( !in_array( $menu_key, $menu_key_in_use ) ) {
                    if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                        $menu_item_id = $menu_info[2];
                    } else {
                        $menu_item_id = $menu_info[5];
                    }
                    $menu_item_title = $menu_info[0];
                    $menu_url_fragment = '';
                    // Strip tags
                    $menu_item_title = $common_methods->strip_html_tags_and_content( $menu_item_title );
                    // Exclude Show All/Less toggles
                    if ( false === strpos( $menu_item_id, 'toplevel_page_asenha_' ) ) {
                        $menu_item_id_transformed = $common_methods->transform_menu_item_id( $menu_item_id );
                        $is_custom_menu = 'no';
                        ?>
						<li id="<?php 
                        echo esc_attr( $menu_item_id );
                        ?>" class="menu-item parent-menu-item menu-item-depth-0" data-custom-menu-item="<?php 
                        echo esc_attr( $is_custom_menu );
                        ?>">
							<div class="menu-item-bar">
								<div class="menu-item-handle">
									<span class="dashicons dashicons-menu"></span>
									<div class="item-title">
										<div class="title-wrapper">
											<span class="menu-item-title">
												<?php 
                        if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                            $separator_name_ori = $menu_info[2];
                            $separator_name = str_replace( 'separator', 'Separator-', $separator_name_ori );
                            $separator_name = str_replace( '--last', '-Last', $separator_name );
                            $separator_name = str_replace( '--woocommerce', '--WooCommerce', $separator_name );
                            echo '~~ ' . esc_html( $separator_name ) . ' ~~';
                        } else {
                            ?>
													<input type="text" value="<?php 
                            echo wp_kses_post( $menu_item_title );
                            ?>" class="menu-item-custom-title" data-menu-item-id="<?php 
                            echo esc_attr( $menu_item_id );
                            ?>">
												<?php 
                        }
                        ?>
											</span>
											<?php 
                        ?>
										</div>
										<div class="options-for-hiding">
											<?php 
                        $hide_text = __( 'Hide until toggled', 'admin-site-enhancements' );
                        $checkbox_class = 'parent-menu-hide-checkbox';
                        ?>
								        	<label class="menu-item-checkbox-label">
												<input type="checkbox" id="hide-status-for-<?php 
                        echo esc_attr( $menu_item_id_transformed );
                        ?>" class="<?php 
                        echo esc_attr( $checkbox_class );
                        ?>" data-menu-item-title="<?php 
                        echo esc_attr( $common_methods->strip_html_tags_and_content( $menu_item_title ) );
                        ?>" data-menu-item-id="<?php 
                        echo esc_attr( $menu_item_id_transformed );
                        ?>" data-menu-item-id-ori="<?php 
                        echo esc_attr( $menu_item_id );
                        ?>" data-menu-url-fragment="<?php 
                        echo esc_attr( $menu_url_fragment );
                        ?>">
												<span><?php 
                        echo esc_html( $hide_text );
                        ?></span>
											</label>
											<?php 
                        ?>
										</div><!-- end of .options-for-hiding -->
									</div><!-- end of .item-title -->
								</div><!-- end of .menu-item-handle -->
							</div><!-- end of .menu-item-bar -->
							<?php 
                        $i = 1;
                        ?>
							<div class="remove-menu-item"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="#bbbbbb" d="M24 2.4L21.6 0L12 9.6L2.4 0L0 2.4L9.6 12L0 21.6L2.4 24l9.6-9.6l9.6 9.6l2.4-2.4l-9.6-9.6z"/></svg></div>
						</li><!-- end of .menu-item -->
						<?php 
                    }
                }
            }
        } else {
            // No custom menu order has been saved yet
            // Render sortables with existing items in the admin menu
            foreach ( $menu as $menu_key => $menu_info ) {
                if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                    $menu_item_id = $menu_info[2];
                } else {
                    $menu_item_id = $menu_info[5];
                }
                $menu_url_fragment = '';
                $menu_item_title = $menu_info[0];
                $menu_item_id_transformed = $common_methods->transform_menu_item_id( $menu_item_id );
                // Strip tags
                $menu_item_title = $common_methods->strip_html_tags_and_content( $menu_item_title );
                $is_custom_menu = 'no';
                ?>
				<li id="<?php 
                echo esc_attr( $menu_item_id );
                ?>" class="menu-item parent-menu-item menu-item-depth-0" data-custom-menu-item="<?php 
                echo esc_attr( $is_custom_menu );
                ?>">
					<div class="menu-item-bar">
						<div class="menu-item-handle">
							<span class="dashicons dashicons-menu"></span>
							<div class="item-title">
								<div class="title-wrapper">
									<span class="menu-item-title">
									<?php 
                if ( false !== strpos( $menu_info[4], 'wp-menu-separator' ) ) {
                    $separator_name_ori = $menu_info[2];
                    $separator_name = str_replace( 'separator', 'Separator-', $separator_name_ori );
                    $separator_name = str_replace( '--last', '-Last', $separator_name );
                    $separator_name = str_replace( '--woocommerce', '--WooCommerce', $separator_name );
                    echo '~~ ' . esc_html( $separator_name ) . ' ~~';
                } else {
                    if ( in_array( $menu_item_id, $renaming_not_allowed ) ) {
                        echo wp_kses_post( $menu_item_title );
                    } else {
                        ?>
											<input type="text" value="<?php 
                        echo wp_kses_post( $menu_item_title );
                        ?>" class="menu-item-custom-title" data-menu-item-id="<?php 
                        echo esc_attr( $menu_item_id );
                        ?>">
											<?php 
                    }
                }
                ?>
									</span>
									<?php 
                ?>
								</div><!-- end of .title-wrapper -->
								<div class="options-for-hiding">
									<?php 
                $hide_text = __( 'Hide until toggled', 'admin-site-enhancements' );
                $checkbox_class = 'parent-menu-hide-checkbox';
                ?>
									<label class="menu-item-checkbox-label">
										<input type="checkbox" id="hide-status-for-<?php 
                echo esc_attr( $menu_item_id_transformed );
                ?>" class="<?php 
                echo esc_attr( $checkbox_class );
                ?>" data-menu-item-title="<?php 
                echo esc_attr( $common_methods->strip_html_tags_and_content( $menu_item_title ) );
                ?>" data-menu-item-id="<?php 
                echo esc_attr( $menu_item_id_transformed );
                ?>" data-menu-item-id-ori="<?php 
                echo esc_attr( $menu_item_id );
                ?>" data-menu-url-fragment="<?php 
                echo esc_attr( $menu_url_fragment );
                ?>">
										<span><?php 
                echo esc_html( $hide_text );
                ?></span>
									</label>
									<?php 
                ?>
								</div><!-- end of .options-for-hiding -->
							</div><!-- end of .item-title -->
						</div><!-- end of .menu-item-handle -->
					</div><!-- end of .menu-item-bar -->
				<?php 
                $i = 1;
                ?>
					<div class="remove-menu-item"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><path fill="#bbbbbb" d="M24 2.4L21.6 0L12 9.6L2.4 0L0 2.4L9.6 12L0 21.6L2.4 24l9.6-9.6l9.6 9.6l2.4-2.4l-9.6-9.6z"/></svg></div>
				</li>
				<?php 
            }
        }
        ?>
		</ul>
		<?php 
        // Hidden input field to store custom menu order (from options as is, or sortupdate) upon clicking Save Changes.
        $field_id = $args['field_id'];
        $field_name = $args['field_name'];
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        echo '<input type="hidden" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-text" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_option_value ) . '">';
        // Hidden input field to store custom menu titles (from options as is, or custom values entered on each non-WP-default menu items.
        $this->output_admin_menu_organizer_hidden_field( 'custom_menu_titles' );
        // Hidden input field to store hidden menu items (from options as is, or 'Hide' checkbox clicks) upon clicking Save Changes.
        $this->output_admin_menu_organizer_hidden_field( 'custom_menu_hidden' );
    }

    /**
     * Output hidden field
     * 
     * @since 6.9.13
     */
    public function output_admin_menu_organizer_hidden_field( $field_id ) {
        $options = get_option( ASENHA_SLUG_U, array() );
        $field_name = ASENHA_SLUG_U . '[' . $field_id . ']';
        $field_option_value = ( isset( $options[$field_id] ) ? $options[$field_id] : '' );
        echo '<input type="hidden" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-text" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_option_value ) . '">';
    }

    /**
     * Render textarea field as sub-field of a toggle/switcher checkbox
     *
     * @since 2.3.0
     */
    function render_datatable( $args ) {
        global $wpdb;
        $option_name = $args['option_name'];
        if ( !empty( $option_name ) ) {
            $options = get_option( $option_name, array() );
        } else {
            $options = get_option( ASENHA_SLUG_U, array() );
        }
        $field_id = $args['field_id'];
        $field_slug = $args['field_slug'];
        $field_name = $args['field_name'];
        $field_type = $args['field_type'];
        $field_description = $args['field_description'];
        $table_title = $args['table_title'];
        $table_name = $args['table_name'];
        $field_option_value = ( isset( $options[$args['field_id']] ) ? $options[$args['field_id']] : '' );
        ?>
		<table id="login-attempts-log" class="wp-list-table widefat striped datatable">
			<thead>
				<tr class="datatable-tr">
					<th class="datatable-th"><?php 
        _e( 'IP Address<br />Last Username', 'admin-site-enhancements' );
        ?></th>
					<th class="datatable-th"><?php 
        _e( 'Attempts<br />Lockouts', 'admin-site-enhancements' );
        ?></th>
					<th class="datatable-th"><?php 
        _e( 'Last Attempt On', 'admin-site-enhancements' );
        ?></th>
				</tr>
			</thead>
			<tbody>
		<?php 
        $limit = 1000;
        $sql = $wpdb->prepare( "SELECT * FROM {$table_name} ORDER BY unixtime DESC LIMIT %d", array($limit) );
        $entries = $wpdb->get_results( $sql, ARRAY_A );
        foreach ( $entries as $entry ) {
            $unixtime = $entry['unixtime'];
            if ( function_exists( 'wp_date' ) ) {
                $date = wp_date( 'F j, Y', $unixtime );
                $time = wp_date( 'H:i:s', $unixtime );
            } else {
                $date = date_i18n( 'F j, Y', $unixtime );
                $time = date_i18n( 'H:i:s', $unixtime );
            }
            ?>
			<tr class="datatable-tr">
				<td class="datatable-td"><?php 
            echo esc_html( $entry['ip_address'] );
            ?><br /><?php 
            echo esc_html( $entry['username'] );
            ?></td>
				<td class="datatable-td"><?php 
            echo esc_html( $entry['fail_count'] );
            ?><br /><?php 
            echo esc_html( $entry['lockout_count'] );
            ?></td>
				<td class="datatable-td"><span class="unixtime"><?php 
            echo esc_html( $entry['unixtime'] );
            ?></span><?php 
            echo esc_html( $date );
            ?><br /><?php 
            echo esc_html( $time );
            ?></td>
			</tr>
			<?php 
        }
        ?>
			</tbody>
		</table>
		<?php 
        echo '<input type="hidden" id="' . esc_attr( $field_name ) . '" class="asenha-subfield-datatable" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $field_option_value ) . '">';
    }

    /**
     * Render checks and status for AVIF support
     * 
     * @link https://php.watch/versions/8.1/gd-avif
     * @since 5.7.0
     */
    public function render_avif_support_status() {
        // Check status of GD extension and it's AVIF support
        if ( extension_loaded( 'gd' ) && function_exists( 'gd_info' ) ) {
            $is_gd_enabled = true;
            $gd_info = gd_info();
            $gd_version = $gd_info['GD Version'];
            $gd_avif_support = ( isset( $gd_info['AVIF Support'] ) ? isset( $gd_info['AVIF Support'] ) : false );
            if ( $gd_avif_support ) {
                $gd_status = $gd_version . ' <span class="supported">' . __( 'with AVIF support', 'admin-site-enhancements' ) . '</span>';
            } else {
                $gd_status = $gd_version . ' <span class="unsupported">' . __( 'without AVIF support', 'admin-site-enhancements' ) . '</span>';
            }
        } else {
            $is_gd_enabled = false;
            $gd_avif_support = false;
            $gd_status = __( 'Not available', 'admin-site-enhancements' );
        }
        // Check status of ImageMagick library and it's AVIF support
        if ( extension_loaded( 'imagick' ) && class_exists( 'Imagick' ) ) {
            $is_imagick_enabled = true;
            $imagick_version = \Imagick::getVersion();
            if ( preg_match( '/((?:[0-9]+\\.?)+)/', $imagick_version['versionString'], $matches ) ) {
                $imagick_version = $matches[0];
            } else {
                $imagick_version = $imagick_version['versionString'];
            }
            if ( version_compare( $imagick_version, '7.0.25', '>=' ) ) {
                $imagick_avif_support = true;
                $imagick_status = $imagick_version . ' <span class="supported">with AVIF support</span>';
            } else {
                $imagick_avif_support = false;
                $imagick_status = $imagick_version . ' <span class="unsupported">without AVIF support</span>';
            }
        } else {
            $is_imagick_enabled = false;
            $imagick_avif_support = false;
            $imagick_status = __( 'Not available', 'admin-site-enhancements' );
        }
        echo '<div class="asenha-subfield-status">';
        echo '<div class="status-title">' . __( 'AVIF Support Status', 'admin-site-enhancements' ) . '</div>';
        echo '<div class="status-body">';
        echo '<div class="status-item"><span class="status-item-title">PHP</span> : ' . wp_kses_post( phpversion() ) . '</div>';
        echo '<div class="status-item"><span class="status-item-title">GD</span> : ' . wp_kses_post( $gd_status ) . '</div>';
        echo '<div class="status-item"><span class="status-item-title">ImageMagick</span> : ' . wp_kses_post( $imagick_status ) . '</div>';
        echo '</div>';
        echo '<div class="status-footer">' . __( 'Full AVIF support requires that your server / hosting has <a href="https://php.watch/versions/8.1/gd-avif" target="_blank">GD extension</a> compiled with AVIF support in PHP 8.1 or greater, or, <a href="https://web.archive.org/web/20240104012918/https://avif.io/blog/tutorials/imagemagick/" target="_blank">ImageMagick 7.0.25 or greater</a> installed. Without either, you can still upload AVIF files but without auto-generation of the smaller thumbnail sizes. A majority of <a href="https://web.archive.org/web/20231207174740/https://avif.io/blog/articles/avif-browser-support/" target="_blank">modern desktop and mobile browsers</a> support the display of AVIF files.', 'admin-site-enhancements' ) . '</div>';
        echo '</div>';
    }

}
