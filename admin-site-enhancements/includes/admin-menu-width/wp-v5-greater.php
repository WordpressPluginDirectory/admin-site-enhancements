<?php

if ( ! is_rtl() ) {
	$margin_property = 'margin-left';
	$position = 'left';
} else {
	$margin_property = 'margin-right';
	$position = 'right';
}

?>
<style>

#wpcontent, #wpfooter {
	<?php echo esc_html( esc_html( $margin_property ) ); ?>: <?php echo esc_html( esc_html( $custom_width ) ); ?>;
}

#adminmenuback, #adminmenuwrap, #adminmenu, #adminmenu .wp-submenu {
	width: <?php echo esc_html( $custom_width ); ?>;
}

#adminmenu .wp-submenu {
	<?php echo esc_html( $position ); ?>: <?php echo esc_html( $custom_width ); ?>;
}

#adminmenu .wp-not-current-submenu .wp-submenu, .folded #adminmenu .wp-has-current-submenu .wp-submenu {
	min-width: <?php echo esc_html( $custom_width ); ?>;
}

@media (min-width:960px) {
	/* WooCommerce header fix */
	.woocommerce-layout__header,
	/* Elementor Settings */
	#e-admin-top-bar-root {
		width: calc(100% - <?php echo esc_html( $custom_width ); ?>);
	}	
}

/* Gutenberg / Block Editor fix */
.auto-fold .interface-interface-skeleton {
	<?php echo esc_html( $position ); ?>: <?php echo esc_html( $custom_width ); ?>;	
}

/* ASE Form Builder */
.fb-header-nav {
    width: calc(100% - <?php echo esc_html( esc_html( $custom_width ) ); ?>) !important;
}

</style>