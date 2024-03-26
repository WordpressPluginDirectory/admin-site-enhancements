<?php

/**
 * Initialize options. [TODO] Move this into activation class at v7.
 * 
 * @since 6.4.1
 */
if ( false === get_option( ASENHA_SLUG_U ) ) {
    add_option( ASENHA_SLUG_U, array() );
}
if ( false === get_option( ASENHA_SLUG_U . '_stats' ) ) {
    add_option( ASENHA_SLUG_U . '_stats', array() );
}
if ( false === get_option( ASENHA_SLUG_U . '_extra' ) ) {
    add_option( ASENHA_SLUG_U . '_extra', array() );
}
/**
 * Register admin menu
 *
 * @since 1.0.0
 */
function asenha_register_admin_menu()
{
    add_submenu_page(
        'tools.php',
        // Parent page/menu
        'Admin and Site Enhancements',
        // Browser tab/window title
        'Enhancements',
        // Sube menu title
        'manage_options',
        // Minimal user capabililty
        ASENHA_SLUG,
        // Page slug. Shows up in URL.
        'asenha_add_settings_page'
    );
}

/**
 * Create the settings page of the plugin
 *
 * @since 1.0.0
 */
function asenha_add_settings_page()
{
    ?>
	<div class="wrap asenha">

		<div id="asenha-header" class="asenha-header">
			<div class="asenha-header-left">
				<img src="<?php 
    echo  esc_html( ASENHA_URL ) . 'assets/img/ase_icon.png' ;
    ?>" class="asenha-icon"/>
				<h1 class="asenha-heading">
					<?php 
    echo  esc_html( get_admin_page_title() ) ;
    ?>
					(ASE)
					<?php 
    ?>
				</h1>
				<!-- <a href="https://wordpress.org/plugins/admin-site-enhancements/" target="_blank" class="asenha-header-action"><span>&#8505;</span> <?php 
    // esc_html_e( 'Info', 'admin-site-enhancements' );
    ?></a> -->
			</div>
			<div class="asenha-header-right">
                <?php 
    // https://icon-sets.iconify.design/iconamoon/star-bold/
    $svg_star = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="2.5" d="m12 2l3.104 6.728l7.358.873l-5.44 5.03l1.444 7.268L12 18.28L5.534 21.9l1.444-7.268L1.538 9.6l7.359-.873L12 2Z"/></svg>';
    // https://icon-sets.iconify.design/octicon/question-16/
    $svg_support = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8Zm8-6.5a6.5 6.5 0 1 0 0 13a6.5 6.5 0 0 0 0-13ZM6.92 6.085h.001a.749.749 0 1 1-1.342-.67c.169-.339.436-.701.849-.977C6.845 4.16 7.369 4 8 4a2.756 2.756 0 0 1 1.637.525c.503.377.863.965.863 1.725c0 .448-.115.83-.329 1.15c-.205.307-.47.513-.692.662c-.109.072-.22.138-.313.195l-.006.004a6.24 6.24 0 0 0-.26.16a.952.952 0 0 0-.276.245a.75.75 0 0 1-1.248-.832c.184-.264.42-.489.692-.661c.103-.067.207-.132.313-.195l.007-.004c.1-.061.182-.11.258-.161a.969.969 0 0 0 .277-.245C8.96 6.514 9 6.427 9 6.25a.612.612 0 0 0-.262-.525A1.27 1.27 0 0 0 8 5.5c-.369 0-.595.09-.74.187a1.01 1.01 0 0 0-.34.398ZM9 11a1 1 0 1 1-2 0a1 1 0 0 1 2 0Z"/></svg>';
    // https://icon-sets.iconify.design/octicon/comment-16/
    $svg_feedback = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16"><path fill="currentColor" d="M1 2.75C1 1.784 1.784 1 2.75 1h10.5c.966 0 1.75.784 1.75 1.75v7.5A1.75 1.75 0 0 1 13.25 12H9.06l-2.573 2.573A1.458 1.458 0 0 1 4 13.543V12H2.75A1.75 1.75 0 0 1 1 10.25Zm1.75-.25a.25.25 0 0 0-.25.25v7.5c0 .138.112.25.25.25h2a.75.75 0 0 1 .75.75v2.19l2.72-2.72a.749.749 0 0 1 .53-.22h4.5a.25.25 0 0 0 .25-.25v-7.5a.25.25 0 0 0-.25-.25Z"/></svg>';
    // https://icon-sets.iconify.design/iconamoon/file-document/
    $svg_docs = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="2"><path stroke-linecap="round" d="M7 21a2 2 0 0 1-2-2V3h9l5 5v11a2 2 0 0 1-2 2H7Z"/><path d="M13 3v6h6"/><path stroke-linecap="round" d="M9 13h6m-6 4h6"/></g></svg>';
    // https://icon-sets.iconify.design/pajamas/heart/
    $svg_sponsor = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 16 16"><path fill="currentColor" fill-rule="evenodd" d="M8.753 2.247L8 3l-.753-.753A4.243 4.243 0 0 0 1.25 8.25l5.69 5.69L8 15l1.06-1.06l5.69-5.69a4.243 4.243 0 0 0-5.997-6.003ZM8 12.879l5.69-5.69a2.743 2.743 0 0 0-3.877-3.881l-.752.753L8 5.12L6.94 4.06l-.753-.752v-.001A2.743 2.743 0 0 0 2.31 7.189L8 12.88Z" clip-rule="evenodd"/></svg>';
    ?>
	 				<a href="https://wordpress.org/plugins/admin-site-enhancements/#reviews" target="_blank" class="asenha-header-action review"><?php 
    echo  wp_kses( $svg_star, get_kses_with_svg_ruleset() ) . esc_html( 'Review', 'admin-site-enhancements' ) ;
    ?></a>
					<a href="https://wordpress.org/support/plugin/admin-site-enhancements/" target="_blank" class="asenha-header-action feedback"><?php 
    echo  wp_kses( $svg_feedback, get_kses_with_svg_ruleset() ) . esc_html( 'Feedback', 'admin-site-enhancements' ) ;
    ?></a>
					<!--<a href="https://www.wpasenha.com/docs/" target="_blank" class="asenha-header-action docs"><?php 
    // echo $svg_docs . esc_html( 'Docs', 'admin-site-enhancements' );
    ?></a>-->
					<!--<a id="plugin-sponsor" href="#" class="asenha-header-action sponsor"><?php 
    // echo $svg_sponsor . esc_html( 'Sponsor', 'admin-site-enhancements' );
    ?></a>-->
	                <a href="https://www.wpase.com/upgrade-btn" target="_blank" id="plugin-upgrade" class="button button-primary plugin-upgrade">Get ASE Pro</a>
				<?php 
    ?>
				<a class="button button-primary asenha-save-button">Save Changes</a>
				<!-- https://icon-sets.iconify.design/svg-spinners/180-ring-with-bg/ -->
				<div class="asenha-saving-changes" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="#2271b1" d="M12,1A11,11,0,1,0,23,12,11,11,0,0,0,12,1Zm0,19a8,8,0,1,1,8-8A8,8,0,0,1,12,20Z" opacity=".25"/><path fill="#2271b1" d="M12,4a8,8,0,0,1,7.89,6.7A1.53,1.53,0,0,0,21.38,12h0a1.5,1.5,0,0,0,1.48-1.75,11,11,0,0,0-21.72,0A1.5,1.5,0,0,0,2.62,12h0a1.53,1.53,0,0,0,1.49-1.3A8,8,0,0,1,12,4Z"><animateTransform attributeName="transform" dur="0.75s" repeatCount="indefinite" type="rotate" values="0 12 12;360 12 12"/></path></svg></div>
				<div class="asenha-changes-saved" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"><path fill="seagreen" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2zM9.29 16.29L5.7 12.7a.996.996 0 1 1 1.41-1.41L10 14.17l6.88-6.88a.996.996 0 1 1 1.41 1.41l-7.59 7.59a.996.996 0 0 1-1.41 0z"/></svg></div>
			</div>
		</div>

		<div class="asenha-body">
			<?php 
    ?>
			<div class="asenha-upgrade-nudge" style="display: none;">
				<div class="asenha-upgrade-nudge__message">The Pro version of ASE is here! Lifetime Deal (LTD) available.</div>
				<a href="https://www.wpase.com/upgrade-ndg" class="button asenha-upgrade-nudge__button" target="_blank">Find Out More</a>
				<a href="#" id="dismiss-upgrade-nudge" class="asenha-upgrade-nudge__dismiss">
					<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"><path fill="currentColor" d="M24 2.4L21.6 0L12 9.6L2.4 0L0 2.4L9.6 12L0 21.6L2.4 24l9.6-9.6l9.6 9.6l2.4-2.4l-9.6-9.6z"/></svg>
				</a>
			</div>
			<div class="asenha-sponsorship-nudge nudge-show-more is-enabled" style="display: none;">
				<h3>Looks like some of these free enhancements have been useful for your site?</h3> 
				<p class="nudge-description intro">Please consider supporting ASE.</p>
				<a id="sponsorship-nudge-show-moreless" class="nudge-show-more-less show-more-less show-more" href="#">Find Out How ▼</a>
				<div class="nudge-wrapper-show-more">
					<?php 
    // Quotes on sponsorship
    $sponsorship_quotes = array(
        '"A very very useful plugin. I have made a little sponsorship and encourage other users to do the same as it is so much deserved. Thank you Bowo!" ~<a href="https://wordpress.org/support/topic/very-very-useful-54/" target="_blank">@pgrand83</a>',
        '"Please consider supporting it if you use it on multiple sites / it saves you a lot of time, we as a community need to keep good devs like this and gems like this plugin alive." ~<a href="https://wordpress.org/support/topic/you-need-this-19/" target="_blank">@kiikiikii</a>',
        '"Tried it and was blown away with all the options! How is this free? I will sponsor you because this is simply... [insert your favorite superlative here]" ~<a href="https://wordpress.org/support/topic/installed-on-all-my-sites-3/" target="_blank">@mgjaltema</a>',
        '"This replaces so many plugins and snippets! Support the developer (reviews, donations etc.)" ~<a href="https://wordpress.org/support/topic/this-relpaces-many-plugins-and-snippets/" target="_blank">Max Ziebell</a>',
        '"Not only free, but worth a donation. This plugins does so many little things that there\'s bound to be a bunch that will make your life easier." ~<a href="https://wordpress.org/support/topic/not-only-free-but-worth-a-donation/" target="_blank">Darryl</a>',
        '"Replaced 6 plugins with this. Very easy plugin and great support. Found a bug and was fixed in hours!" ~<a href="https://wordpress.org/support/topic/replaced-6-plugins-with-this/" target="_blank">@dagaloni</a>',
        '"Amazing plugin that has replaced at least 10 plugins for me. Lightweight, clean and easy to use. The developer is also very helpful and responsive." ~<a href="https://wordpress.org/support/topic/replaced-atleast-10-plugins-for-me/" target="_blank">@sk209</a>',
        '"Very handy plugin by a great dev. Reported a bug yesterday, it was fixed today. Can’t ask for better than that." ~<a href="https://wordpress.org/support/topic/very-handy-plugin-by-a-great-dev/" target="_blank">Greg Mount</a>',
        '"Excellent and very well-supported plugin. I had a small issue, posted a support comment, and the developer had things sorted in a couple of days with a patch upgrade." ~<a href="https://wordpress.org/support/topic/excellent-and-very-well-supported-plugin-saves-me-a-lot-of-work/" target="_blank">@grizdev</a>',
        '"Bowo is awesome! Thanks to his great plugin my WP backend UI is cleaned up and organized, and I was able to uninstall other plugins too. Super useful, really recommended!" ~<a href="https://wordpress.org/support/topic/love-that-plugin-2/" target="_blank">@adminmax</a>',
        '"A very useful plugin that also has great support. Amazing that this plugin is free too. Great work and thanks to the author." ~<a href="https://wordpress.org/support/topic/very-helpful-1357/" target="_blank">@tomo55555</a>',
        '"Great plugin! I will definitely support it’s development because I know it will save me time and frustration on all of the websites I set up." ~<a href="https://wordpress.org/support/topic/great-plugin-clear-useful-and-a-joy-to-use/" target="_blank">@toddneufeld</a>',
        '"Love the work you’ve done with this plugin! Incredibly powerful and well organized. It’s a real accomplishment to put this many features into a plugin and still make it easy to navigate." ~<a href="https://wordpress.org/support/topic/holy-cow-this-is-excellent/" target="_blank">Nathan Ingram</a>',
        '"I must express gratitude to the author for the exceptional effort invested in developing this plugin... it nearly aligns entirely with the options I typically apply to each website." ~<a href="https://wordpress.org/support/topic/excellent-features-selection/" target="_blank">@cvladan</a>',
        '"The support is awesome – I had an issue which I was able to pin down with the creator. Very fast response time, polite conversation and successful in the end 🙂. Fully recommended!" ~<a href="https://wordpress.org/support/topic/simple-yet-powerful-103/" target="_blank">@gulpman</a>',
        '"I\'ve started installing this plugin as part of my \'standard\' set up. It is continually improving and seems very stable and reliable. I have made 6 installations so far and I am very impressed. Thanks Bowo." ~<a href="https://wordpress.org/support/topic/excellent-plugin-8681/" target="_blank">@jacalakie</a>',
        '"I\'ve been looking for something like this for a long time. The developer is also very friendly and helpful. Highly recommended!" ~<a href="https://wordpress.org/support/topic/must-have-684/" target="_blank">@jdudi</a>',
        '"A must have plugin for most sites, So many useful features. along with a great developer who is open to suggestions." ~<a href="https://wordpress.org/support/topic/amazing-plugin-2443/" target="_blank">@akgt</a>',
        '"Great plugin and awesome developer!" ~<a href="https://wordpress.org/support/topic/awesome-9998/" target="_blank">@mrgy05</a>',
        '"This one plug-in has replaced at the very least four other plug-ins I was using regularly. I can’t thank you enough for your work. Amazing job!" ~<a href="https://wordpress.org/support/topic/amazing-work-63/" target="_blank">@tbutcher</a>',
        '"Technical support? Unbelievable, one problem and quick response with immediate solution. It has become one of my essential plugins. Just amazing!" ~<a href="https://wordpress.org/support/topic/there-are-no-words-just-amazing/" target="_blank">@samirhp</a>',
        '"I just can’t believe I’ll be able to start using this single plugin instead of all the separate ones... what a relief! Thanks man for this great plugin, it’s like you’ve read my mind 🙂" ~<a href="https://wordpress.org/support/topic/amazing-how-many-separate-plugins-this-replaces/" target="_blank">@yudayuda</a>',
        '"Awesome plugin! What a time saver and workflow improvements. Having all these setting in one place, one plugin. Replaces many free and paid for plugins." ~<a href="https://wordpress.org/support/topic/awesome-plugin-replaces-so-many-other-plugins/" target="_blank">@greggwatson</a>',
        '"This plugin has quickly become my go-to solution for all my projects. It\'s a game-changer, saving me valuable time and sparing me the frustration of dealing with bloated plugins. " ~<a href="https://wordpress.org/support/topic/must-have-plugin-for-every-website-2/" target="_blank">Aronu</a>'
    );
    $random_sponsorship_quote = $sponsorship_quotes[rand( 0, count( $sponsorship_quotes ) - 1 )];
    // Quotes on general support
    $support_quotes = array(
        '“Simply the best! Still looking for the sixth star for you. Don’t stop developing… We are supporting you!“ ~<a href="https://wordpress.org/support/topic/too-good-to-be-true-22/" target="_blank">@springbreak</a>',
        '"Amazing job – premium functions in free plugin. Everything is clear, fast, consistent and lightweight. Best possible rating." ~<a href="https://wordpress.org/support/topic/amazing-job-premium-functions-in-free-plugin/" target="_blank">@pijag</a>',
        '"Really grateful and impressed at the pace of development and adding new features including some I have suggested, so feedback is really worthwhile." ~<a href="https://wordpress.org/support/topic/fantastic-plugin-replacing-many-other-plugins/" target="_blank">Dale Reardon</a>',
        '"Greatest plugin ever. All in one solution to most of my problems. Thank you very much." ~<a href="https://wordpress.org/support/topic/greatest-plugin-ever-11/" target="_blank">@angelaustr</a>',
        '"It’s worth 10 stars (or more). This plugin eliminates the need to install many other plugins and also makes functions.php smaller since I have to insert fewer code snippets." ~<a href="https://wordpress.org/support/topic/its-worth-10-stars-or-more/" target="_blank">Angelika Reisiger</a>',
        '"This plugin is a real Swiss Army knife... combines multiple plugins and is still lightweight. Also Bowo, the developer, is friendly and replies quite quickly!" ~<a href="https://wordpress.org/support/topic/amazing-3728/" target="_blank">@olpo24</a>',
        '"Great job! Saved me lots of time to add lots of plugins to get ready for my work. It’s a relief to have everything streamlined and ready to go." ~<a href="https://wordpress.org/support/topic/super-2817/" target="_blank">Tao Sheng</a>',
        '"Very powerful tool. With this plugin, I can remove tons of plugins to reduce the possibility of plugin conflicts." ~<a href="https://wordpress.org/support/topic/very-powerful-tool-13/" target="_blank">@chiehliniceday</a>',
        '"Very useful! Great compilation of settings and options. It had quickly become one of my essential plugins." ~<a href="https://wordpress.org/support/topic/very-useful-3276/" target="_blank">@unapersona</a>',
        '"This plugin easily replaces a dozen or more plugins I install on every website project... and support has been wonderful and responsive. Highly recommended!" ~<a href="https://wordpress.org/support/topic/amazing-must-have-plugin-2/" target="_blank">@netzzjd</a>',
        '"I love that the whole plugin is smaller in file-size than some of the plugins that it replaces, which do only one of these things. Thank you for the great work!" ~<a href="https://wordpress.org/support/topic/very-useful-swiss-army-knife/" target="_blank">@dvaer</a>',
        '"This is a great plugin, it replaces many single purpose plugins which bloat up the site and the admin area. Great idea, good work!" ~<a href="https://wordpress.org/support/topic/replaces-a-lot-of-single-purpose-plugins/" target="_blank">@tageins</a>',
        '"Probably the best WP swiss army knife I’ve ever come across... Has noticeably improved performance on many of my sites. Keep up the great work!" ~<a href="https://wordpress.org/support/topic/im-replacing-so-many-plugins-with-this/" target="_blank">@instadesign</a>',
        '"ASE’s enhanced admin dashboard, improved site performance, and robust security features have truly transformed our website management." ~<a href="https://wordpress.org/support/topic/this-has-reduced-my-plugin-list-by-6-7/" target="_blank">@tomhung</a>',
        '"I love your plugin! All this needed functionality in one place is very helpful and saves me from writing (pasting) a lot of custom code every time." ~<a href="https://wordpress.org/support/topic/lifesaver-this-saves-so-much-time-and-custom-coding/" target="_blank">@prosite</a>',
        '"Amazingly good... you can easily replace several plugins with this easy-to-use one. It’s well thought out and offers features I didn’t even realize I needed." ~<a href="https://wordpress.org/support/topic/amazingly-good-8/" target="_blank">@brenteades</a>',
        '"Favorite!!! It has replaced several plugins I had in the past. This has become one of the first plugins I install." ~<a href="https://wordpress.org/support/topic/favorite-11/" target="_blank">@cck23</a>',
        '"ASE has been a game-changer for us... we were able to remove numerous duplicate plugins, reducing clutter and improving efficiency." ~<a href="https://wordpress.org/support/topic/this-has-reduced-my-plugin-list-by-6-7/" target="_blank">@tomhung</a>',
        '"This plugin is amazing. It replaces so many plugins and still removes bloat. For efficiency, security, flexibility and speed, it doesn’t get better." ~<a href="https://wordpress.org/support/topic/covers-all-manner-of-wp-sins/" target="_blank">Mary C. Dunford</a>',
        '"This plugin is what I have been waiting for to see for years! It has so many useful options that previously you needed to google to find snippets for and it was hard to keep track of all of them." ~<a href="https://wordpress.org/support/topic/amazing-swiss-army-tool-for-wordpress/" target="_blank">@alexgraphicd</a>',
        '"I’m already in love with this plugin... it will make my WP-life a lot easier 🙂" ~<a href="https://wordpress.org/support/topic/im-allready-in-love-with-this-plugin/" target="_blank">@medieskolen</a>',
        '"This plugin allows you to install and maintain one plugin instead of a host of smaller ones. My tests were all successful and I was happy to simplify my maintenance with fewer plugins." ~<a href="https://wordpress.org/support/topic/replaced-4-plugins-worked-well-a/" target="_blank">Vic Drover</a>',
        '"So many useful features it blows my mind, as well as enabling me to ditch so many other plugins." ~<a href="https://wordpress.org/support/topic/amazing-plugin-2441/" target="_blank">@simonclay</a>',
        '"One of those plugins that feels like it should be part of the core, lots of useful features without the bloat." ~<a href="https://wordpress.org/support/topic/great-contribution-5/" target="_blank">@mohobook</a>',
        '"One of the best and feature-rich plugins to add simple functions without using all sorts of separate plugins." ~<a href="https://wordpress.org/support/topic/amazing-all-purpose-plugin/" target="_blank">@toineenzo</a>'
    );
    $random_support_quote = $support_quotes[rand( 0, count( $support_quotes ) - 1 )];
    $share_quotes = array(
        '"Will recommend to everyone! A nice plugin, which will grow and become the best plugin ever made!!" ~<a href="https://wordpress.org/support/topic/will-recommend-to-everyone/" target="_blank">@simonvinther</a>',
        '"It’s very honorable that you created this intentionally to give back to the WP community! I’ll be sure to share this plugin with all the freelance WP-builders at my shared office space!" ~<a href="https://wordpress.org/support/topic/installed-on-all-my-sites-3/" target="_blank">@mgjaltema</a>',
        '"Simple and gold. This plugin so awesome and it should be known by more people." ~<a href="https://wordpress.org/support/topic/simple-and-gold/" target="_blank">Julian Song</a>',
        '"ASE is a must-have plugin for anyone looking to optimize their WordPress site and streamline their workflow." ~<a href="https://wordpress.org/support/topic/this-has-reduced-my-plugin-list-by-6-7/" target="_blank">@tomhung</a>',
        '"Admin Site Enhancements has made my list of \'must install\' plug-ins, since it makes so many other tasks much easier." ~<a href="https://wordpress.org/support/topic/excellent-and-very-well-supported-plugin-saves-me-a-lot-of-work/" target="_blank">@grizdev</a>',
        '"I was super skeptical that this plugin could do so much without any problems, but i was wrong... 100% recommended!" ~<a href="https://wordpress.org/support/topic/the-all-in-one-plugin-you-need-in-your-arsenal/" target="_blank">@scarlywebs</a>',
        '"The selection of tools... nearly aligns entirely with the options I typically apply to each website. This plugin is commendable." ~<a href="https://wordpress.org/support/topic/excellent-features-selection/" target="_blank">@cvladan</a>',
        '"This plugin is amazing. So much useful functionality packed in. This is now a must-use plugin in my development stack. Thank you! ~<a href="https://wordpress.org/support/topic/amazing-now-part-of-my-must-use-dev-stack/" target="_blank">@phillcoxon</a>"',
        '"Very good, must use plugin, that makes life easier for everyone." ~<a href="https://wordpress.org/support/topic/very-good-6977/" target="_blank">@alexeerma</a>',
        '"Must-have plugin for every website. It\'s a game-changer." ~<a href="https://wordpress.org/support/topic/must-have-plugin-for-every-website-2/" target="_blank">Aronu</a>',
        '"This plugin has quickly become my go-to solution for all my projects." ~<a href="https://wordpress.org/support/topic/must-have-plugin-for-every-website-2/" target="_blank">Aronu</a>',
        '"Awesome plugin, it\'s an all in one solution!" ~<a href="https://wordpress.org/support/topic/everything-in-one-place-3/" target="_blank">@gabikod</a>',
        '"This is one of the best utility plugins that eliminates the need to use multiple plugins. It has become a standard plugin used in my WP site blueprint." ~<a href="https://wordpress.org/support/topic/must-have-wp-utility-tool/" target="_blank">Ken Sim</a>',
        '"Necessary plugin to every WordPress site." ~<a href="https://wordpress.org/support/topic/its-a-very-useful-plugin/" target="_blank">@ntamas</a>',
        '"ASE is a highly recommended plugin for anyone looking to improve the functionality and usability of their WordPress site" ~<a href="https://wordpress.org/support/topic/amazing-plugin-in-place-of-multiple-plugins/" target="_blank">Ayyaz Ahmad</a>'
    );
    $random_share_quote = $share_quotes[rand( 0, count( $share_quotes ) - 1 )];
    ?>
					<div class="nudge-quotes">
						<div class="user-quote"><?php 
    echo  wp_kses_post( $random_sponsorship_quote ) ;
    ?></div>
						<div class="user-quote"><?php 
    echo  wp_kses_post( $random_support_quote ) ;
    ?></div>
					</div>
					<div class="nudge-content">
						<div class="nudge-primary">
							<h4>Sponsor ASE from as little as USD 1, monthly or one-time</h4>
							<div class="nudge-info">
								<p class="nudge-description">Please consider sponsoring ASE's ongoing development and maintenance so it can remain functional and useful for years to come. Thank you!</p>
							</div>
							<div class="nudge-ctas">
								<a href="https://bowo.io/asenha-sp-gth-ndg" class="button button-primary sponsorship-button monthly" target="_blank">Sponsor Monthly</a>
								<a href="https://bowo.io/asenha-sp-ppl-ndg" class="button sponsorship-button one-time" target="_blank">Sponsor One-Time</a>
								<a href="#" id="have-sponsored" class="asenha-have-sponsored">I've sponsored ASE</a>
							</div>
							<div class="nudge-stats">
								<p class="nudge-description">This free version of ASE has consumed more than <a href="https://wordpress.org/plugins/admin-site-enhancements/#developers" target="_blank">250 hours of dev time</a>. At v6.9.5 (released on March 25, 2024) and 50,000+ active installs, there have been <a href="https://bowo.io/asenha-sp-gth-ndg" target="_blank">6 monthly sponsors</a> and <a href="https://bowo.io/asenha-sp-ppl-ndg" target="_blank">59 one-time sponsors</a>. You can be one today!</p>
							</div>
						</div>
						<div class="nudge-secondary">
							<h4>Give ASE your 5-star review or provide detailed feedback</h4>
							<p class="nudge-description">If financial sponsorship is not something you can provide at the moment, you can always <a href="https://wordpress.org/plugins/admin-site-enhancements/#reviews" target="_blank">add a quick, 5-star review</a>.</p>
							<p class="nudge-description">Or, if you find something is lacking or not working as you expect it to, you can provide a good and detailed <a href="https://wordpress.org/support/plugin/admin-site-enhancements/" target="_blank">feature request or feedback</a>, which is much more appreciated than a 4-star review or less. This is how we can work together to improve ASE.</p>
						</div>
					</div>
					<div class="user-quote share-quote"><?php 
    echo  wp_kses_post( $random_share_quote ) ;
    ?></div>
					<p class="nudge-description">Do <a href="https://wordpress.org/plugins/admin-site-enhancements/" target="_blank">share about ASE</a> with your colleagues and/or community.</p>
					<a href="https://bowo.io" target="_blank" class="nudge-photo-link"><img src="<?php 
    echo  esc_attr( ASENHA_URL . 'assets/img/bowo.jpg' ) ;
    ?>" class="nudge-photo" /></a>
					<h3>Thank you!</h3> 
					<p class="nudge-description nudge-closing">I hope you continue to benefit from ASE. ~<a href="https://bowo.io" target="_blank">Bowo</a></p>
					<div class="dismiss-sponsorship-nudge"><a id="sponsorship-nudge-show-less" href="#">Show less</a> &bull; <a href="#" id="sponsorship-nudge-dismiss" class="asenha-sponsorship-nudge-dismiss">Dismiss</a></div>
				</div>
			</div>
			<?php 
    ?>
			<form action="options.php" method="post">
				<div class="asenha-vertical-tabs">
					<div class="asenha-tab-buttons">
						<?php 
    // https://icon-sets.iconify.design/mdi/database-check-outline/ -- db check
    // https://icon-sets.iconify.design/mdi/file-document-box-multiple-outline/ -- docs
    // https://icon-sets.iconify.design/fluent/content-view-28-regular/ -- content
    // https://icon-sets.iconify.design/lucide/shapes/ -- shapes
    $icon_content_management = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M8.3 10a.7.7 0 0 1-.626-1.079L11.4 3a.7.7 0 0 1 1.198-.043L16.3 8.9a.7.7 0 0 1-.572 1.1Z"/><rect width="7" height="7" x="3" y="14" rx="1"/><circle cx="17.5" cy="17.5" r="3.5"/></g></svg>';
    // https://icon-sets.iconify.design/mingcute/layout-line/
    $icon_admin_interface = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" fill-rule="evenodd"><path d="M24 0v24H0V0h24ZM12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035c-.01-.004-.019-.001-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427c-.002-.01-.009-.017-.017-.018Zm.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093c.012.004.023 0 .029-.008l.004-.014l-.034-.614c-.003-.012-.01-.02-.02-.022Zm-.715.002a.023.023 0 0 0-.027.006l-.006.014l-.034.614c0 .012.007.02.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01l-.184-.092Z"/><path fill="currentColor" d="M3 5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5Zm16 0H5v3h14V5ZM5 19v-9h4v9H5Zm6 0h8v-9h-8v9Z"/></g></svg>';
    // https://icon-sets.iconify.design/ri/login-circle-line/
    $icon_login_logout = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="m10.998 16l5-4l-5-4v3h-9v2h9z"/><path fill="currentColor" d="M12.999 2.999a8.938 8.938 0 0 0-6.364 2.637L8.049 7.05c1.322-1.322 3.08-2.051 4.95-2.051s3.628.729 4.95 2.051S20 10.13 20 12s-.729 3.628-2.051 4.95s-3.08 2.051-4.95 2.051s-3.628-.729-4.95-2.051l-1.414 1.414c1.699 1.7 3.959 2.637 6.364 2.637s4.665-.937 6.364-2.637C21.063 16.665 22 14.405 22 12s-.937-4.665-2.637-6.364a8.938 8.938 0 0 0-6.364-2.637z"/></svg>';
    // https://icon-sets.iconify.design/mingcute/code-line/
    $icon_custom_code = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none"><path d="M0 0h24v24H0z"/><path fill="currentColor" d="M14.486 3.143a1 1 0 0 1 .692 1.233l-4.43 15.788a1 1 0 0 1-1.926-.54l4.43-15.788a1 1 0 0 1 1.234-.693ZM7.207 7.05a1 1 0 0 1 0 1.414L3.672 12l3.535 3.535a1 1 0 1 1-1.414 1.415L1.55 12.707a1 1 0 0 1 0-1.414L5.793 7.05a1 1 0 0 1 1.414 0Zm9.586 1.414a1 1 0 1 1 1.414-1.414l4.243 4.243a1 1 0 0 1 0 1.414l-4.243 4.242a1 1 0 0 1-1.414-1.414L20.328 12l-3.535-3.536Z"/></g></svg>';
    // https://icon-sets.iconify.design/mdi/forbid/
    $icon_disable_components = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2c5.5 0 10 4.5 10 10s-4.5 10-10 10S2 17.5 2 12S6.5 2 12 2m0 2c-1.9 0-3.6.6-4.9 1.7l11.2 11.2c1-1.4 1.7-3.1 1.7-4.9c0-4.4-3.6-8-8-8m4.9 14.3L5.7 7.1C4.6 8.4 4 10.1 4 12c0 4.4 3.6 8 8 8c1.9 0 3.6-.6 4.9-1.7Z"/></svg>';
    // https://icon-sets.iconify.design/jam/shield-check/
    $icon_security = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 16 16"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="m8 1.75l5.25 2v5c0 2.25-2 4.5-5.25 5.5c-3.25-1-5.25-3-5.25-5.5v-5z"/><path d="m5.75 7.75l1.5 1.5l3-3.5"/></g></svg>';
    // https://icon-sets.iconify.design/streamline/image-flash-1-flash-power-connect-charge-electricity-lightning/
    $icon_optimizations = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 14 14"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M4.25.5L2 5.81a.5.5 0 0 0 .46.69h2.79l-2 7l8.59-8.14a.5.5 0 0 0-.34-.86H7.75l2-4Z"/></svg>';
    // https://icon-sets.iconify.design/iconoir/tools/
    $icon_utilities = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"><path d="m10.05 10.607l-7.07 7.07a2 2 0 0 0 0 2.83v0a2 2 0 0 0 2.828 0l7.07-7.072m4.315.365l3.878 3.878a2 2 0 0 1 0 2.828v0a2 2 0 0 1-2.828 0l-6.209-6.208M6.733 5.904L4.61 6.61L2.49 3.075l1.414-1.414L7.44 3.782l-.707 2.122Zm0 0l2.83 2.83"/><path d="M10.05 10.607c-.844-2.153-.679-4.978 1.061-6.718c1.74-1.74 4.95-2.121 6.717-1.06l-3.04 3.04l-.283 3.111l3.111-.282l3.04-3.041c1.062 1.768.68 4.978-1.06 6.717c-1.74 1.74-4.564 1.905-6.717 1.061"/></g></svg>';
    ?>
					    <input id="tab-content-management" type="radio" name="tabs" checked><label for="tab-content-management"><?php 
    echo  wp_kses( $icon_content_management, get_kses_with_svg_ruleset() ) ;
    ?>Content Management</label>
					    <input id="tab-admin-interface" type="radio" name="tabs"><label for="tab-admin-interface"><?php 
    echo  wp_kses( $icon_admin_interface, get_kses_with_svg_ruleset() ) ;
    ?>Admin Interface</label>
					    <input id="tab-login-logout" type="radio" name="tabs"><label for="tab-login-logout"><?php 
    echo  wp_kses( $icon_login_logout, get_kses_with_svg_ruleset() ) ;
    ?>Log In | Log Out</label>
					    <input id="tab-custom-code" type="radio" name="tabs"><label for="tab-custom-code"><?php 
    echo  wp_kses( $icon_custom_code, get_kses_with_svg_ruleset() ) ;
    ?>Custom Code</label>
					    <input id="tab-disable-components" type="radio" name="tabs"><label for="tab-disable-components"><?php 
    echo  wp_kses( $icon_disable_components, get_kses_with_svg_ruleset() ) ;
    ?>Disable Components</label>
					    <input id="tab-security" type="radio" name="tabs"><label for="tab-security"><?php 
    echo  wp_kses( $icon_security, get_kses_with_svg_ruleset() ) ;
    ?>Security</label>
					    <input id="tab-optimizations" type="radio" name="tabs"><label for="tab-optimizations"><?php 
    echo  wp_kses( $icon_optimizations, get_kses_with_svg_ruleset() ) ;
    ?>Optimizations</label>
					    <input id="tab-utilities" type="radio" name="tabs"><label for="tab-utilities"><?php 
    echo  wp_kses( $icon_utilities, get_kses_with_svg_ruleset() ) ;
    ?>Utilities</label>
					</div>
					<div class="asenha-tab-contents">
					    <section class="asenha-fields fields-content-management"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-admin-interface"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-login-logout"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-custom-code"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-disable-components"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-security"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-optimizations"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					    <section class="asenha-fields fields-utilities"> 
					    	<table class="form-table" role="presentation">
					    		<tbody></tbody>
					    	</table>
					    </section>
					</div>
				</div>
				<div style="display:none;"><!-- Hide to prevent flash of fields appearing at the bottom of the page -->
					<?php 
    settings_fields( ASENHA_ID );
    ?>
					<?php 
    do_settings_sections( ASENHA_SLUG );
    ?>
					<?php 
    submit_button(
        'Save Changes',
        // Button copy
        'primary',
        // Type: 'primary', 'small', or 'large'
        'submit',
        // The 'name' attribute
        true,
        // Whether to wrap in <p> tag
        array(
            'id' => 'asenha-submit',
        )
    );
    ?>
				</div>
			</form>
            <?php 
    ?>
			<div id="bottom-upgrade-nudge" class="asenha-upgrade-nudge-bottom" style="display:none;">
				<div class="asenha-upgrade-nudge-bottom__message">Do more with <a href="https://www.wpase.com/upgrade-ndg-btm" target="_blank">ASE Pro</a>. Lifetime deal (LTD) <a href="https://www.wpase.com/upgrade-ndg-btm-prc" target="_blank">available</a>.</div>
			</div>
			<?php 
    ?>
		</div>

		<?php 
    ?>

	</div>
	<?php 
    // Record the number of times changes were saved as well as the date of last save
    $asenha_stats = get_option( ASENHA_SLUG_U . '_stats', array() );
    $changes_saved = ( isset( $_GET['settings-updated'] ) && 'true' == $_GET['settings-updated'] ? true : false );
    
    if ( $changes_saved ) {
        $current_date = date( 'Y-m-d', time() );
        
        if ( !isset( $asenha_stats['first_save_date'] ) ) {
            $asenha_stats['first_save_date'] = $current_date;
            $asenha_stats['last_save_date'] = $current_date;
            $asenha_stats['save_count'] = 1;
            $asenha_stats['have_sponsored'] = false;
            $asenha_stats['sponsorship_nudge_dismissed'] = false;
            $asenha_stats['sponsorship_nudge_last_shown_date'] = '';
            $asenha_stats['sponsorship_nudge_last_shown_save_count'] = 0;
        } else {
            $asenha_stats['last_save_date'] = $current_date;
            $save_count = $asenha_stats['save_count'];
            $save_count++;
            $asenha_stats['save_count'] = $save_count;
        }
        
        update_option( ASENHA_SLUG_U . '_stats', $asenha_stats );
    }

}

/**
 * Suppress all notices, then add notice for successful settings update
 *
 * @since 1.1.0
 */
function asenha_suppress_add_notices()
{
    global  $plugin_page ;
    // Suppress all notices
    if ( ASENHA_SLUG === $plugin_page ) {
        remove_all_actions( 'admin_notices' );
    }
    // Add notice for successful settings update
    if ( isset( $_GET['page'] ) && ASENHA_SLUG == $_GET['page'] && isset( $_GET['settings-updated'] ) && true == $_GET['settings-updated'] ) {
        ?>
			<script>
				jQuery(document).ready( function() {
					jQuery('.asenha-changes-saved').fadeIn(400).delay(2500).fadeOut(400);
				});
			</script>

		<?php 
    }
}

/**
 * Suppress all generic notices on the plugin settings page
 *
 * @since 2.7.0
 */
function asenha_suppress_generic_notices()
{
    global  $plugin_page ;
    // Suppress all notices
    if ( ASENHA_SLUG === $plugin_page ) {
        remove_all_actions( 'all_admin_notices' );
    }
}

/**
 * Enqueue admin scripts
 *
 * @since 1.0.0
 */
function asenha_admin_scripts( $hook_suffix )
{
    global 
        $wp_version,
        $pagenow,
        $typenow,
        $taxnow,
        $hook_suffix,
        $current_user
    ;
    $current_screen = get_current_screen();
    // Get all WP Enhancements options, default to empty array in case it's not been created yet
    $options = get_option( 'admin_site_enhancements', array() );
    // For main page of this plugin
    
    if ( is_asenha() ) {
        wp_enqueue_style(
            'asenha-jbox',
            ASENHA_URL . 'assets/css/jBox.all.min.css',
            array(),
            ASENHA_VERSION
        );
        wp_enqueue_script(
            'asenha-jbox',
            ASENHA_URL . 'assets/js/jBox.all.min.js',
            array(),
            ASENHA_VERSION,
            false
        );
        wp_enqueue_script(
            'asenha-jsticky',
            ASENHA_URL . 'assets/js/jquery.jsticky.mod.min.js',
            array( 'jquery' ),
            ASENHA_VERSION,
            false
        );
        wp_enqueue_script(
            'asenha-js-cookie',
            ASENHA_URL . 'assets/js/js.cookie.min.js',
            array(),
            ASENHA_VERSION,
            false
        );
        // jQuery UI Sortables. In use, e.g. for Admin Interface >> Admin Menu Organizer
        // Re-register and re-enqueue jQuery UI Core and plugins required for sortable, draggable and droppable when ordering menu items
        wp_deregister_script( 'jquery-ui-core' );
        wp_register_script(
            'jquery-ui-core',
            get_site_url() . '/wp-includes/js/jquery/ui/core.min.js',
            array( 'jquery' ),
            ASENHA_VERSION,
            false
        );
        wp_enqueue_script( 'jquery-ui-core' );
        
        if ( version_compare( $wp_version, '5.6.0', '>=' ) ) {
            wp_deregister_script( 'jquery-ui-mouse' );
            wp_register_script(
                'jquery-ui-mouse',
                get_site_url() . '/wp-includes/js/jquery/ui/mouse.min.js',
                array( 'jquery-ui-core' ),
                ASENHA_VERSION,
                false
            );
            wp_enqueue_script( 'jquery-ui-mouse' );
        } else {
            wp_deregister_script( 'jquery-ui-widget' );
            wp_register_script(
                'jquery-ui-widget',
                get_site_url() . '/wp-includes/js/jquery/ui/widget.min.js',
                array( 'jquery' ),
                ASENHA_VERSION,
                false
            );
            wp_enqueue_script( 'jquery-ui-widget' );
            wp_deregister_script( 'jquery-ui-mouse' );
            wp_register_script(
                'jquery-ui-mouse',
                get_site_url() . '/wp-includes/js/jquery/ui/mouse.min.js',
                array( 'jquery-ui-core', 'jquery-ui-widget' ),
                ASENHA_VERSION,
                false
            );
            wp_enqueue_script( 'jquery-ui-mouse' );
        }
        
        wp_deregister_script( 'jquery-ui-sortable' );
        wp_register_script(
            'jquery-ui-sortable',
            get_site_url() . '/wp-includes/js/jquery/ui/sortable.min.js',
            array( 'jquery-ui-mouse' ),
            ASENHA_VERSION,
            false
        );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_deregister_script( 'jquery-ui-draggable' );
        wp_register_script(
            'jquery-ui-draggable',
            get_site_url() . '/wp-includes/js/jquery/ui/draggable.min.js',
            array( 'jquery-ui-mouse' ),
            ASENHA_VERSION,
            false
        );
        wp_enqueue_script( 'jquery-ui-draggable' );
        wp_deregister_script( 'jquery-ui-droppable' );
        wp_register_script(
            'jquery-ui-droppable',
            get_site_url() . '/wp-includes/js/jquery/ui/droppable.min.js',
            array( 'jquery-ui-draggable' ),
            ASENHA_VERSION,
            false
        );
        wp_enqueue_script( 'jquery-ui-droppable' );
        // Script to set behaviour and actions of the sortable menu
        wp_enqueue_script(
            'asenha-custom-admin-menu',
            ASENHA_URL . 'assets/js/custom-admin-menu.js',
            array( 'jquery-ui-draggable' ),
            ASENHA_VERSION,
            false
        );
        // First, we unload the CodeMirror libraries included in WP core
        wp_deregister_script( 'wp-codemirror' );
        wp_deregister_script( 'code-editor' );
        wp_deregister_script( 'htmlhint' );
        wp_deregister_script( 'csslint' );
        wp_deregister_script( 'esprima' );
        wp_deregister_script( 'jshint' );
        // Then, we load ASENHA's CodeMirror libraries. In use, e.g. for Utilities >> Enable Custom Admin / Frontend CSS / ads.txt / app-ads.txt
        wp_enqueue_style(
            'asenha-codemirror',
            ASENHA_URL . 'assets/css/codemirror/codemirror.min.css',
            array(),
            ASENHA_VERSION
        );
        wp_enqueue_script(
            'asenha-codemirror',
            ASENHA_URL . 'assets/js/codemirror/codemirror.min.js',
            array( 'jquery' ),
            ASENHA_VERSION,
            true
        );
        wp_enqueue_script(
            'asenha-codemirror-htmlmixed-mode',
            ASENHA_URL . 'assets/js/codemirror/htmlmixed.js',
            array( 'asenha-codemirror' ),
            ASENHA_VERSION,
            true
        );
        wp_enqueue_script(
            'asenha-codemirror-xml-mode',
            ASENHA_URL . 'assets/js/codemirror/xml.js',
            array( 'asenha-codemirror' ),
            ASENHA_VERSION,
            true
        );
        wp_enqueue_script(
            'asenha-codemirror-javascript-mode',
            ASENHA_URL . 'assets/js/codemirror/javascript.js',
            array( 'asenha-codemirror' ),
            ASENHA_VERSION,
            true
        );
        wp_enqueue_script(
            'asenha-codemirror-css-mode',
            ASENHA_URL . 'assets/js/codemirror/css.js',
            array( 'asenha-codemirror' ),
            ASENHA_VERSION,
            true
        );
        wp_enqueue_script(
            'asenha-codemirror-markdown-mode',
            ASENHA_URL . 'assets/js/codemirror/markdown.js',
            array( 'asenha-codemirror' ),
            ASENHA_VERSION,
            true
        );
        // DataTables. In use, e.g. for Security >> Limit Login Attempts
        wp_enqueue_style(
            'asenha-datatables',
            ASENHA_URL . 'assets/css/datatables/datatables.min.css',
            array(),
            ASENHA_VERSION
        );
        wp_enqueue_script(
            'asenha-datatables',
            ASENHA_URL . 'assets/js/datatables/datatables.min.js',
            array( 'jquery' ),
            ASENHA_VERSION,
            false
        );
        // Add WP media library assets
        wp_enqueue_media();
        // Add WP color picker assets
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        // We force loading the uncompressed version of TinyMCE. This ensures we load 'wp-tinymce-root' and then 'wp-tinymce',
        // which prevents issue where the Visual tab is unusable in some scenarios
        $wp_scripts = wp_scripts();
        $wp_scripts->remove( 'wp-tinymce' );
        wp_register_tinymce_scripts( $wp_scripts, true );
        // Main style and script for the admin page
        wp_enqueue_style(
            'asenha-admin-page',
            ASENHA_URL . 'assets/css/admin-page.css',
            array(
            'asenha-jbox',
            'asenha-codemirror',
            'asenha-datatables',
            'wp-color-picker'
        ),
            ASENHA_VERSION
        );
        wp_enqueue_script(
            'asenha-admin-page',
            ASENHA_URL . 'assets/js/admin-page.js',
            array(
            'asenha-jsticky',
            'asenha-jbox',
            'asenha-js-cookie',
            'asenha-codemirror-htmlmixed-mode',
            'asenha-codemirror-xml-mode',
            'asenha-codemirror-javascript-mode',
            'asenha-codemirror-css-mode',
            'asenha-codemirror-markdown-mode',
            'asenha-datatables',
            'asenha-custom-admin-menu',
            'wp-color-picker',
            'wp-mediaelement',
            'wp-tinymce-root',
            'wp-tinymce'
        ),
            ASENHA_VERSION,
            false
        );
        wp_localize_script( 'asenha-admin-page', 'adminPageVars', array(
            'mediaFrameTitle'      => 'Select an Image',
            'mediaFrameButtonText' => 'Use Selected Image',
            'resetMenuNonce'       => wp_create_nonce( 'reset-menu-nonce' ),
        ) );
    }
    
    // Enqueue on all wp-admin
    wp_enqueue_style(
        'asenha-wp-admin',
        ASENHA_URL . 'assets/css/wp-admin.css',
        array(),
        ASENHA_VERSION
    );
    // Content Management >> Show IDs, for list tables in wp-admin, e.g. All Posts page
    if ( false !== strpos( $current_screen->base, 'edit' ) || false !== strpos( $current_screen->base, 'users' ) || false !== strpos( $current_screen->base, 'upload' ) ) {
        wp_enqueue_style(
            'asenha-list-table',
            ASENHA_URL . 'assets/css/list-table.css',
            array(),
            ASENHA_VERSION
        );
    }
    // Content Management >> Enable Media Replacement
    
    if ( $current_screen->base == 'upload' || $current_screen->id == 'attachment' ) {
        // wp_enqueue_style( 'asenha-jbox', ASENHA_URL . 'assets/css/jBox.all.min.css', array(), ASENHA_VERSION );
        // wp_enqueue_script( 'asenha-jbox', ASENHA_URL . 'assets/js/jBox.all.min.js', array(), ASENHA_VERSION, false );
        wp_enqueue_style(
            'asenha-media-replace',
            ASENHA_URL . 'assets/css/media-replace.css',
            array(),
            ASENHA_VERSION
        );
        wp_enqueue_script(
            'asenha-media-replace',
            ASENHA_URL . 'assets/js/media-replace.js',
            array(),
            ASENHA_VERSION,
            false
        );
    }
    
    // Utilities >> Image Sizes Panel
    
    if ( 'post' == $current_screen->base && 'attachment' == $current_screen->id ) {
        global  $post ;
        // Only enqueue if the attachment is an image
        if ( property_exists( $post, 'post_mime_type' ) && false !== strpos( $post->post_mime_type, 'image' ) ) {
            wp_enqueue_style( 'asenha-image-sizes-panel', ASENHA_URL . 'assets/css/image-sizes-panel.css' );
        }
    }
    
    // Content Management >> Hide Admin Notices
    
    if ( array_key_exists( 'hide_admin_notices', $options ) && $options['hide_admin_notices'] ) {
        $hide_for_nonadmins = ( isset( $options['hide_admin_notices_for_nonadmins'] ) ? $options['hide_admin_notices_for_nonadmins'] : false );
        $minimum_capability = 'manage_options';
        if ( function_exists( 'bwasenha_fs' ) ) {
            if ( $hide_for_nonadmins && bwasenha_fs()->can_use_premium_code__premium_only() ) {
                $minimum_capability = 'read';
            }
        }
        
        if ( current_user_can( $minimum_capability ) ) {
            wp_enqueue_style(
                'asenha-jbox',
                ASENHA_URL . 'assets/css/jBox.all.min.css',
                array(),
                ASENHA_VERSION
            );
            wp_enqueue_script(
                'asenha-jbox',
                ASENHA_URL . 'assets/js/jBox.all.min.js',
                array(),
                ASENHA_VERSION,
                false
            );
            wp_enqueue_style(
                'asenha-hide-admin-notices',
                ASENHA_URL . 'assets/css/hide-admin-notices.css',
                array(),
                ASENHA_VERSION
            );
            wp_enqueue_script(
                'asenha-hide-admin-notices',
                ASENHA_URL . 'assets/js/hide-admin-notices.js',
                array( 'asenha-jbox' ),
                ASENHA_VERSION,
                false
            );
        }
    
    }
    
    // Utilities >> Multiple User Roles
    if ( array_key_exists( 'multiple_user_roles', $options ) && $options['multiple_user_roles'] ) {
        if ( 'user-edit.php' == $hook_suffix || 'user-new.php' == $hook_suffix ) {
            // Only replace roles dropdown with checkboxes for users that can assign roles to other users, e.g. administrators
            if ( current_user_can( 'promote_users', get_current_user_id() ) ) {
                wp_enqueue_script(
                    'asenha-multiple-user-roles',
                    ASENHA_URL . 'assets/js/multiple-user-roles.js',
                    array( 'jquery' ),
                    ASENHA_VERSION,
                    false
                );
            }
        }
    }
    // Pass on ASENHA stats to admin-page.js to determine whether to show sponsorship nudge
    $asenha_stats = get_option( ASENHA_SLUG_U . '_stats', array() );
    $current_date = date( 'Y-m-d', time() );
    $show_sponsorship_nudge = false;
    $hide_upgrade_nudge = false;
    $asenha_stats_localized = array(
        'firstSaveDate'        => '',
        'lastSaveDate'         => '',
        'saveCount'            => 0,
        'hideUpgradeNudge'     => false,
        'showSponsorshipNudge' => false,
        'saveChangesJsonpUrl'  => 'https://bowo.io/asenha-save-btn',
    );
    
    if ( !empty($asenha_stats) ) {
        $hide_upgrade_nudge = ( isset( $asenha_stats['upgrade_nudge_dismissed'] ) ? $asenha_stats['upgrade_nudge_dismissed'] : false );
        $have_sponsored = ( isset( $asenha_stats['have_sponsored'] ) ? $asenha_stats['have_sponsored'] : false );
        $changes_saved = ( isset( $_GET['settings-updated'] ) && 'true' == $_GET['settings-updated'] ? true : false );
        $save_count = ( isset( $asenha_stats['save_count'] ) ? $asenha_stats['save_count'] : 0 );
        // Compensate for redirect from settings-updated=true URL
        
        if ( $changes_saved ) {
            $save_count = $save_count + 1;
        } else {
            $save_count = $save_count;
        }
        
        $saves_to_nudge_sponsorship = 10;
        
        if ( $save_count < $saves_to_nudge_sponsorship ) {
            $save_count_modulo = -1;
        } else {
            $save_count_modulo = $save_count % $saves_to_nudge_sponsorship;
        }
        
        // User have not sponsored ASE
        if ( false === $have_sponsored ) {
            // Sponsorship nudge have not been dismissed
            
            if ( false === $asenha_stats['sponsorship_nudge_dismissed'] ) {
                // Show sponsorship nudge after every x saves
                
                if ( $save_count_modulo >= 0 ) {
                    $show_sponsorship_nudge = true;
                } else {
                    $show_sponsorship_nudge = false;
                }
                
                
                if ( $show_sponsorship_nudge && $save_count_modulo >= 0 ) {
                    $asenha_stats['sponsorship_nudge_last_shown_date'] = $current_date;
                    $asenha_stats['sponsorship_nudge_last_shown_save_count'] = $save_count;
                    update_option( ASENHA_SLUG_U . '_stats', $asenha_stats );
                }
            
            } else {
                
                if ( $save_count_modulo == 0 ) {
                    
                    if ( $save_count > $asenha_stats['sponsorship_nudge_last_shown_save_count'] ) {
                        $asenha_stats['sponsorship_nudge_dismissed'] = false;
                        update_option( ASENHA_SLUG_U . '_stats', $asenha_stats );
                        $show_sponsorship_nudge = true;
                    } else {
                        $show_sponsorship_nudge = false;
                    }
                
                } else {
                    $show_sponsorship_nudge = false;
                }
            
            }
        
        }
        $first_save_date = ( isset( $asenha_stats['first_save_date'] ) ? $asenha_stats['first_save_date'] : '' );
        $last_save_date = ( isset( $asenha_stats['last_save_date'] ) ? $asenha_stats['last_save_date'] : '' );
        $asenha_stats_localized = array(
            'firstSaveDate'        => $first_save_date,
            'lastSaveDate'         => $last_save_date,
            'saveCount'            => $save_count,
            'hideUpgradeNudge'     => $hide_upgrade_nudge,
            'showSponsorshipNudge' => $show_sponsorship_nudge,
            'saveChangesJsonpUrl'  => 'https://bowo.io/asenha-save-btn',
        );
    }
    
    wp_localize_script( 'asenha-admin-page', 'asenhaStats', $asenha_stats_localized );
}

/**
 * Dequeue scripts that prevents ASE settings page from working properly. Usually from plugins.
 * 
 * @since 6.3.3
 */
function asenha_dequeue_scritps()
{
    // https://wordpress.org/plugins/user-activity-log/
    wp_dequeue_script( 'chats-js' );
    wp_dequeue_script( 'custom_wp_admin_js' );
    // https://wordpress.org/plugins/print-invoices-packing-slip-labels-for-woocommerce/
    wp_dequeue_script( 'print-invoices-packing-slip-labels-for-woocommerce' );
    wp_dequeue_script( 'print-invoices-packing-slip-labels-for-woocommerce-form-wizard' );
    // https://wordpress.org/plugins/wp-reading-progress/
    wp_dequeue_script( 'ruigehond006_admin_javascript' );
    // WordPress Mentions Légales plugin v1.2.3 by Jean-Baptiste Aramendy - http://jba-development.fr/
    wp_dequeue_script( 'jquery-ui' );
    wp_dequeue_script( 'wordpress-mentions-legales' );
    // https://wordpress.org/plugins/us-weather-widget-willyweather/
    wp_dequeue_script( 'self' );
    // iThemes Security Pro / Solid Security Pro
    wp_dequeue_script( 'itsec-core-admin-notices' );
}

/**
 * Enqueue public scripts
 *
 * @since 3.9.0
 */
function asenha_public_scripts( $hook_suffix )
{
    // Get all WP Enhancements options, default to empty array in case it's not been created yet
    $options = get_option( 'admin_site_enhancements', array() );
    // External Permalinks
    $enable_external_permalinks = ( array_key_exists( 'enable_external_permalinks', $options ) ? $options['enable_external_permalinks'] : false );
    
    if ( $enable_external_permalinks ) {
        wp_enqueue_script(
            'asenha-public',
            ASENHA_URL . 'assets/js/external-permalinks.js',
            array(),
            ASENHA_VERSION,
            false
        );
        wp_localize_script( 'asenha-public', 'phpVars', array(
            'externalPermalinksEnabled' => $enable_external_permalinks,
        ) );
    }
    
    // Media Categories
    $enable_media_categories = ( array_key_exists( 'enable_media_categories', $options ) ? $options['enable_media_categories'] : false );
    if ( $enable_media_categories && !is_admin() && is_user_logged_in() ) {
        wp_enqueue_style(
            'asenha-media-categories-frontend',
            ASENHA_URL . 'assets/css/media-categories-frontend.css',
            array(),
            ASENHA_VERSION
        );
    }
    // Media Replacement
    $enable_media_replacement = ( array_key_exists( 'enable_media_replacement', $options ) ? $options['enable_media_replacement'] : false );
    if ( $enable_media_replacement && is_user_logged_in() ) {
        wp_enqueue_style(
            'asenha-media-replace-frontend',
            ASENHA_URL . 'assets/css/media-replace-frontend.css',
            array(),
            ASENHA_VERSION
        );
    }
}

/**
 * Add admin bar styles for wp-admin and frontend
 * 
 * @since 6.2.1
 */
function asenha_admin_bar_item_js_css()
{
    if ( is_user_logged_in() ) {
        ?>
		<!--<script></script>-->
		<style>
		#wp-admin-bar-user-info .avatar {
		    object-fit: cover;    
		}
		</style>
		<?php 
    }
}

/**
 * Add 'Access now' plugin action link.
 *
 * @since    1.0.0
 */
function asenha_plugin_action_links( $links )
{
    $settings_link = '<a href="tools.php?page=' . ASENHA_SLUG . '">Configure</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

/**
 * Modify footer text
 *
 * @since 1.0.0
 */
function asenha_footer_text()
{
    // Show nothing
    ?>
	<?php 
}

/**
 * Change WP version number text in footer
 * 
 * @since 4.8.3
 */
function asenha_footer_version_text()
{
    ?>
		ASE <a href="https://www.wpase.com/documentation/changelog/" target="_blank">v<?php 
    echo  esc_html( ASENHA_VERSION ) ;
    ?></a>
	<?php 
}

/**
 * Check if current screen is this plugin's main page
 *
 * @since 1.0.0
 */
function is_asenha()
{
    $request_uri = sanitize_text_field( $_SERVER['REQUEST_URI'] );
    // e.g. /wp-admin/index.php?page=page-slug
    
    if ( strpos( $request_uri, 'page=' . ASENHA_SLUG ) !== false ) {
        return true;
        // Yes, this is the plugin's main page
    } else {
        return false;
        // Nope, this is NOT the plugin's page
    }

}

/**
 * Mark that user have sponsored ASE
 * 
 * @since 5.2.7
 */
function asenha_have_sponsored()
{
    
    if ( isset( $_REQUEST ) ) {
        $asenha_stats = get_option( ASENHA_SLUG_U . '_stats', array() );
        $asenha_stats['have_sponsored'] = true;
        $asenha_stats['sponsorship_nudge_dismissed'] = true;
        $success = update_option( ASENHA_SLUG_U . '_stats', $asenha_stats );
        
        if ( $success ) {
            echo  json_encode( array(
                'success' => true,
            ) ) ;
        } else {
            echo  json_encode( array(
                'success' => false,
            ) ) ;
        }
    
    }

}

/**
 * Dismiss sponsorship nudge
 * 
 * @since 5.8.2
 */
function asenha_dismiss_upgrade_nudge()
{
    
    if ( isset( $_REQUEST ) ) {
        $asenha_stats = get_option( ASENHA_SLUG_U . '_stats', array() );
        $asenha_stats['upgrade_nudge_dismissed'] = true;
        $success = update_option( ASENHA_SLUG_U . '_stats', $asenha_stats );
        
        if ( $success ) {
            echo  json_encode( array(
                'success' => true,
            ) ) ;
        } else {
            echo  json_encode( array(
                'success' => false,
            ) ) ;
        }
    
    }

}

/**
 * Dismiss sponsorship nudge
 * 
 * @since 5.2.7
 */
function asenha_dismiss_sponsorship_nudge()
{
    
    if ( isset( $_REQUEST ) ) {
        $asenha_stats = get_option( ASENHA_SLUG_U . '_stats', array() );
        $asenha_stats['sponsorship_nudge_dismissed'] = true;
        $success = update_option( ASENHA_SLUG_U . '_stats', $asenha_stats );
        
        if ( $success ) {
            echo  json_encode( array(
                'success' => true,
            ) ) ;
        } else {
            echo  json_encode( array(
                'success' => false,
            ) ) ;
        }
    
    }

}
