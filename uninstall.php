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
$mrzgle_tables = [
    $wpdb->prefix . 'mrzgle_lists',
    $wpdb->prefix . 'mrzgle_items',
];

foreach ( $mrzgle_tables as $mrzgle_table ) {
    // Nom de table construit à partir de $wpdb->prefix et d'une liste fixe : rien à préparer ni à mettre en cache.
    $wpdb->query( "DROP TABLE IF EXISTS {$mrzgle_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
}

// Supprime les options
$mrzgle_options = [
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

foreach ( $mrzgle_options as $mrzgle_option ) {
    delete_option( $mrzgle_option );
}

// Supprime les capabilities
$mrzgle_roles = [ 'administrator', 'shop_manager' ];
$mrzgle_capabilities = [ 'mrzgle_manage_lists', 'mrzgle_edit_lists', 'mrzgle_delete_lists' ];

foreach ( $mrzgle_roles as $mrzgle_role_name ) {
    $mrzgle_role = get_role( $mrzgle_role_name );
    if ( $mrzgle_role ) {
        foreach ( $mrzgle_capabilities as $mrzgle_cap ) {
            $mrzgle_role->remove_cap( $mrzgle_cap );
        }
    }
}

// Supprime les transients
delete_transient( 'mrzgle_flush_rewrite_rules' );

// Nettoie les métadonnées des commandes WooCommerce (optionnel, commenté par défaut)
// $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_mrzgle_%'" );

// Flush les rewrite rules
flush_rewrite_rules();
