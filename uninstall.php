<?php
/**
 * Désinstallation du plugin MRZ Gift Lists
 *
 * Ce fichier est exécuté lors de la suppression du plugin depuis l'admin WordPress.
 * Il nettoie toutes les données créées par le plugin.
 *
 * @package MrzGiftsListsExp
 */

// Sécurité : empêche l'accès direct
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Supprime les tables
$tables = [
    $wpdb->prefix . 'mrz_gle_lists',
    $wpdb->prefix . 'mrz_gle_items',
    $wpdb->prefix . 'mrz_gle_types',
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Supprime les options
$options = [
    'mrz_gle_db_version',
    'mrz_gle_page_id',
    'mrz_gle_shop_page_id',
    'mrz_gle_who_can_create',
    'mrz_gle_max_lists_per_user',
    'mrz_gle_display_mode',
    'mrz_gle_products_per_page',
    'mrz_gle_admin_email',
    'mrz_gle_notify_owner_on_purchase',
    'mrz_gle_share_networks',
];

foreach ( $options as $option ) {
    delete_option( $option );
}

// Supprime les capabilities
$roles = [ 'administrator', 'shop_manager' ];
$capabilities = [ 'mrz_gle_manage_lists', 'mrz_gle_edit_lists', 'mrz_gle_delete_lists' ];

foreach ( $roles as $role_name ) {
    $role = get_role( $role_name );
    if ( $role ) {
        foreach ( $capabilities as $cap ) {
            $role->remove_cap( $cap );
        }
    }
}

// Supprime les transients
delete_transient( 'mrz_gle_flush_rewrite_rules' );

// Nettoie les métadonnées des commandes WooCommerce (optionnel, commenté par défaut)
// $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_mrz_gle_%'" );

// Flush les rewrite rules
flush_rewrite_rules();
