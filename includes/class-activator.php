<?php
/**
 * Gestion de l'activation et désactivation du plugin
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp;

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
    private const DB_VERSION_OPTION = 'mrz_gle_db_version';

    /**
     * Activation du plugin
     */
    public static function activate(): void {
        self::create_tables();
        self::create_pages();
        self::add_capabilities();

        // Stocke la version de la DB
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

        // Force le flush des rewrite rules
        set_transient( 'mrz_gle_flush_rewrite_rules', true, 30 );
    }

    /**
     * Désactivation du plugin
     */
    public static function deactivate(): void {
        // Nettoie les transients
        delete_transient( 'mrz_gle_flush_rewrite_rules' );

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
        $sql_lists = "CREATE TABLE " . MRZ_GIFTS_LISTS_EXP_TABLE_LISTS . " (
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
        $sql_items = "CREATE TABLE " . MRZ_GIFTS_LISTS_EXP_TABLE_ITEMS . " (
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
     * Crée les pages nécessaires
     */
    private static function create_pages(): void {
        // Page principale des listes
        $page_id = get_option( 'mrz_gle_page_id' );

        if ( ! $page_id || ! get_post( $page_id ) ) {
            $page_data = [
                'post_title'   => __( 'Listes de cadeaux', 'mrz-gift-lists-exp' ),
                'post_content' => '[mrz_gift_lists]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ];

            $page_id = wp_insert_post( $page_data );

            if ( $page_id && ! is_wp_error( $page_id ) ) {
                update_option( 'mrz_gle_page_id', $page_id );
            }
        }
    }

    /**
     * Ajoute les capacités personnalisées
     */
    private static function add_capabilities(): void {
        $admin = get_role( 'administrator' );

        if ( $admin ) {
            $admin->add_cap( 'mrz_gle_manage_lists' );
            $admin->add_cap( 'mrz_gle_edit_lists' );
            $admin->add_cap( 'mrz_gle_delete_lists' );
        }

        $shop_manager = get_role( 'shop_manager' );

        if ( $shop_manager ) {
            $shop_manager->add_cap( 'mrz_gle_manage_lists' );
            $shop_manager->add_cap( 'mrz_gle_edit_lists' );
        }
    }
}
