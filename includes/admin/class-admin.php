<?php
/**
 * Classe principale Admin
 *
 * @package Mrzgle
 */

namespace Mrzgle\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Admin
 * Gère toute la partie administration du plugin
 */
class Admin {

    /**
     * Constructeur
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_components();
    }

    /**
     * Initialise les hooks
     */
    private function init_hooks(): void {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_filter( 'plugin_action_links_' . MRZGLE_PLUGIN_BASENAME, [ $this, 'add_plugin_links' ] );
        add_action( 'admin_notices', [ $this, 'render_missing_page_notice' ] );
    }

    /**
     * Alerte tant qu'aucune page des listes publiée n'est choisie : tous les
     * liens du plugin (partage, « Mes listes », redirections) en dépendent.
     */
    public function render_missing_page_notice(): void {
        if ( ! current_user_can( 'mrzgle_manage_lists' ) ) {
            return;
        }

        $page_id = (int) get_option( 'mrzgle_page_id', 0 );
        if ( $page_id && 'publish' === get_post_status( $page_id ) ) {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
            esc_html__( 'MRZ Gift Lists : aucune page des listes n\'est définie. Créez une page contenant le shortcode [mrzgle_lists], puis sélectionnez-la dans les réglages.', 'mrz-gift-lists-exp' ),
            esc_url( admin_url( 'admin.php?page=mrz-gle-settings' ) ),
            esc_html__( 'Ouvrir les réglages', 'mrz-gift-lists-exp' )
        );
    }

    /**
     * Initialise les composants admin
     */
    private function init_components(): void {
        new Pages\ListsPage();
        new Pages\SettingsPage();
    }

    /**
     * Ajoute le menu admin
     */
    public function add_admin_menu(): void {
        // Menu principal
        add_menu_page(
            __( 'MRZ Gift Lists', 'mrz-gift-lists-exp' ),
            __( 'MRZ Gift Lists', 'mrz-gift-lists-exp' ),
            'mrzgle_manage_lists',
            'mrz-gle-lists',
            [ $this, 'render_lists_page' ],
            $this->get_menu_icon(),
            '80.9' // Juste sous « Réglages » (position 80).
        );

        // Sous-menu Listes (remplace le menu parent)
        add_submenu_page(
            'mrz-gle-lists',
            __( 'Toutes les listes', 'mrz-gift-lists-exp' ),
            __( 'Toutes les listes', 'mrz-gift-lists-exp' ),
            'mrzgle_manage_lists',
            'mrz-gle-lists',
            [ $this, 'render_lists_page' ]
        );

        // Sous-menu Réglages
        add_submenu_page(
            'mrz-gle-lists',
            __( 'Réglages', 'mrz-gift-lists-exp' ),
            __( 'Réglages', 'mrz-gift-lists-exp' ),
            'mrzgle_manage_lists',
            'mrz-gle-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Retourne l'icône du menu admin.
     *
     * Le SVG du plugin est encodé en base64 pour add_menu_page(). Sa couleur
     * est passée au gris WordPress standard (#a7aaad) pour rester visible sur
     * le fond sombre du menu ; le fichier source reste inchangé. Repli sur un
     * dashicon si le fichier est absent.
     *
     * @return string
     */
    private function get_menu_icon(): string {
        $file = MRZGLE_PLUGIN_DIR . 'icon-gift-lists.svg';

        if ( ! is_readable( $file ) ) {
            return 'dashicons-gifts';
        }

        $svg = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

        if ( ! $svg ) {
            return 'dashicons-gifts';
        }

        $svg = str_replace(
            [ 'fill="black"', 'fill="#000000"', 'fill="#000"' ],
            'fill="#a7aaad"',
            $svg
        );

        return 'data:image/svg+xml;base64,' . base64_encode( $svg );
    }

    /**
     * Rendu de la page des listes
     */
    public function render_lists_page(): void {
        $page = new Pages\ListsPage();
        $page->render();
    }

    /**
     * Rendu de la page des réglages
     */
    public function render_settings_page(): void {
        $page = new Pages\SettingsPage();
        $page->render();
    }

    /**
     * Charge les assets admin
     *
     * @param string $hook Page actuelle
     */
    public function enqueue_assets( string $hook ): void {
        // Seulement sur nos pages
        if ( strpos( $hook, 'mrz-gle-' ) === false && strpos( $hook, 'mrzgle_' ) === false ) {
            return;
        }

        wp_enqueue_style( 'mrz-gle-admin' );
        wp_enqueue_script( 'mrz-gle-admin' );

        // Media uploader pour les images
        wp_enqueue_media();
    }

    /**
     * Ajoute les liens sur la page des plugins
     *
     * @param array $links Liens existants
     * @return array
     */
    public function add_plugin_links( array $links ): array {
        $plugin_links = [
            '<a href="' . admin_url( 'admin.php?page=mrz-gle-settings' ) . '">' . __( 'Réglages', 'mrz-gift-lists-exp' ) . '</a>',
        ];

        return array_merge( $plugin_links, $links );
    }

    /**
     * Affiche une notice admin
     *
     * @param string $message Message à afficher
     * @param string $type    Type de notice (success, error, warning, info)
     */
    public static function add_notice( string $message, string $type = 'success' ): void {
        add_settings_error(
            'mrzgle_notices',
            'mrzgle_notice',
            $message,
            $type
        );
    }

    /**
     * Affiche les notices
     */
    public static function display_notices(): void {
        settings_errors( 'mrzgle_notices' );
    }
}
