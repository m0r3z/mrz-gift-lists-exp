/**
 * MRZ Gift Lists - Admin JavaScript
 *
 * @package Mrzgle
 */

(function($) {
    'use strict';

    var mrzGleAdmin = {
        ajaxUrl: mrzGleAdminData.ajaxUrl,
        nonce: mrzGleAdminData.nonce,
        i18n: mrzGleAdminData.i18n
    };

    /**
     * Initialisation
     */
    $(document).ready(function() {
        initConfirmDelete();
        initColorPicker();
    });

    /**
     * Confirmation de suppression
     */
    function initConfirmDelete() {
        $(document).on('click', '.mrz-gle-delete', function(e) {
            // Message propre au lien (data-confirm), sinon message générique
            if (!confirm($(this).data('confirm') || mrzGleAdmin.i18n.confirmDelete)) {
                e.preventDefault();
                return false;
            }
        });
    }

    /**
     * Color picker
     */
    function initColorPicker() {
        if ($.fn.wpColorPicker) {
            $('.mrz-gle-color-picker').wpColorPicker();
        }
    }

})(jQuery);
