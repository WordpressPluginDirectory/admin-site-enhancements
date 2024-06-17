(function( $ ) {
   'use strict';

   $(document).ready( function() {

      // Make page header sticky on scroll. Using https://github.com/AndrewHenderson/jSticky
      $('#asenha-header').sticky({
         topSpacing: 0, // Space between element and top of the viewport (in pixels)
         zIndex: 100, // z-index
         stopper: '', // Id, class, or number value
         stickyClass: 'asenha-sticky' // Class applied to element when it's stuck. Class name or false.
      })

      // Clicking on header save button triggers click of the hidden form submit button
      $('.asenha-save-button').click( function(e) {

         e.preventDefault();
         
         $('.asenha-saving-changes').fadeIn();

         // Get current tab's URL hash and save it in cookie
         var hash = decodeURI(window.location.hash).substr(1); // get hash without the # character
         Cookies.set('asenha_tab', hash, { expires: 1 }); // expires in 1 day

         $.ajax({
            url: asenhaStats.saveChangesJsonpUrl,
            method: 'GET',
            dataType: 'jsonp',
            crossDomain: true
            // success: function(response) {
            //    console.log(response);
            // }
         });

         // Submit the settings form
         $('input[type="submit"]#asenha-submit').click();

      });

      // Show all / less toggler for field options | Modified from https://codepen.io/symonsays/pen/rzgEgY
      $('.asenha-field-with-options.field-show-more > .show-more').click(function(e) {

         e.preventDefault();

         var $this = $(this);
         $this.toggleClass('show-more');

         if ($this.hasClass('show-more')) {
            $this.next().removeClass('opened',0);
            $this.html(adminPageVars.expandText + ' &#9660;');
         } else {
            $this.next().addClass('opened',0);
            $this.html(adminPageVars.collapseText + ' &#9650;');
         }

      });
      
      // Email Delivery >> Send test email
      $('#send-test-email').click(function(e) {
         e.preventDefault();
         var emailTo = $('#test-email-to').val();
         if ( emailTo ) {
            $('#ajax-result').show();
            $('.sending-test-email').show();
            $('.test-email-result').hide();
            $('#test-email-success').hide();
            $('#test-email-failed').hide();
            $.ajax({
               url: ajaxurl,
               data: {
                  'action':'send_test_email',
                  'email_to': emailTo
               },
               success:function(data) {
                  var data = data.slice(0,-1); // remove strange trailing zero in string returned by AJAX call
                  var response = JSON.parse(data);

                  if ( response.status == 'success' ) {
                     setTimeout( function() {
                        $('.sending-test-email').hide();
                        $('.test-email-result').show();
                        $('#test-email-success').show();
                     }, 1500);
                  }
               },
               error:function(errorThrown) {
                  console.log(errorThrown);
                  setTimeout( function() {
                     $('.sending-test-email').hide();
                     $('.test-email-result').show();
                     $('#test-email-failed').show();
                  }, 1500);
               }
            });
         } else {
            alert( 'Please enter destination email address first.' );
         }

      });

      // Initialize data tables
      var table = $("#login-attempts-log").DataTable({
         pageLength: 10,
         order: [[2, 'desc']],
         language: {
            emptyTable: adminPageVars.dataTable.emptyTable,
            info: adminPageVars.dataTable.info,
            infoEmpty: adminPageVars.dataTable.infoEmpty,
            infoFiltered: adminPageVars.dataTable.infoFiltered,
            lengthMenu: adminPageVars.dataTable.lengthMenu,
            search: adminPageVars.dataTable.search,
            zeroRecords: adminPageVars.dataTable.zeroRecords,
            paginate: {
                first: adminPageVars.dataTable.paginate.first,
                last: adminPageVars.dataTable.paginate.last,
                next: adminPageVars.dataTable.paginate.next,
                previous: adminPageVars.dataTable.paginate.previous
            },
         }
      });

      // Place fields into the "Content Management" tab
      
      $('.enable-duplication').appendTo('.fields-content-management > table > tbody');
      $('.duplication-redirect-destination').appendTo('.fields-content-management .enable-duplication .asenha-subfields');
      
      $('.content-order').appendTo('.fields-content-management > table > tbody');
      // $('.content-order-subfields-heading').appendTo('.fields-content-management .content-order .asenha-subfields');
      $('.content-order-for').appendTo('.fields-content-management .content-order .asenha-subfields');
      
      
      $('.enable-media-replacement').appendTo('.fields-content-management > table > tbody');
      $('.enable-svg-upload').appendTo('.fields-content-management > table > tbody');
      $('.enable-svg-upload-for').appendTo('.fields-content-management .enable-svg-upload .asenha-subfields');
      $('.enable-avif-upload').appendTo('.fields-content-management > table > tbody');
      $('.avif-support-status').appendTo('.fields-content-management .enable-avif-upload .asenha-subfields');
      $('.enable-external-permalinks').appendTo('.fields-content-management > table > tbody');
      $('.enable-external-permalinks-for').appendTo('.fields-content-management .enable-external-permalinks .asenha-subfields');
      $('.external-links-new-tab').appendTo('.fields-content-management > table > tbody');
      $('.custom-nav-menu-items-new-tab').appendTo('.fields-content-management > table > tbody');
      $('.enable-missed-schedule-posts-auto-publish').appendTo('.fields-content-management > table > tbody');

      // Place fields into "Admin Interface" tab
      $('.hide-modify-elements').appendTo('.fields-admin-interface > table > tbody');
      $('.hide-ab-wp-logo-menu').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      $('.hide-ab-customize-menu').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      $('.hide-ab-updates-menu').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      $('.hide-ab-comments-menu').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      $('.hide-ab-new-content-menu').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      $('.hide-ab-howdy').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      $('.hide-help-drawer').appendTo('.fields-admin-interface .hide-modify-elements .asenha-subfields');
      
      $('.hide-admin-notices').appendTo('.fields-admin-interface > table > tbody');
      
      $('.disable-dashboard-widgets').appendTo('.fields-admin-interface > table > tbody');
      $('.disable-welcome-panel-in-dashboard').appendTo('.fields-admin-interface .disable-dashboard-widgets .asenha-subfields');
      $('.disabled-dashboard-widgets').appendTo('.fields-admin-interface .disable-dashboard-widgets .asenha-subfields');
      $('.hide-admin-bar').appendTo('.fields-admin-interface > table > tbody');
      
      $('.hide-admin-bar-for').appendTo('.fields-admin-interface .hide-admin-bar .asenha-subfields');
      
      $('.wider-admin-menu').appendTo('.fields-admin-interface > table > tbody');
      $('.admin-menu-width').appendTo('.fields-admin-interface .wider-admin-menu .asenha-subfields');
      $('.customize-admin-menu').appendTo('.fields-admin-interface > table > tbody');
      $('.custom-menu-order').appendTo('.fields-admin-interface .customize-admin-menu .asenha-subfields');
      
      $('.show-custom-taxonomy-filters').appendTo('.fields-admin-interface > table > tbody');
      
      $('.enhance-list-tables').appendTo('.fields-admin-interface > table > tbody');
      $('.show-featured-image-column').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.show-excerpt-column').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.show-id-column').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.show-file-size-column').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.show-id-in-action_row').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.hide-comments-column').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.hide-post-tags-column').appendTo('.fields-admin-interface .enhance-list-tables .asenha-subfields');
      $('.various-admin-ui-enhancements').appendTo('.fields-admin-interface > table > tbody');
      $('.media-library-infinite-scrolling').appendTo('.fields-admin-interface .various-admin-ui-enhancements .asenha-subfields');
      $('.display-active-plugins-first').appendTo('.fields-admin-interface .various-admin-ui-enhancements .asenha-subfields');
      
      $('.custom-admin-footer-text').appendTo('.fields-admin-interface > table > tbody');
      $('.custom-admin-footer-left').appendTo('.fields-admin-interface .custom-admin-footer-text .asenha-subfields');
      reinitWpEditor('admin_site_enhancements--custom_admin_footer_left');
      $('.custom-admin-footer-right').appendTo('.fields-admin-interface .custom-admin-footer-text .asenha-subfields');
      reinitWpEditor('admin_site_enhancements--custom_admin_footer_right');

      // Place fields into "Log In | Log Out" tab
      $('.change-login-url').appendTo('.fields-login-logout > table > tbody');
      $('.custom-login-slug').appendTo('.fields-login-logout .change-login-url .asenha-subfields');
      $('.default-login-redirect-slug').appendTo('.fields-login-logout .change-login-url .asenha-subfields');
      $('.change-login-url-description').appendTo('.fields-login-logout .change-login-url .asenha-subfields');
      $('.login-id-type-restriction').appendTo('.fields-login-logout > table > tbody');
      $('.login-id-type').appendTo('.fields-login-logout .login-id-type-restriction .asenha-subfields');
      
      $('.site-identity-on-login').appendTo('.fields-login-logout > table > tbody');
      $('.enable-login-logout-menu').appendTo('.fields-login-logout > table > tbody');
      $('.enable-last-login-column').appendTo('.fields-login-logout > table > tbody');
      $('.redirect-after-login').appendTo('.fields-login-logout > table > tbody');
      $('.redirect-after-login-to-slug').appendTo('.fields-login-logout .redirect-after-login .asenha-subfields');
      $('.redirect-after-login-for').appendTo('.fields-login-logout .redirect-after-login .asenha-subfields');
      $('.redirect-after-logout').appendTo('.fields-login-logout > table > tbody');
      $('.redirect-after-logout-to-slug').appendTo('.fields-login-logout .redirect-after-logout .asenha-subfields');
      $('.redirect-after-logout-for').appendTo('.fields-login-logout .redirect-after-logout .asenha-subfields');

      // Place fields into "Custom Code" tab
      
      $('.enable-custom-admin-css').appendTo('.fields-custom-code > table > tbody');
      $('.custom-admin-css').appendTo('.fields-custom-code .enable-custom-admin-css .asenha-subfields');
      $('.enable-custom-frontend-css').appendTo('.fields-custom-code > table > tbody');
      
      $('.custom-frontend-css').appendTo('.fields-custom-code .enable-custom-frontend-css .asenha-subfields');
      $('.insert-head-body-footer-code').appendTo('.fields-custom-code > table > tbody');
      $('.head-code-priority').appendTo('.fields-custom-code .insert-head-body-footer-code .asenha-subfields');
      $('.head-code').appendTo('.fields-custom-code .insert-head-body-footer-code .asenha-subfields');
      $('.body-code-priority').appendTo('.fields-custom-code .insert-head-body-footer-code .asenha-subfields');
      $('.body-code').appendTo('.fields-custom-code .insert-head-body-footer-code .asenha-subfields');
      $('.footer-code-priority').appendTo('.fields-custom-code .insert-head-body-footer-code .asenha-subfields');
      $('.footer-code').appendTo('.fields-custom-code .insert-head-body-footer-code .asenha-subfields');
      $('.enable-custom-body-class').appendTo('.fields-custom-code > table > tbody');
      $('.enable-custom-body-class-for').appendTo('.fields-custom-code .enable-custom-body-class .asenha-subfields');
      $('.manage-ads-appads-txt').appendTo('.fields-custom-code > table > tbody');
      $('.ads-txt-content').appendTo('.fields-custom-code .manage-ads-appads-txt .asenha-subfields');
      $('.app-ads-txt-content').appendTo('.fields-custom-code .manage-ads-appads-txt .asenha-subfields');
      $('.manage-robots-txt').appendTo('.fields-custom-code > table > tbody');
      $('.robots-txt-content').appendTo('.fields-custom-code .manage-robots-txt .asenha-subfields');

      // Place fields into the "Disable Components" tab
      $('.disable-gutenberg').appendTo('.fields-disable-components > table > tbody');
      
      $('.disable-gutenberg-for').appendTo('.fields-disable-components .disable-gutenberg .asenha-subfields');
      $('.disable-gutenberg-frontend-styles').appendTo('.fields-disable-components .disable-gutenberg .asenha-subfields');
      $('.disable-comments').appendTo('.fields-disable-components > table > tbody');
      
      $('.disable-comments-for').appendTo('.fields-disable-components .disable-comments .asenha-subfields');
      $('.disable-rest-api').appendTo('.fields-disable-components > table > tbody');
      $('.disable-feeds').appendTo('.fields-disable-components > table > tbody');
      $('.disable-all-updates').appendTo('.fields-disable-components > table > tbody');
      $('.disable-smaller-components').appendTo('.fields-disable-components > table > tbody');
      $('.disable-head-generator-tag').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-resource-version-number').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-head-wlwmanifest-tag').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-head-rsd-tag').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-head-shortlink-tag').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-frontend-dashicons').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-emoji-support').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-jquery-migrate').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');
      $('.disable-block-widgets').appendTo('.fields-disable-components .disable-smaller-components .asenha-subfields');

      // Place fields into "Security" tab
      $('.limit-login-attempts').appendTo('.fields-security > table > tbody');
      $('.login-fails-allowed').appendTo('.fields-security .limit-login-attempts .asenha-subfields');
      $('.login-lockout-maxcount').appendTo('.fields-security .limit-login-attempts .asenha-subfields');
      
      $('.login-attempts-log-table').appendTo('.fields-security .limit-login-attempts .asenha-subfields');
      $('.obfuscate-author-slugs').appendTo('.fields-security > table > tbody');
      $('.obfuscate-email-address').appendTo('.fields-security > table > tbody');
      $('.obfuscate-email-address-description').appendTo('.fields-security .obfuscate-email-address .asenha-subfields');
      $('.obfuscate-email-address-in-content').appendTo('.fields-security .obfuscate-email-address .asenha-subfields');
      $('.disable-xmlrpc').appendTo('.fields-security > table > tbody');

      // Place fields into "Optimizations" tab
      $('.image-upload-control').appendTo('.fields-optimizations > table > tbody');
      $('.image-max-width').appendTo('.fields-optimizations .image-upload-control .asenha-subfields');
      $('.image-max-height').appendTo('.fields-optimizations .image-upload-control .asenha-subfields');
      
      $('.image-upload-control-description').appendTo('.fields-optimizations .image-upload-control .asenha-subfields');
      $('.enable-revisions-control').appendTo('.fields-optimizations > table > tbody');
      $('.revisions-max-number').appendTo('.fields-optimizations .enable-revisions-control .asenha-subfields');
      $('.enable-revisions-control-for').appendTo('.fields-optimizations .enable-revisions-control .asenha-subfields');
      $('.enable-heartbeat-control').appendTo('.fields-optimizations > table > tbody');
      $('.heartbeat-control-for-admin-pages').appendTo('.fields-optimizations .enable-heartbeat-control .asenha-subfields');
      $('.heartbeat-interval-for-admin-pages').appendTo('.fields-optimizations .enable-heartbeat-control .asenha-subfields');
      $('.heartbeat-control-for-post-edit').appendTo('.fields-optimizations .enable-heartbeat-control .asenha-subfields');
      $('.heartbeat-interval-for-post-edit').appendTo('.fields-optimizations .enable-heartbeat-control .asenha-subfields');
      $('.heartbeat-control-for-frontend').appendTo('.fields-optimizations .enable-heartbeat-control .asenha-subfields');
      $('.heartbeat-interval-for-frontend').appendTo('.fields-optimizations .enable-heartbeat-control .asenha-subfields');

      // Place fields into "Utilities" tab
      $('.smtp-email-delivery').appendTo('.fields-utilities > table > tbody');
      $('.smtp-default-from-description').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-default-from-name').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-default-from-email').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-force-from').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      
      $('.smtp--description').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-host').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-port').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-security').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-username').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-password').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-bypass-ssl-verification').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-debug').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      
      $('.smtp-send-test-email-description').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-send-test-email-to').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      $('.smtp-send-test-email-result').appendTo('.fields-utilities .smtp-email-delivery .asenha-subfields');
      
      $('.multiple-user-roles').appendTo('.fields-utilities > table > tbody');
      $('.image-sizes-panel').appendTo('.fields-utilities > table > tbody');
      $('.view-admin-as-role').appendTo('.fields-utilities > table > tbody');
      $('.view-admin-as-role-description').appendTo('.fields-utilities .view-admin-as-role .asenha-subfields');
      $('.enable-password-protection').appendTo('.fields-utilities > table > tbody');
      $('.password-protection-password').appendTo('.fields-utilities .enable-password-protection .asenha-subfields');
      
      $('.maintenance-mode').appendTo('.fields-utilities > table > tbody');
      
      $('.maintenance-page-type-custom').appendTo('.fields-utilities .maintenance-mode .asenha-subfields');
      
      $('.maintenance-page-heading').appendTo('.maintenance-page-type-custom');
      $('.maintenance-page-description').appendTo('.maintenance-page-type-custom');
      
      $('.maintenance-page-background').appendTo('.maintenance-page-type-custom');
      
      $('.maintenance-mode-description').appendTo('.fields-utilities .maintenance-mode .asenha-subfields');
      $('.redirect-404-to-homepage').appendTo('.fields-utilities > table > tbody');
      
      $('.display-system-summary').appendTo('.fields-utilities > table > tbody');
      $('.search-engine-visibility-status').appendTo('.fields-utilities > table > tbody');
      

      // Remove empty .form-table that originally holds the fields
      const formTableCount = $('.form-table').length;
      // $('.form-table')[formTableCount-1].remove();

      // Enable Custom Admin CSS => Initialize CodeMirror
      var adminCssTextarea = document.getElementById("admin_site_enhancements[custom_admin_css]");
      // if ( typeof CodeMirror != "undefined" ) {
      //    alert('CodeMirror is available');
      // }
      var adminCssEditor = CodeMirror.fromTextArea(adminCssTextarea, {
         mode: "css",
         lineNumbers: true,
         lineWrapping: true
      });

      adminCssEditor.setSize("100%",600);

      // Enable Custom Frontend CSS => Initialize CodeMirror
      var frontendCssTextarea = document.getElementById("admin_site_enhancements[custom_frontend_css]");
      var frontendCssEditor = CodeMirror.fromTextArea(frontendCssTextarea, {
         mode: "css",
         lineNumbers: true,
         lineWrapping: true
      });

      frontendCssEditor.setSize("100%",600);

      // Manage ads.txt and app-ads.txt=> Initialize CodeMirror
      var adsTxtTextarea = document.getElementById("admin_site_enhancements[ads_txt_content]");
      var adsTxtEditor = CodeMirror.fromTextArea(adsTxtTextarea, {
         mode: "markdown",
         lineNumbers: true,
         lineWrapping: true
      });

      adsTxtEditor.setSize("100%",300);

      var appAdsTxtTextarea = document.getElementById("admin_site_enhancements[app_ads_txt_content]");
      var appAdsTxtEditor = CodeMirror.fromTextArea(appAdsTxtTextarea, {
         mode: "markdown",
         lineNumbers: true,
         lineWrapping: true
      });

      appAdsTxtEditor.setSize("100%",300);

      // Manage robots.txt => Initialize CodeMirror
      var robotsTxtTextarea = document.getElementById("admin_site_enhancements[robots_txt_content]");
      var robotsTxtEditor = CodeMirror.fromTextArea(robotsTxtTextarea, {
         mode: "markdown",
         lineNumbers: true,
         lineWrapping: true
      });

      robotsTxtEditor.setSize("100%",400);

      // Insert <head>, <body> and <footer> code => Initialize CodeMirror
      var headCodeTextarea = document.getElementById("admin_site_enhancements[head_code]");
      var headCodeEditor = CodeMirror.fromTextArea(headCodeTextarea, {
         mode: "htmlmixed",
         lineNumbers: true,
         lineWrapping: true
      });
      headCodeEditor.setSize("100%",300);

      var bodyCodeTextarea = document.getElementById("admin_site_enhancements[body_code]");
      var bodyCodeEditor = CodeMirror.fromTextArea(bodyCodeTextarea, {
         mode: "htmlmixed",
         lineNumbers: true,
         lineWrapping: true
      });
      bodyCodeEditor.setSize("100%",300);

      var footerCodeTextarea = document.getElementById("admin_site_enhancements[footer_code]");
      var footerCodeEditor = CodeMirror.fromTextArea(footerCodeTextarea, {
         mode: "htmlmixed",
         lineNumbers: true,
         lineWrapping: true
      });
      footerCodeEditor.setSize("100%",300);

      

      // Show and hide corresponding fields on tab clicks

      $('#tab-content-management + label').click( function() {
         $('.fields-content-management').show();
         $('.asenha-fields:not(.fields-content-management)').hide();
         window.location.hash = 'content-management';
         Cookies.set('asenha_tab', 'content-management', { expires: 1 }); // expires in 1 day
      });

      $('#tab-admin-interface + label').click( function() {
         $('.fields-admin-interface').show();
         $('.asenha-fields:not(.fields-admin-interface)').hide();
         window.location.hash = 'admin-interface';
         Cookies.set('asenha_tab', 'admin-interface', { expires: 1 }); // expires in 1 day
      });

      $('#tab-login-logout + label').click( function() {
         $('.fields-login-logout').show();
         $('.asenha-fields:not(.fields-login-logout)').hide();
         window.location.hash = 'login-logout';
         Cookies.set('asenha_tab', 'login-logout', { expires: 1 }); // expires in 1 day
         
      });

      $('#tab-custom-code + label').click( function() {
         $('.fields-custom-code').show();
         $('.asenha-fields:not(.fields-custom-code)').hide();
         window.location.hash = 'custom-code';
         Cookies.set('asenha_tab', 'custom-code', { expires: 1 }); // expires in 1 day
         adminCssEditor.refresh(); // Custom Admin CSS >> CodeMirror
         frontendCssEditor.refresh(); // Custom Fronend CSS >> CodeMirror
         adsTxtEditor.refresh(); // Manage ads.txt >> CodeMirror
         appAdsTxtEditor.refresh(); // Manage app-ads.txt >> CodeMirror
         headCodeEditor.refresh(); // Insert <head>, <body> and <footer> code >> CodeMirror
         bodyCodeEditor.refresh(); // Insert <head>, <body> and <footer> code >> CodeMirror
         footerCodeEditor.refresh(); // Insert <head>, <body> and <footer> code >> CodeMirror
         robotsTxtEditor.refresh(); // Manage robots.txt >> CodeMirror
      });

      $('#tab-disable-components + label').click( function() {
         $('.fields-disable-components').show();
         $('.asenha-fields:not(.fields-disable-components)').hide();
         window.location.hash = 'disable-components';
         Cookies.set('asenha_tab', 'disable-components', { expires: 1 }); // expires in 1 day
      });

      $('#tab-security + label').click( function() {
         $('.fields-security').show();
         $('.asenha-fields:not(.fields-security)').hide();
         window.location.hash = 'security';
         Cookies.set('asenha_tab', 'security', { expires: 1 }); // expires in 1 day
      });

      $('#tab-optimizations + label').click( function() {
         $('.fields-optimizations').show();
         $('.asenha-fields:not(.fields-optimizations)').hide();
         window.location.hash = 'optimizations';
         Cookies.set('asenha_tab', 'optimizations', { expires: 1 }); // expires in 1 day
      });

      $('#tab-utilities + label').click( function() {
         $('.fields-utilities').show();
         $('.asenha-fields:not(.fields-utilities)').hide();
         window.location.hash = 'utilities';
         Cookies.set('asenha_tab', 'utilities', { expires: 1 }); // expires in 1 day
         
      });

      // Open tab set in 'asenha_tab' cookie set on saving changes. Defaults to content-management tab when cookie is empty
      var asenhaTabHash = Cookies.get('asenha_tab');

      if (typeof asenhaTabHash === 'undefined') {
         $('#tab-content-management + label').trigger('click');         
      } else {
         $('#tab-' + asenhaTabHash + ' + label').trigger('click');         
      }
      
      // Show or hide subfields on document ready and on toggle click

      function subfieldsToggler( fieldId, fieldClass, sortableId, codeMirrorInstances ) {

         if (document.getElementById('admin_site_enhancements['+fieldId+']')) {

            // Show/hide subfields on document ready, depending on if module is enabled or not
            if ( document.getElementById('admin_site_enhancements['+fieldId+']').checked ) {

               $('.'+fieldClass+' .asenha-subfields').show();
               if (document.querySelector('.'+fieldClass+' .asenha-subfield-select-inner')) {
                  $('.'+fieldClass+' .asenha-subfield-select-inner').show();
               }
               $('.asenha-toggle.'+fieldClass+' td .asenha-field-with-options').addClass('is-enabled');
               if ( codeMirrorInstances ) {
                  Object.keys(codeMirrorInstances).forEach(function(key) {
                     if ( codeMirrorInstances[key] ) {
                        codeMirrorInstances[key].refresh();
                     }
                  });
               }

            } else {

               $('.'+fieldClass+' .asenha-subfields').hide();
               if (document.querySelector('.'+fieldClass+' .asenha-subfield-select-inner')) {
                  $('.'+fieldClass+' .asenha-subfield-select-inner').hide();
               }

            }

            // Show/hide subfields on toggle click
            document.getElementById('admin_site_enhancements['+fieldId+']').addEventListener('click', event => {
               if (event.target.checked) {

                  $('.'+fieldClass+' .asenha-subfields').fadeIn();
                  if (document.querySelector('.'+fieldClass+' .asenha-subfield-select-inner')) {
                     $('.'+fieldClass+' .asenha-subfield-select-inner').show();
                  }
                  $('.'+fieldClass+' .asenha-field-with-options').toggleClass('is-enabled');
                  if (document.getElementById(sortableId)) {
                     // Initialize sortable elements: https://api.jqueryui.com/sortable/
                     $('#' + sortableId ).sortable();                     
                  }
                  if ( codeMirrorInstances ) {
                     Object.keys(codeMirrorInstances).forEach(function(key) {
                        if ( codeMirrorInstances[key] ) {
                           codeMirrorInstances[key].refresh();                        
                        }
                     });
                  }

               } else {

                  $('.'+fieldClass+' .asenha-subfields').hide();
                  if (document.querySelector('.'+fieldClass+' .asenha-subfield-select-inner')) {
                     $('.'+fieldClass+' .asenha-subfield-select-inner').hide();
                  }
                  $('.'+fieldClass+' .asenha-field-with-options').toggleClass('is-enabled');

               }
            });
            
         }
         
      }
      
       // Re-init wp_editor for snippet description. Required because the wp_editor was moved in the DOM after document ready.
       // Ref: https://stackoverflow.com/a/21519323
       // Ref: https://core.trac.wordpress.org/ticket/19173
      function reinitWpEditor(id) {
         tinymce.execCommand('mceRemoveEditor', true, id);
         var init = tinymce.extend( {}, tinyMCEPreInit.mceInit[ id ] );
         try { tinymce.init( init ); } catch(e){}
         $('textarea[id="' + id + '"]').closest('form').find('input[type="submit"]').click(function(){
          if( getUserSetting( 'editor' ) == 'tmce' ){
              var id = mce.find( 'textarea' ).attr( 'id' );
              tinymce.execCommand( 'mceRemoveEditor', false, id );
              tinymce.execCommand( 'mceAddEditor', false, id );
          }
          return true;
         });
      }

      
      subfieldsToggler( 'enable_duplication', 'enable-duplication' );
      subfieldsToggler( 'content_order', 'content-order' );
      
      subfieldsToggler( 'enable_svg_upload', 'enable-svg-upload' );
      subfieldsToggler( 'enable_avif_upload', 'enable-avif-upload' );
      subfieldsToggler( 'enable_external_permalinks', 'enable-external-permalinks' );
      
      subfieldsToggler( 'enhance_list_tables', 'enhance-list-tables' );
      subfieldsToggler( 'custom_admin_footer_text', 'custom-admin-footer-text' );
      subfieldsToggler( 'wider_admin_menu', 'wider-admin-menu' );
      subfieldsToggler( 'customize_admin_menu', 'customize-admin-menu', 'custom-admin-menu' );
      subfieldsToggler( 'disable_dashboard_widgets', 'disable-dashboard-widgets' );
      subfieldsToggler( 'various_admin_ui_enhancements', 'various-admin-ui-enhancements' );
      // Clean Up Admin Bar
      subfieldsToggler( 'hide_modify_elements', 'hide-modify-elements' );
      
      subfieldsToggler( 'hide_admin_bar', 'hide-admin-bar' );
      subfieldsToggler( 'change_login_url', 'change-login-url' );
      subfieldsToggler( 'login_id_type_restriction', 'login-id-type-restriction' );
      
      subfieldsToggler( 'redirect_after_login', 'redirect-after-login' );
      subfieldsToggler( 'redirect_after_logout', 'redirect-after-logout' );
      subfieldsToggler( 'enable_custom_admin_css', 'enable-custom-admin-css', '', {adminCssEditor} );
      subfieldsToggler( 'enable_custom_frontend_css', 'enable-custom-frontend-css', '', {frontendCssEditor} );
      subfieldsToggler( 'insert_head_body_footer_code', 'insert-head-body-footer-code', '', {headCodeEditor,bodyCodeEditor,footerCodeEditor} );
      subfieldsToggler( 'enable_custom_body_class', 'enable-custom-body-class' );
      subfieldsToggler( 'manage_ads_appads_txt', 'manage-ads-appads-txt', '', {adsTxtEditor,appAdsTxtEditor} );
      subfieldsToggler( 'manage_robots_txt', 'manage-robots-txt', '', {robotsTxtEditor} );
      subfieldsToggler( 'disable_gutenberg', 'disable-gutenberg' );
      subfieldsToggler( 'disable_comments', 'disable-comments' );
      subfieldsToggler( 'disable_smaller_components', 'disable-smaller-components' );
      subfieldsToggler( 'limit_login_attempts', 'limit-login-attempts' );
      subfieldsToggler( 'obfuscate_email_address', 'obfuscate-email-address' );
      subfieldsToggler( 'image_upload_control', 'image-upload-control' );
      subfieldsToggler( 'enable_revisions_control', 'enable-revisions-control' );
      subfieldsToggler( 'enable_heartbeat_control', 'enable-heartbeat-control' );
      

      // Enable Heartbeat Control => Check if "Modify interval" is chosen/clicked and show/hide the corresponding select field
      if ( $('input[name="admin_site_enhancements[heartbeat_control_for_admin_pages]"]:checked').val() == 'modify' ) {
         $('.heartbeat-interval-for-admin-pages .asenha-subfield-select-inner').show();
      }

      $('input[name="admin_site_enhancements[heartbeat_control_for_admin_pages]"]').click(function() {
         var radioValue = $(this).attr('value');
         if ( radioValue == 'modify' ) {
            $('.heartbeat-interval-for-admin-pages .asenha-subfield-select-inner').show();
         } else {
            $('.heartbeat-interval-for-admin-pages .asenha-subfield-select-inner').hide();            
         }
      });

      if ( $('input[name="admin_site_enhancements[heartbeat_control_for_post_edit]"]:checked').val() == 'modify' ) {
         $('.heartbeat-interval-for-post-edit .asenha-subfield-select-inner').show();
      }

      $('input[name="admin_site_enhancements[heartbeat_control_for_post_edit]"]').click(function() {
         var radioValue = $(this).attr('value');
         if ( radioValue == 'modify' ) {
            $('.heartbeat-interval-for-post-edit .asenha-subfield-select-inner').show();
         } else {
            $('.heartbeat-interval-for-post-edit .asenha-subfield-select-inner').hide();            
         }
      });

      if ( $('input[name="admin_site_enhancements[heartbeat_control_for_frontend]"]:checked').val() == 'modify' ) {
         $('.heartbeat-interval-for-frontend .asenha-subfield-select-inner').show();
      }

      $('input[name="admin_site_enhancements[heartbeat_control_for_frontend]"]').click(function() {
         var radioValue = $(this).attr('value');
         if ( radioValue == 'modify' ) {
            $('.heartbeat-interval-for-frontend .asenha-subfield-select-inner').show();
         } else {
            $('.heartbeat-interval-for-frontend .asenha-subfield-select-inner').hide();            
         }
      });

      subfieldsToggler( 'smtp_email_delivery', 'smtp-email-delivery' );

      // SMTP Email Delivery => Empty field value on click, so new password can be easily entered
      var oldSmtpPassValue = '';

      $('input[name="admin_site_enhancements[smtp_password]"]').focusin(function() {
         oldSmtpPassValue = $(this).val();
         $(this).val('');
      });

      $('input[name="admin_site_enhancements[smtp_password]"]').focusout(function() {
         if ( $(this).val() == '' ) {
            $(this).val(oldSmtpPassValue);
         }
      });

      subfieldsToggler( 'view_admin_as_role', 'view-admin-as-role' );
      subfieldsToggler( 'enable_password_protection', 'enable-password-protection' );

      // Enable Password protection => Empty field value on click, so new password can be easily entered
      var oldValue = '';
      $('input[name="admin_site_enhancements[password_protection_password]"]').focusin(function() {
         oldValue = $(this).val();
         $(this).val('');
      });

      $('input[name="admin_site_enhancements[password_protection_password]"]').focusout(function() {
         if ( $(this).val() == '' ) {
            $(this).val(oldValue);
         }
      });
      
      
         subfieldsToggler( 'maintenance_mode', 'maintenance-mode' );
      

      

      
      
      // Media frame handler for image selection / upload fields
      // Reference: https://plugins.trac.wordpress.org/browser/bm-custom-login/trunk/bm-custom-login.php
      function media_frame_init( selector, button_selector ) {
         var theSelector = $(selector);
         var button = $(button_selector);

         button.click(function (event) {
            event.preventDefault();

            // Configuration of the media frame new instance
            wp.media.frames.frame = wp.media({
               title: adminPageVars.mediaFrameTitle,
               multiple: false,
               library: {
                  type: 'image'
               },
               button: {
                  text: adminPageVars.mediaFrameButtonText
               }
            });

            // Function used for the image selection and media manager closing
            var media_set_image = function() {
               var selection = wp.media.frames.frame.state().get('selection');

               // Nothing is selected
               if (!selection) {
                  return;
               }

               // Iterate through selected elements
               selection.each(function(attachment) {
                  // console.log(attachment);
                  var url = attachment.attributes.url;
                  url = url.replace( adminPageVars.wpcontentUrl, '' );
                  theSelector.val(url);
               });
            };

            wp.media.frames.frame.on('close', media_set_image);
            wp.media.frames.frame.on('select', media_set_image);
            wp.media.frames.frame.open();
         });
      }
            
      // =============== ASE PRO =================

      if ( asenhaStats.hideUpgradeNudge ) {
         $('.asenha-upgrade-nudge').hide();
         $('#bottom-upgrade-nudge').show();
      } else {
         $('.asenha-upgrade-nudge').show();         
         $('#bottom-upgrade-nudge').hide();
      }

      $('#dismiss-upgrade-nudge').click(function(e) {
         e.preventDefault();
         $.ajax({
            url: ajaxurl,
            data: {
               'action':'dismiss_upgrade_nudge'
            },
            success:function(data) {
               $('.asenha-upgrade-nudge').hide();
               // $('#bottom-upgrade-nudge').show();
            },
            error:function(errorThrown) {
               console.log(errorThrown);
            }
         });
      });      
      
      // =============== SPONSORSHIP =================

      // Stats on saving changes from asenha_admin_scripts() wp_localize_script() is availble in the 'asenhaStats' object-----
      // console.log( asenhaStats );
      // alert(JSON.stringify(asenhaStats));
      if ( asenhaStats.showSupportNudge ) {
         $('.asenha-support-nudge').show();
      } else {
         $('.asenha-support-nudge').hide();
      }

      $('#have-shared,#have-reviewed').click(function(e) {
         e.preventDefault();
         $.ajax({
            url: 'https://bowo.io/asenha-sp-ndg',
            method: 'GET',
            dataType: 'jsonp',
            crossDomain: true
         });
         $.ajax({
            url: ajaxurl,
            data: {
               'action':'have_supported'
            },
            success:function(data) {
               $('.asenha-support-nudge').hide();
            },
            error:function(errorThrown) {
               console.log(errorThrown);
            }
         });
      });
      
      $('#support-nudge-dismiss').click(function(e) {
         e.preventDefault();
         $.ajax({
            url: 'https://bowo.io/asenha-sp-ndg',
            method: 'GET',
            dataType: 'jsonp',
            crossDomain: true
         });
         $.ajax({
            url: ajaxurl,
            data: {
               'action':'dismiss_support_nudge'
            },
            success:function(data) {
               $('.asenha-support-nudge').hide();
            },
            error:function(errorThrown) {
               console.log(errorThrown);
            }
         });
      });

      // Expand support notice | Modified from https://codepen.io/symonsays/pen/rzgEgY
      $('.asenha-support-nudge.nudge-show-more > .show-more').click(function(e) {

         e.preventDefault();

         var $this = $(this);
         $this.toggleClass('show-more');
         $this.hide();

         if ($this.hasClass('show-more')) {
            $this.next().removeClass('opened',0);
         } else {
            $this.next().addClass('opened',0);
         }

      });

      // Collapse support notice | Modified from https://codepen.io/symonsays/pen/rzgEgY
      $('#support-nudge-show-less').click(function(e) {

         e.preventDefault();

         $('.nudge-wrapper-show-more').removeClass('opened',0);
         $('#support-nudge-show-moreless').addClass('show-more');
         $('#support-nudge-show-moreless').show();

      });

      

      // Modal for sponsoring plugin dev and maintenance: https://stephanwagner.me/jBox

      // var sponsorModal = new jBox('Modal', {
      //    attach: '#plugin-sponsor',
      //    trigger: 'click', // or 'mouseenter'
      //    // content: 'Test'
      //    content: $('#asenha-sponsor'),
      //    width: 740, // pixels
      //    closeButton: 'box',
      //    addClass: 'plugin-sponsor-modal',
      //    overlayClass: 'plugin-sponsor-modal-overlay',
      //    target: '#wpwrap', // where to anchor the modal
      //    position: {
      //       x: 'center',
      //       y: 'top'
      //    },
      //    // fade: 1000,
      //    animation: {
      //       open: 'slide:top',
      //       close: 'slide:top'
      //    }
      // });
      
      // $('#plugin-sponsor').click( function() {
      //    $.ajax({
      //       url: 'https://bowo.io/asenha-sp-btn',
      //       method: 'GET',
      //       dataType: 'jsonp',
      //       crossDomain: true
      //       // success: function(response) {
      //       //    console.log(response);
      //       // }
      //    });
      // });

   }); // END OF $(document).ready()

})( jQuery );