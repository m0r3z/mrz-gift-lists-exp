/**
 * MRZ Gift Lists - Frontend JavaScript
 *
 * @package MrzGiftsListsExp
 */

(function($) {
    'use strict';

    // Variables globales
    var mrzGle = {
        ajaxUrl: mrzGleData.ajaxUrl,
        nonce: mrzGleData.nonce,
        i18n: mrzGleData.i18n
    };

    /**
     * Initialisation
     */
    $(document).ready(function() {
        initAddToCart();
        initRemoveItem();
        initTogglePriority();
        initShareButtons();
        initAddToListFromProduct();
    });

    /**
     * Ajout au panier depuis la liste
     */
    function initAddToCart() {
        $(document).on('click', '.mrz-gle-add-to-cart', function(e) {
            e.preventDefault();

            var $button = $(this);
            var productId = $button.data('product-id');
            var listId = $button.data('list-id');
            var itemId = $button.data('item-id');
            var originalText = $button.text();

            $button.prop('disabled', true).text(mrzGle.i18n.adding);

            $.ajax({
                url: mrzGle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mrz_gle_add_to_cart',
                    nonce: mrzGle.nonce,
                    product_id: productId,
                    list_id: listId,
                    item_id: itemId,
                    quantity: 1
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(mrzGle.i18n.addedToCart, 'success');
                        $button.text(mrzGle.i18n.added);

                        // Met à jour le compteur de panier si WooCommerce le supporte
                        $(document.body).trigger('wc_fragment_refresh');

                        setTimeout(function() {
                            $button.prop('disabled', false).text(originalText);
                        }, 2000);
                    } else {
                        showNotice(response.data.message || mrzGle.i18n.error, 'error');
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    showNotice(mrzGle.i18n.error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Suppression d'un produit de la liste
     */
    function initRemoveItem() {
        $(document).on('click', '.mrz-gle-remove-item', function(e) {
            e.preventDefault();

            if (!confirm(mrzGle.i18n.confirmDelete)) {
                return;
            }

            var $button = $(this);
            var itemId = $button.data('item-id');
            var $item = $button.closest('.mrz-gle-product-item');

            $item.css('opacity', '0.5');

            $.ajax({
                url: mrzGle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mrz_gle_remove_product_from_list',
                    nonce: mrzGle.nonce,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                        });
                        showNotice(mrzGle.i18n.productRemoved, 'success');
                    } else {
                        $item.css('opacity', '1');
                        showNotice(response.data.message || mrzGle.i18n.error, 'error');
                    }
                },
                error: function() {
                    $item.css('opacity', '1');
                    showNotice(mrzGle.i18n.error, 'error');
                }
            });
        });
    }

    /**
     * Bascule la priorité d'un produit
     */
    function initTogglePriority() {
        $(document).on('click', '.mrz-gle-toggle-priority', function(e) {
            e.preventDefault();

            var $button = $(this);
            var itemId = $button.data('item-id');

            $.ajax({
                url: mrzGle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mrz_gle_toggle_priority',
                    nonce: mrzGle.nonce,
                    item_id: itemId
                },
                success: function(response) {
                    if (response.success) {
                        var newIcon = response.data.priority ? '⭐' : '☆';
                        $button.text(newIcon);

                        // Met à jour l'icône de priorité sur l'item
                        var $item = $button.closest('.mrz-gle-product-item');
                        var $priorityIcon = $item.find('.mrz-gle-product-priority');

                        if (response.data.priority) {
                            if (!$priorityIcon.length) {
                                $item.prepend('<span class="mrz-gle-product-priority" title="Prioritaire">⭐</span>');
                            }
                        } else {
                            $priorityIcon.remove();
                        }
                    } else {
                        showNotice(response.data.message || mrzGle.i18n.error, 'error');
                    }
                },
                error: function() {
                    showNotice(mrzGle.i18n.error, 'error');
                }
            });
        });
    }

    /**
     * Boutons de partage
     */
    function initShareButtons() {
        // Copier le lien
        $(document).on('click', '.mrz-gle-share-copy', function(e) {
            e.preventDefault();

            var url = $(this).data('url');

            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    showNotice(mrzGle.i18n.copied, 'success');
                });
            } else {
                // Fallback pour les anciens navigateurs
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(url).select();
                document.execCommand('copy');
                $temp.remove();
                showNotice(mrzGle.i18n.copied, 'success');
            }
        });
    }

    /**
     * Ajout à une liste depuis la page produit WooCommerce
     */
    function initAddToListFromProduct() {
        // Toggle dropdown
        $(document).on('click', '.mrz-gle-add-to-list-toggle', function(e) {
            e.preventDefault();
            $(this).siblings('.mrz-gle-add-to-list-dropdown').slideToggle(200);
        });

        // Confirmer l'ajout
        $(document).on('click', '.mrz-gle-confirm-add-to-list', function(e) {
            e.preventDefault();

            var $wrapper = $(this).closest('.mrz-gle-add-to-list-wrapper');
            var $select = $wrapper.find('.mrz-gle-list-select');
            var listId = $select.val();
            var productId = $select.data('product-id');

            if (!listId) {
                alert('Veuillez sélectionner une liste.');
                return;
            }

            var $button = $(this);
            var originalText = $button.text();
            $button.prop('disabled', true).text('...');

            $.ajax({
                url: mrzGle.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mrz_gle_add_product_to_list',
                    nonce: mrzGle.nonce,
                    product_id: productId,
                    list_id: listId
                },
                success: function(response) {
                    if (response.success) {
                        showNotice(mrzGle.i18n.productAdded, 'success');
                        $wrapper.find('.mrz-gle-add-to-list-dropdown').slideUp(200);
                        $select.val('');
                    } else {
                        showNotice(response.data.message || mrzGle.i18n.error, 'error');
                    }
                    $button.prop('disabled', false).text(originalText);
                },
                error: function() {
                    showNotice(mrzGle.i18n.error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    /**
     * Affiche une notification
     */
    function showNotice(message, type) {
        // Supprime les notices existantes
        $('.mrz-gle-toast').remove();

        var bgColor = type === 'success' ? '#00a32a' : '#d63638';

        var $toast = $('<div class="mrz-gle-toast" style="' +
            'position: fixed; bottom: 20px; right: 20px; ' +
            'padding: 12px 20px; background: ' + bgColor + '; color: #fff; ' +
            'border-radius: 4px; z-index: 99999; box-shadow: 0 2px 10px rgba(0,0,0,0.2); ' +
            'font-size: 14px; max-width: 300px;' +
            '">' + message + '</div>');

        $('body').append($toast);

        setTimeout(function() {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

})(jQuery);
