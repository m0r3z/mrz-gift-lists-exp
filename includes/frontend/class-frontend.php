<?php
/**
 * Classe principale Frontend
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Frontend
 * Gère l'affichage côté public
 */
class Frontend {

    /**
     * Constructeur
     */
    public function __construct() {
        $this->init_hooks();
        $this->init_shortcodes();
    }

    /**
     * Initialise les hooks
     */
    private function init_hooks(): void {
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'template_redirect', [ $this, 'handle_owner_list_actions' ] );
    }

    /**
     * Initialise les shortcodes
     */
    private function init_shortcodes(): void {
        new Shortcodes\GiftListsShortcode();
    }

    /**
     * Charge les assets
     */
    public function enqueue_assets(): void {
        // Charge sur les pages de listes, et sur les pages produit pour les
        // utilisateurs connectés (bouton « Ajouter à une liste »).
        $is_product_page = is_user_logged_in()
            && function_exists( 'is_product' )
            && is_product();

        if ( ! $this->is_gift_lists_page() && ! $is_product_page ) {
            return;
        }

        wp_enqueue_style( 'mrz-gle-frontend' );
        wp_enqueue_script( 'mrz-gle-frontend' );
    }

    /**
     * Vérifie si on est sur une page de listes
     *
     * @return bool
     */
    private function is_gift_lists_page(): bool {
        global $post;

        if ( ! $post ) {
            return false;
        }

        // Page des listes
        $page_id = get_option( 'mrz_gle_page_id' );
        if ( $page_id && $post->ID == $page_id ) {
            return true;
        }

        // Contient le shortcode
        if ( has_shortcode( $post->post_content, 'mrz_gift_lists' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Traite les actions du propriétaire sur sa liste (activer / désactiver /
     * supprimer) soumises en POST depuis la fiche, puis redirige.
     */
    public function handle_owner_list_actions(): void {
        if ( empty( $_POST['mrz_gle_list_action'] ) || ! is_user_logged_in() ) {
            return;
        }

        $action  = sanitize_key( wp_unslash( $_POST['mrz_gle_list_action'] ) );
        $list_id = isset( $_POST['mrz_gle_list_id'] ) ? absint( $_POST['mrz_gle_list_id'] ) : 0;

        if ( ! $list_id || ! in_array( $action, [ 'activate', 'deactivate', 'delete' ], true ) ) {
            return;
        }

        // Nonce lié à la liste concernée.
        $nonce = isset( $_POST['mrz_gle_list_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mrz_gle_list_nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'mrz_gle_owner_list_action_' . $list_id ) ) {
            return;
        }

        $list_repo = new \MrzGiftsListsExp\Repositories\ListRepository();

        // Seul le propriétaire (ou un administrateur) peut agir.
        if ( ! $list_repo->user_owns_list( $list_id, get_current_user_id() ) && ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $list = $list_repo->find( $list_id );
        if ( ! $list ) {
            return;
        }

        $plugin     = \MrzGiftsListsExp\Plugin::instance();
        $list_url   = $plugin->get_list_url( $list->access_key );
        $lists_page = add_query_arg( 'action', 'my-lists', $plugin->get_lists_page_url() );

        // Où revenir après une bascule d'état : la liste des listes ou la fiche.
        $return = isset( $_POST['mrz_gle_return'] ) ? sanitize_key( wp_unslash( $_POST['mrz_gle_return'] ) ) : 'list';
        $back   = ( 'my-lists' === $return ) ? $lists_page : $list_url;

        switch ( $action ) {
            case 'activate':
                $list_repo->update_status( $list_id, 'active' );
                $redirect = $back;
                break;

            case 'deactivate':
                $list_repo->update_status( $list_id, 'archived' );
                $redirect = $back;
                break;

            case 'delete':
                $item_repo = new \MrzGiftsListsExp\Repositories\ItemRepository();
                $item_repo->delete_by_list( $list_id );
                $list_repo->delete( $list_id );
                $redirect = $lists_page;
                break;

            default:
                return;
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Vérifie si l'utilisateur peut créer une liste
     *
     * @return bool
     */
    public static function can_create_list(): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        // Les administrateurs WordPress peuvent toujours créer des listes,
        // sans restriction de nombre.
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Vérifie la limite de listes
        $max_lists = (int) get_option( 'mrz_gle_max_lists_per_user', 5 );
        if ( $max_lists > 0 ) {
            $list_repo = new \MrzGiftsListsExp\Repositories\ListRepository();
            $current_count = $list_repo->count_by_user( get_current_user_id(), 'active' );

            if ( $current_count >= $max_lists ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retourne l'URL de connexion / compte : page WooCommerce « Mon compte »
     * si disponible, sinon la page de connexion WordPress.
     *
     * @return string
     */
    public static function get_account_url(): string {
        if ( function_exists( 'wc_get_page_permalink' ) ) {
            $url = wc_get_page_permalink( 'myaccount' );
            if ( $url ) {
                return $url;
            }
        }

        return wp_login_url();
    }

    /**
     * Retourne le message d'erreur si l'utilisateur ne peut pas créer de liste
     *
     * @return string
     */
    public static function get_cannot_create_message(): string {
        if ( ! is_user_logged_in() ) {
            return sprintf(
                __( 'Vous devez <a href="%s">vous connecter</a> pour créer une liste.', 'mrz-gift-lists-exp' ),
                esc_url( self::get_account_url() )
            );
        }

        $max_lists = (int) get_option( 'mrz_gle_max_lists_per_user', 5 );
        if ( $max_lists > 0 ) {
            $list_repo = new \MrzGiftsListsExp\Repositories\ListRepository();
            $current_count = $list_repo->count_by_user( get_current_user_id(), 'active' );

            if ( $current_count >= $max_lists ) {
                return sprintf(
                    __( 'Vous avez atteint le nombre maximum de listes actives (%d).', 'mrz-gift-lists-exp' ),
                    $max_lists
                );
            }
        }

        return __( 'Vous n\'avez pas les droits pour créer une liste.', 'mrz-gift-lists-exp' );
    }
}
