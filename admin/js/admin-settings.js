/**
 * BuddyPress Birthdays Admin Settings JavaScript
 *
 * @package BP_Birthdays
 * @since 2.4.0
 */

(function($) {
	'use strict';

	/**
	 * Toggle dependent fields visibility based on checkbox state.
	 *
	 * @param {jQuery} $checkbox - The toggle checkbox element.
	 * @param {string} dependentClass - Class name of dependent rows.
	 */
	function toggleDependentFields($checkbox, dependentClass) {
		var isChecked = $checkbox.is(':checked');
		var $dependentRows = $('.' + dependentClass);

		if (isChecked) {
			$dependentRows.removeClass('disabled');
			$dependentRows.find('input, select, textarea').prop('disabled', false);
		} else {
			$dependentRows.addClass('disabled');
			$dependentRows.find('input, select, textarea').prop('disabled', true);
		}
	}

	/**
	 * Initialize settings page functionality.
	 */
	function init() {
		// Email notifications toggle
		var $emailToggle = $('input[name="bp_birthdays_settings[email_enabled]"]');
		if ($emailToggle.length) {
			toggleDependentFields($emailToggle, 'email-dependent');
			$emailToggle.on('change', function() {
				toggleDependentFields($(this), 'email-dependent');
			});
		}

		// Activity feed toggle
		var $activityToggle = $('input[name="bp_birthdays_settings[activity_enabled]"]');
		if ($activityToggle.length) {
			toggleDependentFields($activityToggle, 'activity-dependent');
			$activityToggle.on('change', function() {
				toggleDependentFields($(this), 'activity-dependent');
			});
		}

		// Notifications toggle
		var $notificationToggle = $('input[name="bp_birthdays_settings[notification_enabled]"]');
		if ($notificationToggle.length) {
			toggleDependentFields($notificationToggle, 'notification-dependent');
			$notificationToggle.on('change', function() {
				toggleDependentFields($(this), 'notification-dependent');
			});
		}
	}

	// Initialize on document ready
	$(document).ready(init);

})(jQuery);
