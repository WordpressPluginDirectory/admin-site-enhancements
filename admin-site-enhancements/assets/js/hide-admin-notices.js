(function( $ ) {
   'use strict';

   $(document).ready( function() {

		// Move admin notices at an interval after document is ready and WP core moves the position of notices under the page heading.
	   	// https://plugins.trac.wordpress.org/browser/hide-admin-notices/tags/1.2.2/assets/js/hide-admin-notices.js
	   	// https://plugins.trac.wordpress.org/browser/admin-notices-manager/tags/1.3.1/assets/js/admin/notices.js

		var noticesPanel = $('.asenha-admin-notices-drawer');
		var startTime = new Date().getTime();

		var interval = setInterval(function () {

			// Stop monitoring after 5 seconds
			if (new Date().getTime() - startTime > 1000) {
				clearInterval(interval);

		   		// Count hidden notices and append into admin bar counter
		   		var noticesCount = $('.asenha-admin-notices-drawer > div').length;

		   		if ( noticesCount > 0 ) {
		   			$('.asenha-admin-notices-menu').show(); // show admin bar menu
		   			// $('.asenha-admin-notices-counter').show(); // show counter
			   		$('.asenha-admin-notices-counter').html(noticesCount); // insert count
		   			$('.asenha-admin-notices-counter').css("opacity", "1"); // show counter
		   		} else {
		   			$('.asenha-admin-notices-menu').hide(); // hide admin bar menu
		   		}

				return;
			}

			// Plugins that outputs notices. For testing.
			// Ajax Press - https://wordpress.org/plugins/ajax-press/
			// Atlas Search - https://wordpress.org/plugins/atlas-search/
			// ExactMetrics Analytics - https://wordpress.org/plugins/google-analytics-dashboard-for-wp/
			// JetPack - https://wordpress.org/plugins/jetpack/
			// ManageWP Worker - https://wordpress.org/plugins/worker/
			// WP Smushit - https://wordpress.org/plugins/wp-smushit/
			// FluentSMTP - https://wordpress.org/plugins/fluent-smtp/
			// WP Backend File Search - https://wordpress.org/plugins/wp-backend-file-search-editor-tweaks/
			// TotalPress Custom post types - https://wordpress.org/plugins/custom-post-types/

			// Reposition notices with the following selectors. Excluding 'notice-system'.
			$('#wpbody-content > .wrap > .notice:not(.system-notice,.hidden,.wcml-notice),'
			+ '#wpbody-content > .wrap > .notice-error,'
			+ '#wpbody-content > .wrap > .error:not(.hidden),'
			+ '#wpbody-content > .wrap > .notice-info,'
			+ '#wpbody-content > .wrap > .notice-information,'
			+ '#wpbody-content > .wrap > #message,'
			+ '#wpbody-content > .wrap > .notice-warning:not(.hidden),'
			+ '#wpbody-content > .wrap > .notice-success,'
			+ '#wpbody-content > .wrap > .notice-updated,'
			+ '#wpbody-content > .wrap > .updated,'
			+ '#wpbody-content > .wrap > .update-nag,'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .notice:not(.system-notice,.hidden),'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .notice-error,'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .error:not(.hidden),'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .notice-info,'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .notice-information,'
			+ '#wpbody-content > .wrap > div > #message,'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .notice-warning:not(.hidden),'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .notice-success,'
			+ '#wpbody-content > .wrap > div :not(#loco-notices,#loco-content)> .notice-updated,'
			+ '#wpbody-content > .wrap > div:not(#loco-notices,#loco-content) > .updated,'
			+ '#wpbody-content > .wrap > div > .update-nag,'
			+ '#wpbody-content > div > .wrap > .notice:not(.system-notice,.hidden),'
			+ '#wpbody-content > div > .wrap > .notice-error,'
			+ '#wpbody-content > div > .wrap > .error:not(.hidden),'
			+ '#wpbody-content > div > .wrap > .notice-info,'
			+ '#wpbody-content > div > .wrap > .notice-information,'
			+ '#wpbody-content > div > .wrap > #message,'
			+ '#wpbody-content > div > .wrap > .notice-warning:not(.hidden),'
			+ '#wpbody-content > div > .wrap > .notice-success,'
			+ '#wpbody-content > div > .wrap > .notice-updated,'
			+ '#wpbody-content > div > .wrap > .updated,'
			+ '#wpbody-content > div > .wrap > .update-nag,'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice:not(.system-notice,.hidden),'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice-error,'
			+ '#wpbody-content > .wrap.woocommerce > form > .error:not(.hidden),'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice-info,'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice-information,'
			+ '#wpbody-content > .wrap.woocommerce > form > #message,'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice-warning:not(.hidden),'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice-success,'
			+ '#wpbody-content > .wrap.woocommerce > form > .notice-updated,'
			+ '#wpbody-content > .wrap.woocommerce > form > .updated,'
			+ '#wpbody-content > .wrap.woocommerce > form > .update-nag,'
			// TranslatePress
			+ '#wpbody-content > #trp-main-settings > form > .notice:not(.system-notice,.hidden),'
			+ '#wpbody-content > #trp-main-settings > form > .notice-error,'
			+ '#wpbody-content > #trp-main-settings > form > .error:not(.hidden),'
			+ '#wpbody-content > #trp-main-settings > form > .notice-info,'
			+ '#wpbody-content > #trp-main-settings > form > .notice-information,'
			+ '#wpbody-content > #trp-main-settings > form > #message,'
			+ '#wpbody-content > #trp-main-settings > form > .notice-warning:not(.hidden),'
			+ '#wpbody-content > #trp-main-settings > form > .notice-success,'
			+ '#wpbody-content > #trp-main-settings > form > .notice-updated,'
			+ '#wpbody-content > #trp-main-settings > form > .updated,'
			+ '#wpbody-content > #trp-main-settings > form > .update-nag,'
			// TranslatePress
			+ '#wpbody-content > .bwfan_header > .notice:not(.system-notice,.hidden),'
			+ '#wpbody-content > .bwfan_header > .notice-error,'
			+ '#wpbody-content > .bwfan_header > .error:not(.hidden),'
			+ '#wpbody-content > .bwfan_header > .notice-info,'
			+ '#wpbody-content > .bwfan_header > .notice-information,'
			+ '#wpbody-content > .bwfan_header > #message,'
			+ '#wpbody-content > .bwfan_header > .notice-warning:not(.hidden),'
			+ '#wpbody-content > .bwfan_header > .notice-success,'
			+ '#wpbody-content > .bwfan_header > .notice-updated,'
			+ '#wpbody-content > .bwfan_header > .updated,'
			+ '#wpbody-content > .bwfan_header > .update-nag,'
			+ '#wpbody-content > .notice:not(.otgs-notice,.wcml-notice),' // LearnDash, WPML WooCommerce Multilingual
			+ '#wpbody-content > .update-nag,' // LearnDash
			+ '#wpbody-content > .jp-connection-banner,' // Jetpack
			+ '#wpbody-content > .jitm-banner,' // Jetpack
			+ '#wpbody-content > .jetpack-jitm-message,' // Jetpack
			+ '#wpbody-content > .ngg_admin_notice,' // Nextgen Gallery
			+ '#wpbody-content > .imagify-welcome,' // Imagify
			+ '#wpbody-content #wordfenceAutoUpdateChoice,' // Wordfence
			+ '#wpbody-content #easy-updates-manager-dashnotice,' // Easy Updates Manager
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice:not(.system-notice,.hidden),' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice-error,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .error:not(.hidden),' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice-info,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice-information,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap #message,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice-warning:not(.hidden),' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice-success,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .notice-updated,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .updated,' // GenerateBlocks
			+ '#wpbody-content > .wrap.gblocks-dashboard-wrap .update-nag' // GenerateBlocks
			).not(':hidden').detach()
			.appendTo(noticesPanel)
			.show();

		}, 250);

   		// Set up the side drawer that holds the hidden admin notices: https://stephanwagner.me/jBox

   		var noticesModal = new jBox('Modal', {
   			attach: '.asenha-admin-notices-menu',
   			trigger: 'click', // or 'mouseenter'
   			// content: 'Test'
   			content: $('.asenha-admin-notices-drawer'),
   			width: 1118, // pixels
   			closeButton: 'box',
   			addClass: 'admin-notices-modal',
   			overlayClass: 'admin-notices-modal-overlay',
   			target: '#wpwrap', // where to anchor the modal
   			position: {
   				x: 'right',
   				y: 'top'
   			},
   			// fade: 1000,
   			animation: {
   				open: 'slide:bottom',
   				close: 'slide:bottom'
   			}
   		});

   });

})( jQuery );