<?php
/**
 * Plugin Name:       MRZ Gift Lists Exp
 * Plugin URI:        https://github.com/m0r3z/mrz-gift-lists-exp
 * Description:       Listes de cadeaux pour WooCommerce : vos clients créent une liste de produits, partagent le lien, et leurs proches achètent.
 * Version:           0.11.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Morez.co
 * Author URI:        https://morez.co
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       mrz-gift-lists-exp
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   9.0
 *
 * MRZ Gift Lists — Copyright (C) 2026 Morez.co <hello@morez.co>
 * "MRZ Gift Lists" is a trademark of Morez.co. See LICENSE for full terms.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License v3 as published by
 * the Free Software Foundation. See LICENSE for the full license text.
 *
 * @package MrzGiftsListsExp
 */

defined( 'ABSPATH' ) || exit;

// Constantes du plugin
define( 'MRZ_GIFTS_LISTS_EXP_VERSION', '0.11.1' );
define( 'MRZ_GIFTS_LISTS_EXP_PLUGIN_FILE', __FILE__ );
define( 'MRZ_GIFTS_LISTS_EXP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MRZ_GIFTS_LISTS_EXP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MRZ_GIFTS_LISTS_EXP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Tables de base de données
global $wpdb;
define( 'MRZ_GIFTS_LISTS_EXP_TABLE_LISTS', $wpdb->prefix . 'mrz_gle_lists' );
define( 'MRZ_GIFTS_LISTS_EXP_TABLE_ITEMS', $wpdb->prefix . 'mrz_gle_items' );

/**
 * Vérifie si WooCommerce est actif
 *
 * @return bool
 */
function mrz_gle_is_woocommerce_active(): bool {
    return class_exists( 'WooCommerce' );
}

/**
 * Affiche une notice d'erreur si WooCommerce n'est pas actif
 */
function mrz_gle_woocommerce_missing_notice(): void {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e( 'MRZ Gift Lists', 'mrz-gift-lists-exp' ); ?></strong>
            <?php esc_html_e( 'nécessite WooCommerce pour fonctionner. Veuillez installer et activer WooCommerce.', 'mrz-gift-lists-exp' ); ?>
        </p>
    </div>
    <?php
}

/**
 * Initialise le plugin
 */
function mrz_gle_init(): void {
    // Vérifie WooCommerce
    if ( ! mrz_gle_is_woocommerce_active() ) {
        add_action( 'admin_notices', 'mrz_gle_woocommerce_missing_notice' );
        return;
    }

    // Charge les traductions
    load_plugin_textdomain( 'mrz-gift-lists-exp', false, dirname( MRZ_GIFTS_LISTS_EXP_PLUGIN_BASENAME ) . '/languages' );

    // Charge l'autoloader
    require_once MRZ_GIFTS_LISTS_EXP_PLUGIN_DIR . 'includes/class-autoloader.php';
    MrzGiftsListsExp\Autoloader::register();

    // Initialise le plugin
    MrzGiftsListsExp\Plugin::instance();
}
add_action( 'plugins_loaded', 'mrz_gle_init', 20 );

/**
 * Activation du plugin
 */
function mrz_gle_activate(): void {
    require_once MRZ_GIFTS_LISTS_EXP_PLUGIN_DIR . 'includes/class-activator.php';
    MrzGiftsListsExp\Activator::activate();
}
register_activation_hook( __FILE__, 'mrz_gle_activate' );

/**
 * Désactivation du plugin
 */
function mrz_gle_deactivate(): void {
    require_once MRZ_GIFTS_LISTS_EXP_PLUGIN_DIR . 'includes/class-activator.php';
    MrzGiftsListsExp\Activator::deactivate();
}
register_deactivation_hook( __FILE__, 'mrz_gle_deactivate' );

/**
 * Déclare la compatibilité HPOS (High-Performance Order Storage) de WooCommerce
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});
