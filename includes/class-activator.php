<?php
/**
 * Gestion de l'activation et désactivation du plugin
 *
 * @package Mrzgle
 */

namespace Mrzgle;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Activator
 * Gère l'installation des tables et données initiales
 */
class Activator {

    /**
     * Version de la base de données
     *
     * @var string
     */
    private const DB_VERSION = '1.0.0';

    /**
     * Option pour stocker la version de la DB
     *
     * @var string
     */
    private const DB_VERSION_OPTION = 'mrzgle_db_version';

    /**
     * Activation du plugin
     */
    public static function activate(): void {
        self::create_tables();
        self::add_capabilities();

        // Stocke la version de la DB
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

        // Force le flush des rewrite rules
        set_transient( 'mrzgle_flush_rewrite_rules', true, 30 );
    }

    /**
     * Désactivation du plugin
     */
    public static function deactivate(): void {
        // Nettoie les transients
        delete_transient( 'mrzgle_flush_rewrite_rules' );

        // Flush des rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crée les tables de base de données
     */
    private static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table des listes
        $sql_lists = "CREATE TABLE " . MRZGLE_TABLE_LISTS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATE DEFAULT NULL,
            image_id BIGINT(20) UNSIGNED DEFAULT NULL,
            access_key VARCHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY access_key (access_key),
            KEY user_id (user_id),
            KEY status (status)
        ) $charset_collate;";

        // Table des produits de liste
        $sql_items = "CREATE TABLE " . MRZGLE_TABLE_ITEMS . " (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            list_id BIGINT(20) UNSIGNED NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            variation_id BIGINT(20) UNSIGNED DEFAULT NULL,
            quantity INT(11) UNSIGNED NOT NULL DEFAULT 1,
            quantity_purchased INT(11) UNSIGNED NOT NULL DEFAULT 0,
            priority TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
            note TEXT,
            status VARCHAR(20) NOT NULL DEFAULT 'available',
            purchased_by VARCHAR(255) DEFAULT NULL,
            purchased_at DATETIME DEFAULT NULL,
            order_id BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY list_id (list_id),
            KEY product_id (product_id),
            KEY status (status),
            UNIQUE KEY list_product (list_id, product_id, variation_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql_lists );
        dbDelta( $sql_items );
    }

    /**
     * Ajoute les capacités personnalisées
     */
    private static function add_capabilities(): void {
        $admin = get_role( 'administrator' );

        if ( $admin ) {
            $admin->add_cap( 'mrzgle_manage_lists' );
            $admin->add_cap( 'mrzgle_edit_lists' );
            $admin->add_cap( 'mrzgle_delete_lists' );
        }

        $shop_manager = get_role( 'shop_manager' );

        if ( $shop_manager ) {
            $shop_manager->add_cap( 'mrzgle_manage_lists' );
            $shop_manager->add_cap( 'mrzgle_edit_lists' );
        }
    }
}
