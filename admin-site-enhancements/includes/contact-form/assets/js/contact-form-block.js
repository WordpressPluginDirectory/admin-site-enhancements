( function ( wp ) {
	'use strict';

	var registerBlockType = wp.blocks.registerBlockType;
	var createElement = wp.element.createElement;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var ServerSideRender = wp.serverSideRender;

	registerBlockType( 'asenha/contact-form', {
		apiVersion: 3,
		title: asenhaContactFormBlock.i18n.title,
		description: asenhaContactFormBlock.i18n.description,
		category: 'widgets',
		icon: 'email',
		supports: {
			html: false,
		},
		edit: function () {
			var blockProps = useBlockProps();

			return createElement(
				'div',
				blockProps,
				createElement( ServerSideRender, {
					block: 'asenha/contact-form',
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
}( window.wp ) );
