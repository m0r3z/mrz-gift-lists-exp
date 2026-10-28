<?php
/**
 * Plugin Name:       MRZ Gift Lists Exp
 * Plugin URI:        https://github.com/m0r3z/mrz-gift-lists-exp
 * Description:       Listes de cadeaux pour WooCommerce : vos clients créent une liste de produits, partagent le lien, et leurs proches achètent.
 * Version:           0.11.5
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
 * @package Mrzgle
 */

defined( 'ABSPATH' ) || exit;

// Constantes du plugin
define( 'MRZGLE_VERSION', '0.11.5' );
define( 'MRZGLE_PLUGIN_FILE', __FILE__ );
define( 'MRZGLE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MRZGLE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MRZGLE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Tables de base de données
global $wpdb;
define( 'MRZGLE_TABLE_LISTS', $wpdb->prefix . 'mrzgle_lists' );
define( 'MRZGLE_TABLE_ITEMS', $wpdb->prefix . 'mrzgle_items' );

/**
 * Vérifie si WooCommerce est actif
 *
 * @return bool
 */
function mrzgle_is_woocommerce_active(): bool {
    return class_exists( 'WooCommerce' );
}

/**
 * Affiche une notice d'erreur si WooCommerce n'est pas actif
 */
function mrzgle_woocommerce_missing_notice(): void {
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
function mrzgle_init(): void {
    // Vérifie WooCommerce
    if ( ! mrzgle_is_woocommerce_active() ) {
        add_action( 'admin_notices', 'mrzgle_woocommerce_missing_notice' );
        return;
    }

    // Charge l'autoloader
    require_once MRZGLE_PLUGIN_DIR . 'includes/class-autoloader.php';
    Mrzgle\Autoloader::register();

    // Initialise le plugin
    Mrzgle\Plugin::instance();
}
add_action( 'plugins_loaded', 'mrzgle_init', 20 );

/**
 * Activation du plugin
 */
function mrzgle_activate(): void {
    require_once MRZGLE_PLUGIN_DIR . 'includes/class-activator.php';
    Mrzgle\Activator::activate();
}
register_activation_hook( __FILE__, 'mrzgle_activate' );

/**
 * Désactivation du plugin
 */
function mrzgle_deactivate(): void {
    require_once MRZGLE_PLUGIN_DIR . 'includes/class-activator.php';
    Mrzgle\Activator::deactivate();
}
register_deactivation_hook( __FILE__, 'mrzgle_deactivate' );

/**
 * Déclare la compatibilité HPOS (High-Performance Order Storage) de WooCommerce
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});
