<?php
/**
 * Handler AJAX
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Ajax;

use MrzGiftsListsExp\Repositories\ListRepository;
use MrzGiftsListsExp\Repositories\ItemRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Handler
 * Gère toutes les requêtes AJAX du plugin
 */
class Handler {

    /**
     * Repository des listes
     *
     * @var ListRepository
     */
    private ListRepository $list_repo;

    /**
     * Repository des items
     *
     * @var ItemRepository
     */
    private ItemRepository $item_repo;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->list_repo = new ListRepository();
        $this->item_repo = new ItemRepository();

        $this->register_actions();
    }

    /**
     * Enregistre les actions AJAX
     */
    private function register_actions(): void {
        // Actions pour utilisateurs connectés
        $logged_in_actions = [
            'mrz_gle_add_product_to_list',
            'mrz_gle_remove_product_from_list',
            'mrz_gle_toggle_priority',
            'mrz_gle_update_list',
            'mrz_gle_delete_list',
        ];

        foreach ( $logged_in_actions as $action ) {
            add_action( 'wp_ajax_' . $action, [ $this, str_replace( 'mrz_gle_', '', $action ) ] );
        }

        // Actions pour tous (connectés + non connectés)
        $public_actions = [
            'mrz_gle_add_to_cart',
        ];

        foreach ( $public_actions as $action ) {
            add_action( 'wp_ajax_' . $action, [ $this, str_replace( 'mrz_gle_', '', $action ) ] );
            add_action( 'wp_ajax_nopriv_' . $action, [ $this, str_replace( 'mrz_gle_', '', $action ) ] );
        }
    }

    /**
     * Vérifie le nonce et les permissions
     *
     * @param string $nonce_action Action du nonce
     * @param bool   $check_login  Vérifier la connexion
     * @return bool
     */
    private function verify_request( string $nonce_action = 'mrz_gle_frontend_nonce', bool $check_login = true ): bool {
        // Vérifie le nonce
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
            wp_send_json_error( [
                'message' => __( 'Erreur de sécurité. Veuillez rafraîchir la page.', 'mrz-gift-lists-exp' ),
            ] );
            return false;
        }

        // Vérifie la connexion si nécessaire
        if ( $check_login && ! is_user_logged_in() ) {
            wp_send_json_error( [
                'message' => __( 'Vous devez être connecté.', 'mrz-gift-lists-exp' ),
            ] );
            return false;
        }

        return true;
    }

    /**
     * Vérifie si l'utilisateur est propriétaire de la liste
     *
     * @param int $list_id ID de la liste
     * @return bool
     */
    private function verify_ownership( int $list_id ): bool {
        if ( ! $this->list_repo->user_owns_list( $list_id, get_current_user_id() ) ) {
            wp_send_json_error( [
                'message' => __( 'Vous n\'êtes pas autorisé à modifier cette liste.', 'mrz-gift-lists-exp' ),
            ] );
            return false;
        }

        return true;
    }

    /**
     * Ajoute un produit à une liste
     */
    public function add_product_to_list(): void {
        $this->verify_request();

        $list_id = isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        if ( ! $list_id || ! $product_id ) {
            wp_send_json_error( [ 'message' => __( 'Données manquantes.', 'mrz-gift-lists-exp' ) ] );
        }

        if ( ! $this->verify_ownership( $list_id ) ) {
            return;
        }

        // Vérifie que le produit existe
        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            wp_send_json_error( [ 'message' => __( 'Produit introuvable.', 'mrz-gift-lists-exp' ) ] );
        }

        // Ajoute le produit
        $item_id = $this->item_repo->add_to_list( $list_id, $product_id, [
            'quantity' => max( 1, $quantity ),
        ] );

        if ( ! $item_id ) {
            wp_send_json_error( [ 'message' => __( 'Ce produit est déjà dans la liste.', 'mrz-gift-lists-exp' ) ] );
        }

        wp_send_json_success( [
            'message' => __( 'Produit ajouté à la liste.', 'mrz-gift-lists-exp' ),
            'item_id' => $item_id,
            'product' => [
                'id'    => $product->get_id(),
                'name'  => $product->get_name(),
                'price' => $product->get_price_html(),
                'image' => wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' ),
            ],
        ] );
    }

    /**
     * Retire un produit d'une liste
     */
    public function remove_product_from_list(): void {
        $this->verify_request();

        $item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;

        if ( ! $item_id ) {
            wp_send_json_error( [ 'message' => __( 'Données manquantes.', 'mrz-gift-lists-exp' ) ] );
        }

        // Récupère l'item pour vérifier la propriété
        $item = $this->item_repo->find( $item_id );

        if ( ! $item ) {
            wp_send_json_error( [ 'message' => __( 'Produit introuvable.', 'mrz-gift-lists-exp' ) ] );
        }

        if ( ! $this->verify_ownership( (int) $item->list_id ) ) {
            return;
        }

        // Supprime l'item
        if ( $this->item_repo->delete( $item_id ) ) {
            wp_send_json_success( [ 'message' => __( 'Produit retiré de la liste.', 'mrz-gift-lists-exp' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la suppression.', 'mrz-gift-lists-exp' ) ] );
        }
    }

    /**
     * Bascule la priorité d'un produit
     */
    public function toggle_priority(): void {
        $this->verify_request();

        $item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;

        if ( ! $item_id ) {
            wp_send_json_error( [ 'message' => __( 'Données manquantes.', 'mrz-gift-lists-exp' ) ] );
        }

        $item = $this->item_repo->find( $item_id );

        if ( ! $item ) {
            wp_send_json_error( [ 'message' => __( 'Produit introuvable.', 'mrz-gift-lists-exp' ) ] );
        }

        if ( ! $this->verify_ownership( (int) $item->list_id ) ) {
            return;
        }

        $new_priority = $this->item_repo->toggle_priority( $item_id );

        wp_send_json_success( [
            'message'  => $new_priority
                ? __( 'Marqué comme prioritaire.', 'mrz-gift-lists-exp' )
                : __( 'Priorité retirée.', 'mrz-gift-lists-exp' ),
            'priority' => $new_priority,
        ] );
    }

    /**
     * Met à jour une liste
     */
    public function update_list(): void {
        $this->verify_request();

        $list_id = isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;

        if ( ! $list_id ) {
            wp_send_json_error( [ 'message' => __( 'Données manquantes.', 'mrz-gift-lists-exp' ) ] );
        }

        if ( ! $this->verify_ownership( $list_id ) ) {
            return;
        }

        $data = [];

        if ( isset( $_POST['title'] ) ) {
            $data['title'] = sanitize_text_field( wp_unslash( $_POST['title'] ) );
        }

        if ( isset( $_POST['description'] ) ) {
            $data['description'] = sanitize_textarea_field( wp_unslash( $_POST['description'] ) );
        }

        if ( isset( $_POST['event_date'] ) ) {
            $data['event_date'] = sanitize_text_field( wp_unslash( $_POST['event_date'] ) );
        }

        if ( isset( $_POST['status'] ) ) {
            $data['status'] = sanitize_key( $_POST['status'] );
        }

        if ( empty( $data ) ) {
            wp_send_json_error( [ 'message' => __( 'Aucune donnée à mettre à jour.', 'mrz-gift-lists-exp' ) ] );
        }

        if ( $this->list_repo->update( $list_id, $data ) ) {
            wp_send_json_success( [ 'message' => __( 'Liste mise à jour.', 'mrz-gift-lists-exp' ) ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la mise à jour.', 'mrz-gift-lists-exp' ) ] );
        }
    }

    /**
     * Supprime une liste
     */
    public function delete_list(): void {
        $this->verify_request();

        $list_id = isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;

        if ( ! $list_id ) {
            wp_send_json_error( [ 'message' => __( 'Données manquantes.', 'mrz-gift-lists-exp' ) ] );
        }

        if ( ! $this->verify_ownership( $list_id ) ) {
            return;
        }

        // Supprime d'abord les items
        $this->item_repo->delete_by_list( $list_id );

        // Puis la liste
        if ( $this->list_repo->delete( $list_id ) ) {
            wp_send_json_success( [
                'message'  => __( 'Liste supprimée.', 'mrz-gift-lists-exp' ),
                'redirect' => \MrzGiftsListsExp\Plugin::instance()->get_lists_page_url(),
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de la suppression.', 'mrz-gift-lists-exp' ) ] );
        }
    }

    /**
     * Ajoute un produit au panier depuis une liste
     */
    public function add_to_cart(): void {
        $this->verify_request( 'mrz_gle_frontend_nonce', false );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $list_id = isset( $_POST['list_id'] ) ? absint( $_POST['list_id'] ) : 0;
        $item_id = isset( $_POST['item_id'] ) ? absint( $_POST['item_id'] ) : 0;
        $quantity = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1;

        if ( ! $product_id || ! $list_id || ! $item_id ) {
            wp_send_json_error( [ 'message' => __( 'Données manquantes.', 'mrz-gift-lists-exp' ) ] );
        }

        // Vérifie que le produit est dans la liste
        $item = $this->item_repo->find( $item_id );

        if ( ! $item || (int) $item->list_id !== $list_id ) {
            wp_send_json_error( [ 'message' => __( 'Produit introuvable dans la liste.', 'mrz-gift-lists-exp' ) ] );
        }

        // Calcule la quantité restante
        $quantity_remaining = max( 0, (int) $item->quantity - (int) $item->quantity_purchased );

        // Vérifie que le produit n'est pas déjà entièrement acheté
        if ( $quantity_remaining <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Ce produit a déjà été offert.', 'mrz-gift-lists-exp' ) ] );
        }

        // Limite la quantité disponible
        $quantity = min( max( 1, $quantity ), $quantity_remaining );

        // Ajoute au panier WooCommerce
        $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, 0, [], [
            'mrz_gle_list_id'    => $list_id,
            'mrz_gle_item_id'    => $item_id,
            'mrz_gle_list_title' => $this->list_repo->find( $list_id )->title ?? '',
            'unique_key'      => md5( microtime() . rand() ),
        ] );

        if ( $cart_item_key ) {
            wp_send_json_success( [
                'message'   => __( 'Produit ajouté au panier.', 'mrz-gift-lists-exp' ),
                'cart_url'  => wc_get_cart_url(),
                'cart_hash' => WC()->cart->get_cart_hash(),
            ] );
        } else {
            wp_send_json_error( [ 'message' => __( 'Erreur lors de l\'ajout au panier.', 'mrz-gift-lists-exp' ) ] );
        }
    }
}
