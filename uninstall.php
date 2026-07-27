<?php
/**
 * Désinstallation du plugin MRZ Gift Lists
 *
 * Ce fichier est exécuté lors de la suppression du plugin depuis l'admin WordPress.
 * Il nettoie toutes les données créées par le plugin.
 *
 * @package Mrzgle
 */

// Sécurité : empêche l'accès direct
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

// Supprime les tables
$tables = [
    $wpdb->prefix . 'mrzgle_lists',
    $wpdb->prefix . 'mrzgle_items',
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

// Supprime les options
$options = [
    'mrzgle_db_version',
    'mrzgle_page_id',
    'mrzgle_shop_page_id',
    'mrzgle_who_can_create',
    'mrzgle_max_lists_per_user',
    'mrzgle_display_mode',
    'mrzgle_products_per_page',
    'mrzgle_admin_email',
    'mrzgle_notify_owner_on_purchase',
    'mrzgle_share_networks',
];

foreach ( $options as $option ) {
    delete_option( $option );
}

// Supprime les capabilities
$roles = [ 'administrator', 'shop_manager' ];
$capabilities = [ 'mrzgle_manage_lists', 'mrzgle_edit_lists', 'mrzgle_delete_lists' ];

foreach ( $roles as $role_name ) {
    $role = get_role( $role_name );
    if ( $role ) {
        foreach ( $capabilities as $cap ) {
            $role->remove_cap( $cap );
        }
    }
}

// Supprime les transients
delete_transient( 'mrzgle_flush_rewrite_rules' );

// Nettoie les métadonnées des commandes WooCommerce (optionnel, commenté par défaut)
// $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_mrzgle_%'" );

// Flush les rewrite rules
flush_rewrite_rules();
