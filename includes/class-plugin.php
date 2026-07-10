<?php
/**
 * Classe principale du plugin
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Plugin
 * Point d'entrée principal - Pattern Singleton
 */
final class Plugin {

    /**
     * Instance unique
     *
     * @var Plugin|null
     */
    private static ?Plugin $instance = null;

    /**
     * Gestionnaire admin
     *
     * @var Admin\Admin|null
     */
    public ?Admin\Admin $admin = null;

    /**
     * Gestionnaire frontend
     *
     * @var Frontend\Frontend|null
     */
    public ?Frontend\Frontend $frontend = null;

    /**
     * Gestionnaire WooCommerce
     *
     * @var Integrations\WooCommerce|null
     */
    public ?Integrations\WooCommerce $woocommerce = null;

    /**
     * Gestionnaire AJAX
     *
     * @var Ajax\Handler|null
     */
    public ?Ajax\Handler $ajax = null;

    /**
     * Retourne l'instance unique
     *
     * @return Plugin
     */
    public static function instance(): Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Empêche le clonage
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation
     */
    public function __wakeup() {
        throw new \Exception( 'Cannot unserialize singleton' );
    }

    /**
     * Initialise les hooks
     */
    private function init_hooks(): void {
        // Flush rewrite rules après activation
        add_action( 'init', [ $this, 'maybe_flush_rewrite_rules' ], 99 );

        // Enregistre les assets
        add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );

        // Enregistre les endpoints
        add_action( 'init', [ $this, 'register_endpoints' ] );

        // Ajoute les query vars
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
    }

    /**
     * Initialise les composants
     */
    private function init_components(): void {
        // Admin
        if ( is_admin() ) {
            $this->admin = new Admin\Admin();
        }

        // Frontend
        $this->frontend = new Frontend\Frontend();

        // WooCommerce
        $this->woocommerce = new Integrations\WooCommerce();

        // AJAX (admin et frontend)
        $this->ajax = new Ajax\Handler();

        // Emails
        new Emails\Manager();
    }

    /**
     * Flush les rewrite rules si nécessaire
     */
    public function maybe_flush_rewrite_rules(): void {
        if ( get_transient( 'mrz_gle_flush_rewrite_rules' ) ) {
            flush_rewrite_rules();
            delete_transient( 'mrz_gle_flush_rewrite_rules' );
        }
    }

    /**
     * Enregistre les endpoints
     */
    public function register_endpoints(): void {
        // Endpoint dans Mon Compte WooCommerce
        add_rewrite_endpoint( 'mrz-gift-lists', EP_ROOT | EP_PAGES );
    }

    /**
     * Ajoute les query vars
     *
     * @param array $vars Variables de requête existantes
     * @return array
     */
    public function add_query_vars( array $vars ): array {
        $vars[] = 'mrz-gift-lists';
        $vars[] = 'mrz_gle_list_key';
        $vars[] = 'mrz_gle_list_action';
        return $vars;
    }

    /**
     * Enregistre les assets frontend
     */
    public function register_frontend_assets(): void {
        // CSS
        wp_register_style(
            'mrz-gle-frontend',
            MRZ_GIFTS_LISTS_EXP_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            MRZ_GIFTS_LISTS_EXP_VERSION
        );

        // JS
        wp_register_script(
            'mrz-gle-frontend',
            MRZ_GIFTS_LISTS_EXP_PLUGIN_URL . 'assets/js/frontend.js',
            [ 'jquery', 'wp-util' ],
            MRZ_GIFTS_LISTS_EXP_VERSION,
            true
        );

        // Données localisées pour JS
        wp_localize_script( 'mrz-gle-frontend', 'mrzGleData', [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'mrz_gle_frontend_nonce' ),
            'i18n'      => [
                'confirmDelete'     => __( 'Êtes-vous sûr de vouloir supprimer cet élément ?', 'mrz-gift-lists-exp' ),
                'adding'            => __( 'Ajout en cours...', 'mrz-gift-lists-exp' ),
                'added'             => __( 'Ajouté !', 'mrz-gift-lists-exp' ),
                'error'             => __( 'Une erreur est survenue', 'mrz-gift-lists-exp' ),
                'productAdded'      => __( 'Produit ajouté à la liste', 'mrz-gift-lists-exp' ),
                'productRemoved'    => __( 'Produit retiré de la liste', 'mrz-gift-lists-exp' ),
                'addedToCart'       => __( 'Produit ajouté au panier', 'mrz-gift-lists-exp' ),
                'copied'            => __( 'Lien copié !', 'mrz-gift-lists-exp' ),
            ],
        ] );
    }

    /**
     * Enregistre les assets admin
     */
    public function register_admin_assets(): void {
        // CSS Admin
        wp_register_style(
            'mrz-gle-admin',
            MRZ_GIFTS_LISTS_EXP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MRZ_GIFTS_LISTS_EXP_VERSION
        );

        // JS Admin
        wp_register_script(
            'mrz-gle-admin',
            MRZ_GIFTS_LISTS_EXP_PLUGIN_URL . 'assets/js/admin.js',
            [ 'jquery', 'wp-util' ],
            MRZ_GIFTS_LISTS_EXP_VERSION,
            true
        );

        wp_localize_script( 'mrz-gle-admin', 'mrzGleAdminData', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'mrz_gle_admin_nonce' ),
            'i18n'    => [
                'confirmDelete' => __( 'Êtes-vous sûr de vouloir supprimer ?', 'mrz-gift-lists-exp' ),
                'saved'         => __( 'Enregistré', 'mrz-gift-lists-exp' ),
                'error'         => __( 'Erreur', 'mrz-gift-lists-exp' ),
            ],
        ] );
    }

    /**
     * Retourne l'URL de la page des listes
     *
     * @return string
     */
    public function get_lists_page_url(): string {
        $page_id = get_option( 'mrz_gle_page_id' );
        return $page_id ? get_permalink( $page_id ) : home_url( '/mrz-gift-lists/' );
    }

    /**
     * Retourne l'URL d'une liste spécifique
     *
     * @param string $access_key Clé d'accès de la liste
     * @return string
     */
    public function get_list_url( string $access_key ): string {
        return add_query_arg( 'mrz_gle_list_key', $access_key, $this->get_lists_page_url() );
    }

    /**
     * Vérifie si l'utilisateur peut gérer les listes
     *
     * @return bool
     */
    public function current_user_can_manage(): bool {
        return current_user_can( 'mrz_gle_manage_lists' );
    }
}
