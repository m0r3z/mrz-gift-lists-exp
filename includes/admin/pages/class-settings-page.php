<?php
/**
 * Page des réglages
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Admin\Pages;

defined( 'ABSPATH' ) || exit;

/**
 * Classe SettingsPage
 * Gère les réglages du plugin
 */
class SettingsPage {

    /**
     * Groupe d'options
     *
     * @var string
     */
    private string $option_group = 'mrz_gle_settings';

    /**
     * Constructeur
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Enregistre les réglages
     */
    public function register_settings(): void {
        // Section Général
        add_settings_section(
            'mrz_gle_general_section',
            __( 'Réglages généraux', 'mrz-gift-lists-exp' ),
            [ $this, 'render_general_section' ],
            $this->option_group
        );

        // Page des listes
        register_setting( $this->option_group, 'mrz_gle_page_id', 'absint' );
        add_settings_field(
            'mrz_gle_page_id',
            __( 'Page des listes', 'mrz-gift-lists-exp' ),
            [ $this, 'render_page_field' ],
            $this->option_group,
            'mrz_gle_general_section'
        );

        // Page boutique (cible du bouton « Ajouter un produit »)
        register_setting( $this->option_group, 'mrz_gle_shop_page_id', 'absint' );
        add_settings_field(
            'mrz_gle_shop_page_id',
            __( 'Page boutique', 'mrz-gift-lists-exp' ),
            [ $this, 'render_shop_page_field' ],
            $this->option_group,
            'mrz_gle_general_section'
        );

        // Nombre max de listes par utilisateur
        register_setting( $this->option_group, 'mrz_gle_max_lists_per_user', 'absint' );
        add_settings_field(
            'mrz_gle_max_lists_per_user',
            __( 'Listes max par utilisateur', 'mrz-gift-lists-exp' ),
            [ $this, 'render_max_lists_field' ],
            $this->option_group,
            'mrz_gle_general_section'
        );

        // Section Affichage
        add_settings_section(
            'mrz_gle_display_section',
            __( 'Affichage', 'mrz-gift-lists-exp' ),
            [ $this, 'render_display_section' ],
            $this->option_group
        );

        // Affichage des produits
        register_setting( $this->option_group, 'mrz_gle_display_mode', 'sanitize_text_field' );
        add_settings_field(
            'mrz_gle_display_mode',
            __( 'Mode d\'affichage', 'mrz-gift-lists-exp' ),
            [ $this, 'render_display_mode_field' ],
            $this->option_group,
            'mrz_gle_display_section'
        );

        // Produits par page
        register_setting( $this->option_group, 'mrz_gle_products_per_page', 'absint' );
        add_settings_field(
            'mrz_gle_products_per_page',
            __( 'Produits par page', 'mrz-gift-lists-exp' ),
            [ $this, 'render_products_per_page_field' ],
            $this->option_group,
            'mrz_gle_display_section'
        );

        // Section Emails
        add_settings_section(
            'mrz_gle_email_section',
            __( 'Emails', 'mrz-gift-lists-exp' ),
            [ $this, 'render_email_section' ],
            $this->option_group
        );

        // Notification admin
        register_setting( $this->option_group, 'mrz_gle_admin_email', 'sanitize_email' );
        add_settings_field(
            'mrz_gle_admin_email',
            __( 'Email administrateur', 'mrz-gift-lists-exp' ),
            [ $this, 'render_admin_email_field' ],
            $this->option_group,
            'mrz_gle_email_section'
        );

        // Notifier le créateur
        register_setting( $this->option_group, 'mrz_gle_notify_owner_on_purchase', 'absint' );
        add_settings_field(
            'mrz_gle_notify_owner_on_purchase',
            __( 'Notifier le créateur', 'mrz-gift-lists-exp' ),
            [ $this, 'render_notify_owner_field' ],
            $this->option_group,
            'mrz_gle_email_section'
        );

    }

    /**
     * Affiche la page
     */
    public function render(): void {
        if ( ! current_user_can( 'mrz_gle_manage_lists' ) ) {
            wp_die( __( 'Vous n\'avez pas les droits nécessaires.', 'mrz-gift-lists-exp' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Réglages MRZ Gift Lists', 'mrz-gift-lists-exp' ); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( $this->option_group );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // ========================================
    // Sections
    // ========================================

    public function render_general_section(): void {
        echo '<p>' . esc_html__( 'Configurez les options générales du plugin.', 'mrz-gift-lists-exp' ) . '</p>';
    }

    public function render_display_section(): void {
        echo '<p>' . esc_html__( 'Personnalisez l\'affichage des listes.', 'mrz-gift-lists-exp' ) . '</p>';
    }

    public function render_email_section(): void {
        echo '<p>' . esc_html__( 'Configurez les notifications par email.', 'mrz-gift-lists-exp' ) . '</p>';
    }

    // ========================================
    // Champs
    // ========================================

    public function render_page_field(): void {
        $page_id = get_option( 'mrz_gle_page_id', 0 );

        wp_dropdown_pages( [
            'name'              => 'mrz_gle_page_id',
            'selected'          => $page_id,
            'show_option_none'  => __( '-- Sélectionner --', 'mrz-gift-lists-exp' ),
            'option_none_value' => 0,
        ] );

        echo '<p class="description">' . esc_html__( 'Page contenant le shortcode [mrz_gift_lists].', 'mrz-gift-lists-exp' ) . '</p>';
    }

    public function render_shop_page_field(): void {
        $default = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
        $page_id = (int) get_option( 'mrz_gle_shop_page_id', $default > 0 ? $default : 0 );

        wp_dropdown_pages( [
            'name'              => 'mrz_gle_shop_page_id',
            'selected'          => $page_id,
            'show_option_none'  => __( '-- Sélectionner --', 'mrz-gift-lists-exp' ),
            'option_none_value' => 0,
        ] );

        echo '<p class="description">' . esc_html__( 'Page vers laquelle renvoie le bouton « Ajouter un produit » d\'une liste. Par défaut, la page boutique WooCommerce.', 'mrz-gift-lists-exp' ) . '</p>';
    }

    public function render_max_lists_field(): void {
        $value = get_option( 'mrz_gle_max_lists_per_user', 5 );
        ?>
        <input type="number" name="mrz_gle_max_lists_per_user" value="<?php echo esc_attr( $value ); ?>" min="1" max="50" class="small-text">
        <p class="description"><?php esc_html_e( 'Nombre maximum de listes actives par utilisateur (0 = illimité).', 'mrz-gift-lists-exp' ); ?></p>
        <?php
    }

    public function render_display_mode_field(): void {
        $value = get_option( 'mrz_gle_display_mode', 'grid' );
        ?>
        <select name="mrz_gle_display_mode">
            <option value="grid" <?php selected( $value, 'grid' ); ?>>
                <?php esc_html_e( 'Grille', 'mrz-gift-lists-exp' ); ?>
            </option>
            <option value="list" <?php selected( $value, 'list' ); ?>>
                <?php esc_html_e( 'Liste', 'mrz-gift-lists-exp' ); ?>
            </option>
        </select>
        <?php
    }

    public function render_products_per_page_field(): void {
        $value = get_option( 'mrz_gle_products_per_page', 12 );
        ?>
        <input type="number" name="mrz_gle_products_per_page" value="<?php echo esc_attr( $value ); ?>" min="4" max="100" class="small-text">
        <?php
    }

    public function render_admin_email_field(): void {
        $value = get_option( 'mrz_gle_admin_email', get_option( 'admin_email' ) );
        ?>
        <input type="email" name="mrz_gle_admin_email" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Email pour recevoir les notifications (laisser vide pour désactiver).', 'mrz-gift-lists-exp' ); ?></p>
        <?php
    }

    public function render_notify_owner_field(): void {
        $value = get_option( 'mrz_gle_notify_owner_on_purchase', 1 );
        ?>
        <label>
            <input type="checkbox" name="mrz_gle_notify_owner_on_purchase" value="1" <?php checked( $value, 1 ); ?>>
            <?php esc_html_e( 'Envoyer un email au créateur de la liste quand un produit est acheté', 'mrz-gift-lists-exp' ); ?>
        </label>
        <?php
    }

}
