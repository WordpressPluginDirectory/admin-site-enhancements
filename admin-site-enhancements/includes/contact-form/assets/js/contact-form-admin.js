(function ($) {
	'use strict';

	var $modal = $('#asenha-cf-submission-modal');
	var $loader = $modal.find('.asenha-cf-modal-loader');
	var $content = $modal.find('.asenha-cf-modal-content');

	function openModal() {
		$modal.removeAttr('hidden');
		$('body').addClass('asenha-cf-modal-open');
	}

	function closeModal() {
		$modal.attr('hidden', 'hidden');
		$('body').removeClass('asenha-cf-modal-open');
		$content.empty();
	}

	function showLoader() {
		$loader.removeAttr('hidden');
		$content.empty();
	}

	function hideLoader() {
		$loader.attr('hidden', 'hidden');
	}

	$(document).on('click', '.asenha-cf-view-submission', function () {
		var submissionId = $(this).data('submission-id');

		openModal();
		showLoader();

		$.post(asenhaContactFormAdmin.ajaxurl, {
			action: 'asenha_contact_form_get_submission',
			nonce: asenhaContactFormAdmin.nonce,
			submission_id: submissionId
		}).done(function (response) {
			hideLoader();

			if (response.success && response.data.html) {
				$content.html(response.data.html);
				return;
			}

			$content.html('<p>' + asenhaContactFormAdmin.i18n.load_failed + '</p>');
		}).fail(function () {
			hideLoader();
			$content.html('<p>' + asenhaContactFormAdmin.i18n.load_failed + '</p>');
		});
	});

	$(document).on('click', '#doaction, #doaction2', function (e) {
		var $select = $(this).prev('select');
		var action = $select.val();

		if ('delete' !== action) {
			return;
		}

		var checkedCount = $('input[name="submission[]"]:checked').length;

		if (checkedCount === 0) {
			e.preventDefault();
			window.alert(asenhaContactFormAdmin.i18n.no_items_selected);
			return;
		}

		if (!window.confirm(asenhaContactFormAdmin.i18n.bulk_delete_confirm)) {
			e.preventDefault();
		}
	});

	$(document).on('click', '.asenha-cf-delete-submission', function () {
		if (!window.confirm(asenhaContactFormAdmin.i18n.delete_confirm)) {
			return;
		}

		var $button = $(this);
		var submissionId = $button.data('submission-id');
		var deleteNonce = $button.data('nonce');
		var $row = $button.closest('tr');

		$.post(asenhaContactFormAdmin.ajaxurl, {
			action: 'asenha_contact_form_delete_submission',
			submission_id: submissionId,
			delete_nonce: deleteNonce
		}).done(function (response) {
			if (response.success) {
				$row.fadeOut(function () {
					$row.remove();
				});
				return;
			}

			window.alert(response.data && response.data.message ? response.data.message : asenhaContactFormAdmin.i18n.delete_failed);
		}).fail(function () {
			window.alert(asenhaContactFormAdmin.i18n.delete_failed);
		});
	});

	$(document).on('click', '.asenha-cf-modal-close', function () {
		closeModal();
	});

	$(document).on('click', '#asenha-cf-submission-modal', function (event) {
		if (event.target === this) {
			closeModal();
		}
	});
}(jQuery));
