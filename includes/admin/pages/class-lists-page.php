<?php
/**
 * Page d'administration des listes
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Admin\Pages;

use MrzGiftsListsExp\Repositories\ListRepository;
use MrzGiftsListsExp\Repositories\ItemRepository;
use MrzGiftsListsExp\Admin\ListTable;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ListsPage
 * Gère l'affichage et les actions de la page des listes
 */
class ListsPage {

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

        $this->handle_actions();
    }

    /**
     * Gère les actions (suppression, etc.)
     */
    private function handle_actions(): void {
        if ( ! isset( $_GET['page'] ) || 'mrz-gle-lists' !== $_GET['page'] ) {
            return;
        }

        $action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : '';
        $list_id = isset( $_GET['list_id'] ) ? absint( $_GET['list_id'] ) : 0;

        if ( ! $action || ! $list_id ) {
            return;
        }

        // Vérifie le nonce
        $nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'mrz_gle_list_action' ) ) {
            wp_die( esc_html__( 'Action non autorisée.', 'mrz-gift-lists-exp' ) );
        }

        // Défense en profondeur : handle_actions() s'exécute sur toute requête
        // admin (via le constructeur), avant le contrôle de capacité du menu.
        if ( ! current_user_can( 'mrz_gle_manage_lists' ) ) {
            wp_die( esc_html__( 'Action non autorisée.', 'mrz-gift-lists-exp' ) );
        }

        switch ( $action ) {
            case 'delete':
                $this->delete_list( $list_id );
                break;

            case 'archive':
                $this->archive_list( $list_id );
                break;

            case 'activate':
                $this->activate_list( $list_id );
                break;
        }
    }

    /**
     * Supprime une liste
     *
     * @param int $list_id ID de la liste
     */
    private function delete_list( int $list_id ): void {
        // Supprime d'abord les items
        $this->item_repo->delete_by_list( $list_id );

        // Puis la liste
        if ( $this->list_repo->delete( $list_id ) ) {
            wp_safe_redirect( add_query_arg( [
                'page'    => 'mrz-gle-lists',
                'message' => 'deleted',
            ], admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    /**
     * Archive une liste
     *
     * @param int $list_id ID de la liste
     */
    private function archive_list( int $list_id ): void {
        if ( $this->list_repo->update_status( $list_id, 'archived' ) ) {
            wp_safe_redirect( add_query_arg( [
                'page'    => 'mrz-gle-lists',
                'message' => 'archived',
            ], admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    /**
     * Active une liste
     *
     * @param int $list_id ID de la liste
     */
    private function activate_list( int $list_id ): void {
        if ( $this->list_repo->update_status( $list_id, 'active' ) ) {
            wp_safe_redirect( add_query_arg( [
                'page'    => 'mrz-gle-lists',
                'message' => 'activated',
            ], admin_url( 'admin.php' ) ) );
            exit;
        }
    }

    /**
     * Affiche la page
     */
    public function render(): void {
        // Vérifie si on affiche une liste spécifique
        $list_id = isset( $_GET['list_id'] ) ? absint( $_GET['list_id'] ) : 0;
        $view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : '';

        if ( $list_id && 'details' === $view ) {
            $this->render_list_details( $list_id );
            return;
        }

        $this->render_list_table();
    }

    /**
     * Affiche le tableau des listes
     */
    private function render_list_table(): void {
        $table = new ListTable();
        $table->prepare_items();

        // Messages de notification
        $this->display_messages();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Listes de cadeaux', 'mrz-gift-lists-exp' ); ?></h1>

            <hr class="wp-header-end">

            <form method="get">
                <input type="hidden" name="page" value="mrz-gle-lists">
                <?php
                $table->search_box( __( 'Rechercher', 'mrz-gift-lists-exp' ), 'mrz-gle-search' );
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Affiche les détails d'une liste
     *
     * @param int $list_id ID de la liste
     */
    private function render_list_details( int $list_id ): void {
        $list = $this->list_repo->find( $list_id );

        if ( ! $list ) {
            wp_die( esc_html__( 'Liste non trouvée.', 'mrz-gift-lists-exp' ) );
        }

        $items = $this->item_repo->find_by_list( $list_id );
        $stats = $this->item_repo->get_list_stats( $list_id );
        $user = get_user_by( 'id', $list->user_id );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=mrz-gle-lists' ) ); ?>" class="page-title-action" style="margin-right: 10px;">
                    ← <?php esc_html_e( 'Retour', 'mrz-gift-lists-exp' ); ?>
                </a>
                <?php echo esc_html( $list->title ); ?>
            </h1>

            <hr class="wp-header-end">

            <div class="mrz-gle-list-details" style="display: grid; grid-template-columns: 1fr 300px; gap: 20px; margin-top: 20px;">
                <!-- Colonne principale -->
                <div>
                    <!-- Informations -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e( 'Informations', 'mrz-gift-lists-exp' ); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><?php esc_html_e( 'Créateur', 'mrz-gift-lists-exp' ); ?></th>
                                    <td>
                                        <?php if ( $user ) : ?>
                                            <a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>">
                                                <?php echo esc_html( $user->display_name ); ?>
                                            </a>
                                            (<?php echo esc_html( $user->user_email ); ?>)
                                        <?php else : ?>
                                            <?php esc_html_e( 'Utilisateur supprimé', 'mrz-gift-lists-exp' ); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Date de création', 'mrz-gift-lists-exp' ); ?></th>
                                    <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $list->created_at ) ) ); ?></td>
                                </tr>
                                <?php $event_ts = ( $list->event_date && '0000-00-00' !== $list->event_date ) ? strtotime( $list->event_date ) : false; ?>
                                <?php if ( $event_ts ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Date de l\'événement', 'mrz-gift-lists-exp' ); ?></th>
                                    <td><?php echo esc_html( wp_date( get_option( 'date_format' ), $event_ts ) ); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <th><?php esc_html_e( 'Statut', 'mrz-gift-lists-exp' ); ?></th>
                                    <td>
                                        <?php
                                        $statuses = [
                                            'active'   => '<span style="color: #00a32a;">● ' . __( 'Active', 'mrz-gift-lists-exp' ) . '</span>',
                                            'archived' => '<span style="color: #dba617;">● ' . __( 'Archivée', 'mrz-gift-lists-exp' ) . '</span>',
                                            'completed' => '<span style="color: #2271b1;">● ' . __( 'Terminée', 'mrz-gift-lists-exp' ) . '</span>',
                                        ];
                                        echo wp_kses_post( $statuses[ $list->status ] ?? $list->status );
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Clé d\'accès', 'mrz-gift-lists-exp' ); ?></th>
                                    <td>
                                        <code><?php echo esc_html( $list->access_key ); ?></code>
                                        <a href="<?php echo esc_url( \MrzGiftsListsExp\Plugin::instance()->get_list_url( $list->access_key ) ); ?>" target="_blank" class="button button-small">
                                            <?php esc_html_e( 'Voir', 'mrz-gift-lists-exp' ); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php if ( $list->description ) : ?>
                                <tr>
                                    <th><?php esc_html_e( 'Description', 'mrz-gift-lists-exp' ); ?></th>
                                    <td><?php echo wp_kses_post( nl2br( $list->description ) ); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Produits -->
                    <div class="postbox">
                        <h2 class="hndle">
                            <?php esc_html_e( 'Produits', 'mrz-gift-lists-exp' ); ?>
                            <span class="count">(<?php echo esc_html( count( $items ) ); ?>)</span>
                        </h2>
                        <div class="inside">
                            <?php if ( empty( $items ) ) : ?>
                                <p><?php esc_html_e( 'Aucun produit dans cette liste.', 'mrz-gift-lists-exp' ); ?></p>
                            <?php else : ?>
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;"></th>
                                            <th><?php esc_html_e( 'Produit', 'mrz-gift-lists-exp' ); ?></th>
                                            <th style="width: 100px;"><?php esc_html_e( 'Prix', 'mrz-gift-lists-exp' ); ?></th>
                                            <th style="width: 100px;"><?php esc_html_e( 'Statut', 'mrz-gift-lists-exp' ); ?></th>
                                            <th><?php esc_html_e( 'Acheté par', 'mrz-gift-lists-exp' ); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ( $items as $item ) : ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo esc_url( $item->product_image ); ?>" alt="" style="width: 40px; height: 40px; object-fit: cover;">
                                                </td>
                                                <td>
                                                    <?php if ( $item->priority ) : ?>
                                                        <span title="<?php esc_attr_e( 'Favori', 'mrz-gift-lists-exp' ); ?>">⭐</span>
                                                    <?php endif; ?>
                                                    <?php if ( $item->product_exists ) : ?>
                                                        <a href="<?php echo esc_url( get_edit_post_link( $item->product_id ) ); ?>">
                                                            <?php echo esc_html( $item->product_name ); ?>
                                                        </a>
                                                    <?php else : ?>
                                                        <span style="color: #d63638;"><?php echo esc_html( $item->product_name ); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo wp_kses_post( $item->product_price_html ); ?></td>
                                                <td>
                                                    <?php
                                                    $item_statuses = [
                                                        'available' => '<span style="color: #00a32a;">' . __( 'Disponible', 'mrz-gift-lists-exp' ) . '</span>',
                                                        'reserved'  => '<span style="color: #dba617;">' . __( 'Réservé', 'mrz-gift-lists-exp' ) . '</span>',
                                                        'purchased' => '<span style="color: #2271b1;">' . __( 'Acheté', 'mrz-gift-lists-exp' ) . '</span>',
                                                    ];
                                                    echo wp_kses_post( $item_statuses[ $item->status ] ?? $item->status );
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if ( $item->purchased_by ) {
                                                        echo esc_html( $item->purchased_by );
                                                        if ( $item->order_id ) {
                                                            echo ' (<a href="' . esc_url( get_edit_post_link( $item->order_id ) ) . '">#' . esc_html( $item->order_id ) . '</a>)';
                                                        }
                                                    } else {
                                                        echo '—';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Barre latérale -->
                <div>
                    <!-- Statistiques -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e( 'Statistiques', 'mrz-gift-lists-exp' ); ?></h2>
                        <div class="inside">
                            <ul style="margin: 0;">
                                <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                    <strong><?php echo esc_html( $stats->total_items ); ?></strong>
                                    <?php esc_html_e( 'produits au total', 'mrz-gift-lists-exp' ); ?>
                                </li>
                                <li style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                    <strong style="color: #00a32a;"><?php echo esc_html( $stats->available_items ); ?></strong>
                                    <?php esc_html_e( 'disponibles', 'mrz-gift-lists-exp' ); ?>
                                </li>
                                <li style="padding: 8px 0;">
                                    <strong style="color: #2271b1;"><?php echo esc_html( $stats->purchased_items ); ?></strong>
                                    <?php esc_html_e( 'achetés', 'mrz-gift-lists-exp' ); ?>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e( 'Actions', 'mrz-gift-lists-exp' ); ?></h2>
                        <div class="inside">
                            <p>
                                <a href="<?php echo esc_url( \MrzGiftsListsExp\Plugin::instance()->get_list_url( $list->access_key ) ); ?>" class="button button-primary" target="_blank" style="width: 100%; text-align: center;">
                                    <?php esc_html_e( 'Voir la liste', 'mrz-gift-lists-exp' ); ?>
                                </a>
                            </p>

                            <?php if ( 'active' === $list->status ) : ?>
                                <p>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mrz-gle-lists&action=archive&list_id=' . $list->id ), 'mrz_gle_list_action' ) ); ?>" class="button" style="width: 100%; text-align: center;">
                                        <?php esc_html_e( 'Archiver', 'mrz-gift-lists-exp' ); ?>
                                    </a>
                                </p>
                            <?php else : ?>
                                <p>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mrz-gle-lists&action=activate&list_id=' . $list->id ), 'mrz_gle_list_action' ) ); ?>" class="button" style="width: 100%; text-align: center;">
                                        <?php esc_html_e( 'Réactiver', 'mrz-gift-lists-exp' ); ?>
                                    </a>
                                </p>
                            <?php endif; ?>

                            <p>
                                <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=mrz-gle-lists&action=delete&list_id=' . $list->id ), 'mrz_gle_list_action' ) ); ?>" class="button" style="width: 100%; text-align: center; color: #d63638;" onclick="return confirm('<?php esc_attr_e( 'Êtes-vous sûr de vouloir supprimer cette liste ?', 'mrz-gift-lists-exp' ); ?>');">
                                    <?php esc_html_e( 'Supprimer', 'mrz-gift-lists-exp' ); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche les messages de notification
     */
    private function display_messages(): void {
        if ( ! isset( $_GET['message'] ) ) {
            return;
        }

        $messages = [
            'deleted'   => __( 'Liste supprimée avec succès.', 'mrz-gift-lists-exp' ),
            'archived'  => __( 'Liste archivée avec succès.', 'mrz-gift-lists-exp' ),
            'activated' => __( 'Liste réactivée avec succès.', 'mrz-gift-lists-exp' ),
        ];

        $message_key = sanitize_key( $_GET['message'] );

        if ( isset( $messages[ $message_key ] ) ) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html( $messages[ $message_key ] )
            );
        }
    }
}
