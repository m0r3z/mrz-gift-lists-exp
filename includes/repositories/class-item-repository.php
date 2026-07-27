<?php
/**
 * Repository des produits de liste
 *
 * @package Mrzgle
 */

namespace Mrzgle\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ItemRepository
 * Gestion des produits dans les listes
 */
class ItemRepository extends BaseRepository {

    /**
     * Constructeur
     */
    public function __construct() {
        parent::__construct();
        $this->table = MRZGLE_TABLE_ITEMS;
    }

    /**
     * Récupère les produits d'une liste
     *
     * @param int   $list_id ID de la liste
     * @param array $args    Arguments
     * @return array
     */
    public function find_by_list( int $list_id, array $args = [] ): array {
        $defaults = [
            'status'  => null,
            'orderby' => 'priority',
            'order'   => 'DESC',
        ];

        $args = wp_parse_args( $args, $defaults );

        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE list_id = %d",
            $list_id
        );

        if ( $args['status'] ) {
            $sql .= $this->db->prepare( ' AND status = %s', $args['status'] );
        }

        // Double tri : d'abord les favoris, puis par ordre
        if ( 'priority' === $args['orderby'] ) {
            $sql .= ' ORDER BY priority DESC, created_at ASC';
        } else {
            $sql .= $this->build_order_clause( $args['orderby'], $args['order'] );
        }

        $items = $this->db->get_results( $sql );

        // Enrichit avec les données WooCommerce
        return $this->enrich_with_product_data( $items );
    }

    /**
     * Récupère un produit d'une liste
     *
     * @param int $list_id    ID de la liste
     * @param int $product_id ID du produit
     * @param int $variation_id ID de la variation (optionnel)
     * @return object|null
     */
    public function find_in_list( int $list_id, int $product_id, int $variation_id = 0 ): ?object {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE list_id = %d AND product_id = %d",
            $list_id,
            $product_id
        );

        if ( $variation_id > 0 ) {
            $sql .= $this->db->prepare( ' AND variation_id = %d', $variation_id );
        } else {
            $sql .= ' AND (variation_id IS NULL OR variation_id = 0)';
        }

        $sql .= ' LIMIT 1';

        $result = $this->db->get_row( $sql );

        return $result ?: null;
    }

    /**
     * Vérifie si un produit est dans une liste
     *
     * @param int $list_id    ID de la liste
     * @param int $product_id ID du produit
     * @param int $variation_id ID de la variation (optionnel)
     * @return bool
     */
    public function is_in_list( int $list_id, int $product_id, int $variation_id = 0 ): bool {
        return null !== $this->find_in_list( $list_id, $product_id, $variation_id );
    }

    /**
     * Ajoute un produit à une liste
     *
     * @param int   $list_id    ID de la liste
     * @param int   $product_id ID du produit
     * @param array $data       Données additionnelles
     * @return int|false
     */
    public function add_to_list( int $list_id, int $product_id, array $data = [] ) {
        $variation_id = isset( $data['variation_id'] ) ? absint( $data['variation_id'] ) : 0;

        // Vérifie si déjà présent
        if ( $this->is_in_list( $list_id, $product_id, $variation_id ) ) {
            return false;
        }

        // Vérifie que le produit WC existe
        $product = wc_get_product( $variation_id ?: $product_id );
        if ( ! $product ) {
            return false;
        }

        $insert_data = [
            'list_id'      => $list_id,
            'product_id'   => $product_id,
            'variation_id' => $variation_id ?: null,
            'quantity'     => isset( $data['quantity'] ) ? absint( $data['quantity'] ) : 1,
            'priority'     => isset( $data['priority'] ) ? absint( $data['priority'] ) : 0,
            'note'         => isset( $data['note'] ) ? sanitize_textarea_field( $data['note'] ) : null,
            'status'       => 'available',
        ];

        return $this->create( $insert_data );
    }

    /**
     * Retire un produit d'une liste
     *
     * @param int $list_id    ID de la liste
     * @param int $product_id ID du produit
     * @param int $variation_id ID de la variation (optionnel)
     * @return bool
     */
    public function remove_from_list( int $list_id, int $product_id, int $variation_id = 0 ): bool {
        $where = [
            'list_id'    => $list_id,
            'product_id' => $product_id,
        ];

        $where_format = [ '%d', '%d' ];

        if ( $variation_id > 0 ) {
            $where['variation_id'] = $variation_id;
            $where_format[] = '%d';
        }

        $result = $this->db->delete( $this->table, $where, $where_format );

        return false !== $result;
    }

    /**
     * Met à jour le statut d'un produit (acheté, etc.)
     *
     * @param int    $item_id ID de l'item
     * @param string $status  Nouveau statut
     * @param array  $data    Données additionnelles
     * @return bool
     */
    public function update_status( int $item_id, string $status, array $data = [] ): bool {
        $valid_statuses = [ 'available', 'reserved', 'purchased' ];

        if ( ! in_array( $status, $valid_statuses, true ) ) {
            return false;
        }

        $update_data = [ 'status' => $status ];

        if ( 'purchased' === $status ) {
            $update_data['purchased_at'] = current_time( 'mysql' );

            if ( isset( $data['purchased_by'] ) ) {
                $update_data['purchased_by'] = sanitize_text_field( $data['purchased_by'] );
            }

            if ( isset( $data['order_id'] ) ) {
                $update_data['order_id'] = absint( $data['order_id'] );
            }

            if ( isset( $data['quantity_purchased'] ) ) {
                $update_data['quantity_purchased'] = absint( $data['quantity_purchased'] );
            }
        }

        return $this->update( $item_id, $update_data );
    }

    /**
     * Marque un produit comme acheté
     *
     * @param int    $item_id      ID de l'item
     * @param int    $order_id     ID de la commande
     * @param string $buyer_name   Nom de l'acheteur
     * @param int    $quantity     Quantité achetée
     * @return bool
     */
    public function mark_as_purchased( int $item_id, int $order_id, string $buyer_name = '', int $quantity = 1 ): bool {
        $item = $this->find( $item_id );

        if ( ! $item ) {
            return false;
        }

        $new_purchased_qty = $item->quantity_purchased + $quantity;
        $new_status = $new_purchased_qty >= $item->quantity ? 'purchased' : 'available';

        return $this->update( $item_id, [
            'status'             => $new_status,
            'quantity_purchased' => $new_purchased_qty,
            'purchased_by'       => $buyer_name,
            'purchased_at'       => current_time( 'mysql' ),
            'order_id'           => $order_id,
        ] );
    }

    /**
     * Met à jour la priorité (favori)
     *
     * @param int $item_id  ID de l'item
     * @param int $priority Priorité (0 ou 1)
     * @return bool
     */
    public function set_priority( int $item_id, int $priority ): bool {
        return $this->update( $item_id, [ 'priority' => min( 1, max( 0, $priority ) ) ] );
    }

    /**
     * Bascule la priorité (favori)
     *
     * @param int $item_id ID de l'item
     * @return bool Nouvelle valeur de priorité
     */
    public function toggle_priority( int $item_id ): bool {
        $item = $this->find( $item_id );

        if ( ! $item ) {
            return false;
        }

        $new_priority = $item->priority ? 0 : 1;
        $this->set_priority( $item_id, $new_priority );

        return (bool) $new_priority;
    }

    /**
     * Compte les produits d'une liste
     *
     * @param int    $list_id ID de la liste
     * @param string $status  Statut optionnel
     * @return int
     */
    public function count_by_list( int $list_id, string $status = '' ): int {
        $sql = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE list_id = %d",
            $list_id
        );

        if ( $status ) {
            $sql .= $this->db->prepare( ' AND status = %s', $status );
        }

        return (int) $this->db->get_var( $sql );
    }

    /**
     * Récupère les statistiques d'une liste
     *
     * @param int $list_id ID de la liste
     * @return object
     */
    public function get_list_stats( int $list_id ): object {
        $sql = $this->db->prepare(
            "SELECT
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_items,
                SUM(CASE WHEN status = 'purchased' THEN 1 ELSE 0 END) as purchased_items,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved_items,
                SUM(quantity) as total_quantity,
                SUM(quantity_purchased) as purchased_quantity
             FROM {$this->table}
             WHERE list_id = %d",
            $list_id
        );

        $stats = $this->db->get_row( $sql );

        // Valeurs par défaut si aucun résultat
        if ( ! $stats ) {
            $stats = (object) [
                'total_items'        => 0,
                'available_items'    => 0,
                'purchased_items'    => 0,
                'reserved_items'     => 0,
                'total_quantity'     => 0,
                'purchased_quantity' => 0,
            ];
        }

        return $stats;
    }

    /**
     * Supprime tous les produits d'une liste
     *
     * @param int $list_id ID de la liste
     * @return bool
     */
    public function delete_by_list( int $list_id ): bool {
        $result = $this->db->delete(
            $this->table,
            [ 'list_id' => $list_id ],
            [ '%d' ]
        );

        return false !== $result;
    }

    /**
     * Récupère un item par son ID de commande
     *
     * @param int $order_id ID de la commande
     * @return array
     */
    public function find_by_order( int $order_id ): array {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE order_id = %d",
            $order_id
        );

        return $this->db->get_results( $sql );
    }

    /**
     * Enrichit les items avec les données produit WooCommerce
     *
     * @param array $items Items de liste
     * @return array
     */
    private function enrich_with_product_data( array $items ): array {
        foreach ( $items as &$item ) {
            $product_id = $item->variation_id ?: $item->product_id;
            $product = wc_get_product( $product_id );

            if ( $product ) {
                $item->product_name = $product->get_name();
                $item->product_price = $product->get_price();
                $item->product_price_html = $product->get_price_html();
                $item->product_image = wp_get_attachment_image_url( $product->get_image_id(), 'woocommerce_thumbnail' );
                $item->product_url = $product->get_permalink();
                $item->product_stock_status = $product->get_stock_status();
                $item->product_exists = true;

                // Pour les variations
                if ( $item->variation_id && $product instanceof \WC_Product_Variation ) {
                    $item->variation_attributes = $product->get_variation_attributes();
                }
            } else {
                $item->product_name = __( 'Produit non disponible', 'mrz-gift-lists-exp' );
                $item->product_price = 0;
                $item->product_price_html = '';
                $item->product_image = wc_placeholder_img_src();
                $item->product_url = '#';
                $item->product_stock_status = 'outofstock';
                $item->product_exists = false;
            }

            // Calcule la quantité restante
            $item->quantity_remaining = max( 0, $item->quantity - $item->quantity_purchased );
            $item->is_fully_purchased = $item->quantity_remaining <= 0;
        }

        return $items;
    }

    /**
     * Sanitize les données
     *
     * @param array $data Données
     * @return array
     */
    protected function sanitize_data( array $data ): array {
        $sanitized = [];

        foreach ( $data as $key => $value ) {
            switch ( $key ) {
                case 'list_id':
                case 'product_id':
                case 'variation_id':
                case 'order_id':
                case 'quantity':
                case 'quantity_purchased':
                case 'priority':
                    $sanitized[ $key ] = is_null( $value ) ? null : absint( $value );
                    break;

                case 'status':
                    $sanitized[ $key ] = sanitize_key( $value );
                    break;

                case 'note':
                    $sanitized[ $key ] = sanitize_textarea_field( $value );
                    break;

                case 'purchased_by':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;

                case 'purchased_at':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;

                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }

        return $sanitized;
    }
}
