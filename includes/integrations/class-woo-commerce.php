<?php
/**
 * Intégration WooCommerce
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Integrations;

use MrzGiftsListsExp\Repositories\ListRepository;
use MrzGiftsListsExp\Repositories\ItemRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Classe WooCommerce
 * Gère l'intégration avec WooCommerce
 */
class WooCommerce {

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

        $this->init_hooks();
    }

    /**
     * Initialise les hooks WooCommerce
     */
    private function init_hooks(): void {
        // Mon compte - Endpoint
        add_action( 'init', [ $this, 'add_endpoints' ] );
        add_filter( 'woocommerce_account_menu_items', [ $this, 'add_account_menu_item' ] );
        add_action( 'woocommerce_account_mrz-gift-lists_endpoint', [ $this, 'render_account_page' ] );

        // Panier - Ajout de métadonnées
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'add_cart_item_data' ], 10, 3 );
        add_filter( 'woocommerce_get_item_data', [ $this, 'display_cart_item_data' ], 10, 2 );

        // Commande - Sauvegarde des métadonnées
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_order_item_meta' ], 10, 4 );

        // Commande - Changement de statut
        add_action( 'woocommerce_order_status_completed', [ $this, 'on_order_completed' ] );
        add_action( 'woocommerce_order_status_processing', [ $this, 'on_order_completed' ] );

        // Champ personnalisé checkout (message pour le créateur)
        add_action( 'woocommerce_after_order_notes', [ $this, 'add_checkout_fields' ] );
        add_action( 'woocommerce_checkout_create_order', [ $this, 'save_checkout_fields' ], 10, 2 );

        // Admin - Colonne commandes
        add_filter( 'manage_edit-shop_order_columns', [ $this, 'add_order_column' ], 20 );
        add_action( 'manage_shop_order_posts_custom_column', [ $this, 'render_order_column' ], 10, 2 );

        // Admin - Détail commande
        add_action( 'woocommerce_admin_order_data_after_billing_address', [ $this, 'display_order_meta' ] );

        // Page produit - Bouton "Ajouter à une liste"
        // Après le formulaire panier (hors du flex du thème) : le bloc s'affiche
        // en pleine largeur, sous « Ajouter au panier », et déroule vers le bas.
        add_action( 'woocommerce_after_add_to_cart_form', [ $this, 'add_to_list_button' ] );

        // Compatibilité HPOS
        add_filter( 'woocommerce_order_item_display_meta_key', [ $this, 'display_meta_key' ], 10, 3 );
    }

    /**
     * Ajoute l'endpoint Mon Compte
     */
    public function add_endpoints(): void {
        add_rewrite_endpoint( 'mrz-gift-lists', EP_ROOT | EP_PAGES );
    }

    /**
     * Ajoute l'item au menu Mon Compte
     *
     * @param array $items Items du menu
     * @return array
     */
    public function add_account_menu_item( array $items ): array {
        // Insère après "Commandes"
        $new_items = [];

        foreach ( $items as $key => $value ) {
            $new_items[ $key ] = $value;

            if ( 'orders' === $key ) {
                $new_items['mrz-gift-lists'] = __( 'Mes listes de cadeaux', 'mrz-gift-lists-exp' );
            }
        }

        return $new_items;
    }

    /**
     * Affiche la page Mon Compte
     */
    public function render_account_page(): void {
        echo do_shortcode( '[mrz_gift_lists view="my-lists"]' );
    }

    /**
     * Ajoute les données au panier
     *
     * @param array $cart_item_data Données du panier
     * @param int   $product_id     ID du produit
     * @param int   $variation_id   ID de la variation
     * @return array
     */
    public function add_cart_item_data( array $cart_item_data, int $product_id, int $variation_id ): array {
        // Vérifie si on ajoute depuis une liste
        if ( isset( $_POST['mrz_gle_list_id'] ) && isset( $_POST['mrz_gle_item_id'] ) ) {
            // Vérifie le nonce
            if ( ! isset( $_POST['mrz_gle_nonce'] ) || ! wp_verify_nonce( $_POST['mrz_gle_nonce'], 'mrz_gle_add_to_cart' ) ) {
                return $cart_item_data;
            }

            $list_id = absint( $_POST['mrz_gle_list_id'] );
            $item_id = absint( $_POST['mrz_gle_item_id'] );

            // Vérifie que l'item existe dans la liste
            $item = $this->item_repo->find( $item_id );
            if ( $item && $item->list_id === $list_id ) {
                $list = $this->list_repo->find( $list_id );

                $cart_item_data['mrz_gle_list_id'] = $list_id;
                $cart_item_data['mrz_gle_item_id'] = $item_id;
                $cart_item_data['mrz_gle_list_title'] = $list ? $list->title : '';

                // Clé unique pour permettre le même produit plusieurs fois
                $cart_item_data['unique_key'] = md5( microtime() . rand() );
            }
        }

        return $cart_item_data;
    }

    /**
     * Affiche les données dans le panier
     *
     * @param array $item_data Données à afficher
     * @param array $cart_item Item du panier
     * @return array
     */
    public function display_cart_item_data( array $item_data, array $cart_item ): array {
        if ( isset( $cart_item['mrz_gle_list_title'] ) ) {
            $item_data[] = [
                'key'   => __( 'Liste de cadeaux', 'mrz-gift-lists-exp' ),
                'value' => $cart_item['mrz_gle_list_title'],
            ];
        }

        return $item_data;
    }

    /**
     * Sauvegarde les métadonnées dans la commande
     *
     * @param \WC_Order_Item_Product $item          Item de commande
     * @param string                 $cart_item_key Clé du panier
     * @param array                  $values        Valeurs
     * @param \WC_Order              $order         Commande
     */
    public function save_order_item_meta( $item, $cart_item_key, $values, $order ): void {
        if ( isset( $values['mrz_gle_list_id'] ) ) {
            $item->add_meta_data( '_mrz_gle_list_id', $values['mrz_gle_list_id'] );
            $item->add_meta_data( '_mrz_gle_item_id', $values['mrz_gle_item_id'] );
            $item->add_meta_data( '_mrz_gle_list_title', $values['mrz_gle_list_title'] );
        }
    }

    /**
     * Traite la commande complétée
     *
     * @param int $order_id ID de la commande
     */
    public function on_order_completed( int $order_id ): void {
        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return;
        }

        // Récupère le nom de l'acheteur
        $buyer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        foreach ( $order->get_items() as $item ) {
            $list_id = $item->get_meta( '_mrz_gle_list_id' );
            $item_id = $item->get_meta( '_mrz_gle_item_id' );

            if ( $list_id && $item_id ) {
                $quantity = $item->get_quantity();

                // Marque le produit comme acheté
                $this->item_repo->mark_as_purchased(
                    absint( $item_id ),
                    $order_id,
                    $buyer_name,
                    $quantity
                );

                // Déclenche l'action pour les notifications
                do_action( 'mrz_gle_item_purchased', $item_id, $list_id, $order_id, $buyer_name );
            }
        }
    }

    /**
     * Ajoute les champs checkout
     *
     * @param \WC_Checkout $checkout Checkout
     */
    public function add_checkout_fields( $checkout ): void {
        // Vérifie si le panier contient des produits de liste
        $has_list_items = false;

        foreach ( WC()->cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['mrz_gle_list_id'] ) ) {
                $has_list_items = true;
                break;
            }
        }

        if ( ! $has_list_items ) {
            return;
        }

        echo '<div class="mrz-gle-checkout-fields">';
        echo '<h3>' . esc_html__( 'Message pour le créateur de la liste', 'mrz-gift-lists-exp' ) . '</h3>';

        woocommerce_form_field( 'mrz_gle_gift_message', [
            'type'        => 'textarea',
            'class'       => [ 'form-row-wide' ],
            'label'       => __( 'Votre message (optionnel)', 'mrz-gift-lists-exp' ),
            'placeholder' => __( 'Laissez un petit mot pour accompagner votre cadeau...', 'mrz-gift-lists-exp' ),
        ], $checkout->get_value( 'mrz_gle_gift_message' ) );

        echo '</div>';
    }

    /**
     * Sauvegarde les champs checkout
     *
     * @param \WC_Order $order Commande
     * @param array     $data  Données
     */
    public function save_checkout_fields( $order, $data ): void {
        if ( isset( $_POST['mrz_gle_gift_message'] ) && ! empty( $_POST['mrz_gle_gift_message'] ) ) {
            $message = sanitize_textarea_field( $_POST['mrz_gle_gift_message'] );
            $order->update_meta_data( '_mrz_gle_gift_message', $message );
        }
    }

    /**
     * Ajoute la colonne dans les commandes admin
     *
     * @param array $columns Colonnes existantes
     * @return array
     */
    public function add_order_column( array $columns ): array {
        $new_columns = [];

        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;

            if ( 'order_total' === $key ) {
                $new_columns['mrz_gle_list'] = __( 'Liste cadeaux', 'mrz-gift-lists-exp' );
            }
        }

        return $new_columns;
    }

    /**
     * Affiche le contenu de la colonne
     *
     * @param string $column  Nom de la colonne
     * @param int    $post_id ID du post
     */
    public function render_order_column( string $column, int $post_id ): void {
        if ( 'mrz_gle_list' !== $column ) {
            return;
        }

        $order = wc_get_order( $post_id );

        if ( ! $order ) {
            echo '—';
            return;
        }

        $list_titles = [];

        foreach ( $order->get_items() as $item ) {
            $list_title = $item->get_meta( '_mrz_gle_list_title' );
            if ( $list_title && ! in_array( $list_title, $list_titles, true ) ) {
                $list_titles[] = $list_title;
            }
        }

        if ( empty( $list_titles ) ) {
            echo '—';
        } else {
            echo '<span class="mrz-gle-order-lists">';
            echo esc_html( implode( ', ', $list_titles ) );
            echo '</span>';
        }
    }

    /**
     * Affiche les métadonnées dans le détail commande
     *
     * @param \WC_Order $order Commande
     */
    public function display_order_meta( $order ): void {
        $message = $order->get_meta( '_mrz_gle_gift_message' );

        if ( ! $message ) {
            return;
        }
        ?>
        <div class="mrz-gle-order-meta">
            <h4><?php esc_html_e( 'Message cadeau', 'mrz-gift-lists-exp' ); ?></h4>
            <p><?php echo wp_kses_post( nl2br( $message ) ); ?></p>
        </div>
        <?php
    }

    /**
     * Ajoute le bouton "Ajouter à une liste" sur la page produit
     */
    public function add_to_list_button(): void {
        if ( ! is_user_logged_in() ) {
            return;
        }

        global $product;

        if ( ! $product ) {
            return;
        }

        // Récupère les listes de l'utilisateur
        $lists = $this->list_repo->find_by_user( get_current_user_id(), [ 'status' => 'active' ] );

        if ( empty( $lists ) ) {
            return;
        }
        ?>
        <div class="mrz-gle-add-to-list-wrapper">
            <button type="button" class="button mrz-gle-add-to-list-toggle">
                <?php esc_html_e( '+ Ajouter à une liste', 'mrz-gift-lists-exp' ); ?>
            </button>

            <div class="mrz-gle-add-to-list-dropdown" style="display: none;">
                <div class="mrz-gle-add-to-list-row">
                    <select name="mrz_gle_add_to_list" class="mrz-gle-list-select" data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
                        <option value=""><?php esc_html_e( 'Choisir une liste...', 'mrz-gift-lists-exp' ); ?></option>
                        <?php foreach ( $lists as $list ) : ?>
                            <option value="<?php echo esc_attr( $list->id ); ?>">
                                <?php echo esc_html( $list->title ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button mrz-gle-confirm-add-to-list">
                        <?php esc_html_e( 'Ajouter', 'mrz-gift-lists-exp' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Formate l'affichage des clés de métadonnées
     *
     * @param string                 $display_key Clé à afficher
     * @param \WC_Meta_Data          $meta        Métadonnée
     * @param \WC_Order_Item_Product $item        Item
     * @return string
     */
    public function display_meta_key( string $display_key, $meta, $item ): string {
        $custom_keys = [
            '_mrz_gle_list_title' => __( 'Liste de cadeaux', 'mrz-gift-lists-exp' ),
        ];

        if ( isset( $custom_keys[ $display_key ] ) ) {
            return $custom_keys[ $display_key ];
        }

        // Cache les métadonnées internes
        if ( in_array( $display_key, [ '_mrz_gle_list_id', '_mrz_gle_item_id' ], true ) ) {
            return '';
        }

        return $display_key;
    }
}
