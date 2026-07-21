<?php
/**
 * Tableau des listes pour l'admin
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Admin;

use MrzGiftsListsExp\Repositories\ListRepository;
use MrzGiftsListsExp\Repositories\ItemRepository;

defined( 'ABSPATH' ) || exit;

// Charge WP_List_Table si nécessaire
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Classe ListTable
 * Affiche les listes dans un tableau admin WordPress
 */
class ListTable extends \WP_List_Table {

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
        parent::__construct( [
            'singular' => 'list',
            'plural'   => 'lists',
            'ajax'     => false,
        ] );

        $this->list_repo = new ListRepository();
        $this->item_repo = new ItemRepository();
    }

    /**
     * Colonnes du tableau
     *
     * @return array
     */
    public function get_columns(): array {
        return [
            'title'      => __( 'Titre', 'mrz-gift-lists-exp' ),
            'owner'      => __( 'Créateur', 'mrz-gift-lists-exp' ),
            'items'      => __( 'Produits', 'mrz-gift-lists-exp' ),
            'status'     => __( 'Statut', 'mrz-gift-lists-exp' ),
            'created_at' => __( 'Date', 'mrz-gift-lists-exp' ),
        ];
    }

    /**
     * Colonnes triables
     *
     * @return array
     */
    protected function get_sortable_columns(): array {
        return [
            'title'      => [ 'title', false ],
            'owner'      => [ 'user_id', false ],
            'created_at' => [ 'created_at', true ],
        ];
    }

    /**
     * Prépare les items à afficher
     */
    public function prepare_items(): void {
        $per_page = 20;
        $current_page = $this->get_pagenum();

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [ $columns, $hidden, $sortable ];

        // Récupère les filtres
        $status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at';
        $order = isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'DESC';

        // Construit les arguments de requête
        $args = [
            'orderby' => $orderby,
            'order'   => $order,
            'limit'   => $per_page,
            'offset'  => ( $current_page - 1 ) * $per_page,
        ];

        // Recherche
        if ( $search ) {
            $this->items = $this->list_repo->search( $search, $args );
            $total_items = count( $this->list_repo->search( $search, [ 'limit' => 0 ] ) );
        } else {
            // Filtre par statut — jointure créateur conservée
            $args['status'] = $status;

            $this->items = $this->get_lists_with_details( $args );

            $total_items = $status
                ? $this->list_repo->count( [ 'status' => $status ] )
                : $this->list_repo->count();
        }

        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ] );
    }

    /**
     * Récupère les listes avec leurs détails
     *
     * @param array $args Arguments
     * @return array
     */
    private function get_lists_with_details( array $args ): array {
        global $wpdb;

        // Whitelist stricte des colonnes de tri : $orderby est interpolé dans le
        // SQL (impossible à préparer), donc on n'autorise que des colonnes connues.
        $allowed_orderby = [ 'id', 'title', 'user_id', 'created_at', 'status' ];
        $orderby = in_array( $args['orderby'] ?? '', $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
        $order = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';
        $limit = absint( $args['limit'] ?? 20 );
        $offset = absint( $args['offset'] ?? 0 );
        $status = $args['status'] ?? '';

        $where  = '';
        $params = [];

        if ( $status ) {
            $where    = 'WHERE l.status = %s';
            $params[] = $status;
        }

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT l.*, u.display_name as owner_name, u.user_email as owner_email
                FROM " . MRZ_GIFTS_LISTS_EXP_TABLE_LISTS . " l
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                {$where}
                ORDER BY l.{$orderby} {$order}
                LIMIT %d OFFSET %d";

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }

    /**
     * Colonne par défaut
     *
     * @param object $item        Item
     * @param string $column_name Nom de colonne
     * @return string
     */
    protected function column_default( $item, $column_name ): string {
        return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
    }

    /**
     * Colonne titre
     *
     * @param object $item Item
     * @return string
     */
    protected function column_title( $item ): string {
        $view_url = add_query_arg( [
            'page'    => 'mrz-gle-lists',
            'view'    => 'details',
            'list_id' => $item->id,
        ], admin_url( 'admin.php' ) );

        $delete_url = wp_nonce_url(
            add_query_arg( [
                'page'    => 'mrz-gle-lists',
                'action'  => 'delete',
                'list_id' => $item->id,
            ], admin_url( 'admin.php' ) ),
            'mrz_gle_list_action'
        );

        $front_url = \MrzGiftsListsExp\Plugin::instance()->get_list_url( $item->access_key );

        $actions = [
            'view'   => sprintf(
                '<a href="%s">%s</a>',
                esc_url( $view_url ),
                __( 'Voir détails', 'mrz-gift-lists-exp' )
            ),
            'front'  => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url( $front_url ),
                __( 'Voir sur le site', 'mrz-gift-lists-exp' )
            ),
            'delete' => sprintf(
                '<a href="%s" class="mrz-gle-delete" onclick="return confirm(\'%s\');">%s</a>',
                esc_url( $delete_url ),
                esc_attr__( 'Êtes-vous sûr de vouloir supprimer cette liste ?', 'mrz-gift-lists-exp' ),
                __( 'Supprimer', 'mrz-gift-lists-exp' )
            ),
        ];

        return sprintf(
            '<strong><a href="%s">%s</a></strong> %s',
            esc_url( $view_url ),
            esc_html( $item->title ),
            $this->row_actions( $actions )
        );
    }

    /**
     * Colonne propriétaire
     *
     * @param object $item Item
     * @return string
     */
    protected function column_owner( $item ): string {
        if ( empty( $item->owner_name ) ) {
            return '<em>' . __( 'Utilisateur supprimé', 'mrz-gift-lists-exp' ) . '</em>';
        }

        return sprintf(
            '<a href="%s">%s</a><br><small>%s</small>',
            esc_url( get_edit_user_link( $item->user_id ) ),
            esc_html( $item->owner_name ),
            esc_html( $item->owner_email ?? '' )
        );
    }

    /**
     * Colonne produits
     *
     * @param object $item Item
     * @return string
     */
    protected function column_items( $item ): string {
        $stats = $this->item_repo->get_list_stats( $item->id );

        if ( $stats->total_items <= 0 ) {
            return '<span style="color: #999;">0</span>';
        }

        $purchased = $stats->purchased_items;
        $total = $stats->total_items;
        $percent = $total > 0 ? round( ( $purchased / $total ) * 100 ) : 0;

        return sprintf(
            '%d / %d<br><small style="color: %s;">%d%% %s</small>',
            $purchased,
            $total,
            $percent > 50 ? '#00a32a' : '#666',
            $percent,
            __( 'achetés', 'mrz-gift-lists-exp' )
        );
    }

    /**
     * Colonne statut
     *
     * @param object $item Item
     * @return string
     */
    protected function column_status( $item ): string {
        $statuses = [
            'active'    => [ __( 'Active', 'mrz-gift-lists-exp' ), '#00a32a' ],
            'archived'  => [ __( 'Archivée', 'mrz-gift-lists-exp' ), '#dba617' ],
            'completed' => [ __( 'Terminée', 'mrz-gift-lists-exp' ), '#2271b1' ],
        ];

        $status = $statuses[ $item->status ] ?? [ $item->status, '#666' ];

        return sprintf(
            '<span style="color: %s;">● %s</span>',
            esc_attr( $status[1] ),
            esc_html( $status[0] )
        );
    }

    /**
     * Colonne date
     *
     * @param object $item Item
     * @return string
     */
    protected function column_created_at( $item ): string {
        $timestamp = strtotime( $item->created_at );

        return sprintf(
            '%s<br><small>%s</small>',
            esc_html( wp_date( get_option( 'date_format' ), $timestamp ) ),
            esc_html( wp_date( get_option( 'time_format' ), $timestamp ) )
        );
    }

    /**
     * Message quand aucun item
     */
    public function no_items(): void {
        esc_html_e( 'Aucune liste trouvée.', 'mrz-gift-lists-exp' );
    }

    /**
     * Filtres supplémentaires
     *
     * @param string $which Position (top/bottom)
     */
    protected function extra_tablenav( $which ): void {
        if ( 'top' !== $which ) {
            return;
        }

        $current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
        ?>
        <div class="alignleft actions">
            <select name="status">
                <option value=""><?php esc_html_e( 'Tous les statuts', 'mrz-gift-lists-exp' ); ?></option>
                <option value="active" <?php selected( $current_status, 'active' ); ?>><?php esc_html_e( 'Active', 'mrz-gift-lists-exp' ); ?></option>
                <option value="archived" <?php selected( $current_status, 'archived' ); ?>><?php esc_html_e( 'Archivée', 'mrz-gift-lists-exp' ); ?></option>
                <option value="completed" <?php selected( $current_status, 'completed' ); ?>><?php esc_html_e( 'Terminée', 'mrz-gift-lists-exp' ); ?></option>
            </select>
            <?php submit_button( __( 'Filtrer', 'mrz-gift-lists-exp' ), '', 'filter_action', false ); ?>
        </div>
        <?php
    }
}
