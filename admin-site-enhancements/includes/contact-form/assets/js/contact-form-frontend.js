(function ($) {
	'use strict';

	function setJsToken($form) {
		var token = $form.data('js-token');

		if (token) {
			$form.find('input[name="asenha_cf_js"]').val(token);
		}
	}

	function clearMessages($form) {
		$form.find('.asenha-cf-messages').empty();
		$form.find('.asenha-cf-field-error').removeClass('asenha-cf-field-error');
		$form.find('.asenha-cf-field-error-message').remove();
	}

	function showMessage($form, message, type) {
		if (type === 'success') {
			$form.find('.asenha-cf-messages').html(
				'<p class="asenha-cf-success-msg">' +
					'<span class="asenha-cf-success-icon" aria-hidden="true">' +
						'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" focusable="false">' +
							'<path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2m-2 15l-5-5l1.41-1.41L10 14.17l7.59-7.59L19 8z"/>' +
						'</svg>' +
					'</span>' +
					'<span class="asenha-cf-success-text"></span>' +
				'</p>'
			);
			$form.find('.asenha-cf-success-text').text(message);
			return;
		}

		$form.find('.asenha-cf-messages')
			.empty()
			.append($('<p>', { 'class': 'asenha-cf-error-msg' }).text(message));
	}

	function showFieldErrors($form, errors) {
		Object.keys(errors).forEach(function (fieldName) {
			var $field = $form.find('[name="' + fieldName + '"]').closest('.asenha-cf-field');

			$field.addClass('asenha-cf-field-error');
			$field.append($('<div>', { 'class': 'asenha-cf-field-error-message' }).text(errors[fieldName]));
		});
	}

	function scrollToMessages($form) {
		var $messages = $form.find('.asenha-cf-messages');

		if (!$messages.length || !$messages.children().length) {
			return;
		}

		$messages[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
	}

	function setSubmitLoading($form, isLoading) {
		var $button = $form.find('.asenha-cf-submit-button');
		var $spinner = $form.find('.asenha-cf-submit-spinner');

		if (isLoading) {
			$button.addClass('is-loading').prop('disabled', true);
			$form.attr('aria-busy', 'true');
			$spinner.removeAttr('aria-hidden');

			if ($spinner.length && !$spinner.find('.screen-reader-text').length) {
				$spinner.append('<span class="screen-reader-text">' + asenhaContactForm.i18n.sending + '</span>');
			}

			return;
		}

		$button.removeClass('is-loading').prop('disabled', false);
		$form.attr('aria-busy', 'false');
		$spinner.attr('aria-hidden', 'true');
		$spinner.find('.screen-reader-text').remove();
	}

	$(function () {
		$('.asenha-contact-form').each(function () {
			setJsToken($(this));
		});

		$(document).on('submit', '.asenha-contact-form', function (event) {
			event.preventDefault();

			var $form = $(this);
			var $button = $form.find('.asenha-cf-submit-button');

			if ($button.hasClass('is-loading')) {
				return;
			}

			clearMessages($form);
			setSubmitLoading($form, true);

			$.ajax({
				type: 'POST',
				url: asenhaContactForm.ajaxurl,
				dataType: 'json',
				data: $form.serialize() + '&action=asenha_contact_form_submit&asenha_cf_sent_from=' + encodeURIComponent(window.location.href)
			}).done(function (response) {
				if (response.success) {
					showMessage($form, response.data.message, 'success');
					scrollToMessages($form);
					$form[0].reset();
					setJsToken($form);
					return;
				}

				if (response.data && response.data.errors) {
					showFieldErrors($form, response.data.errors);
				}

				showMessage($form, response.data && response.data.message ? response.data.message : asenhaContactForm.i18n.generic_error, 'error');
				scrollToMessages($form);
			}).fail(function () {
				showMessage($form, asenhaContactForm.i18n.generic_error, 'error');
				scrollToMessages($form);
			}).always(function () {
				setSubmitLoading($form, false);
			});
		});
	});
}(jQuery));
