<?php
/**
 * Shortcode principal [mrzgle_lists]
 *
 * @package Mrzgle
 */

namespace Mrzgle\Frontend\Shortcodes;

use Mrzgle\Repositories\ListRepository;
use Mrzgle\Repositories\ItemRepository;
use Mrzgle\Frontend\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Classe GiftListsShortcode
 * Gère le shortcode principal d'affichage des listes
 */
class GiftListsShortcode {

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

        add_shortcode( 'mrzgle_lists', [ $this, 'render' ] );
    }

    /**
     * Rendu du shortcode
     *
     * @param array $atts Attributs du shortcode
     * @return string
     */
    public function render( $atts = [] ): string {
        $atts = shortcode_atts( [
            'view' => '',
        ], $atts, 'mrzgle_lists' );

        // Garantit le chargement des assets quand le shortcode est inséré là où
        // has_shortcode() ne le voit pas (bloc Raw HTML WPBakery, section globale...).
        wp_enqueue_style( 'mrz-gle-frontend' );
        wp_enqueue_script( 'mrz-gle-frontend' );

        // Détermine la vue à afficher
        $view = $this->get_current_view( $atts );

        ob_start();

        echo '<div class="mrz-gle-container">';

        switch ( $view ) {
            case 'create':
                $this->render_create_form();
                break;

            case 'edit':
                $this->render_edit_form();
                break;

            case 'list':
                $this->render_single_list();
                break;

            case 'my-lists':
                $this->render_my_lists();
                break;

            default:
                $this->render_home();
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Détermine la vue actuelle
     *
     * @param array $atts Attributs
     * @return string
     */
    private function get_current_view( array $atts ): string {
        // Priorité aux paramètres GET
        if ( isset( $_GET['mrzgle_list_key'] ) ) {
            return 'list';
        }

        if ( isset( $_GET['action'] ) ) {
            return sanitize_key( $_GET['action'] );
        }

        // Puis aux attributs
        if ( ! empty( $atts['view'] ) ) {
            return sanitize_key( $atts['view'] );
        }

        return 'home';
    }

    /**
     * Affiche la page d'accueil
     */
    private function render_home(): void {
        ?>
        <div class="mrz-gle-home">
            <div class="mrz-gle-home-header">
                <p><?php esc_html_e( 'Créez votre liste de cadeaux et partagez-la avec vos proches.', 'mrz-gift-lists-exp' ); ?></p>
            </div>

            <div class="mrz-gle-home-actions">
                <?php if ( is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'action', 'my-lists' ) ); ?>" class="mrz-gle-btn mrz-gle-btn-primary">
                        <?php esc_html_e( 'Mes listes', 'mrz-gift-lists-exp' ); ?>
                    </a>
                <?php endif; ?>

                <?php if ( Frontend::can_create_list() ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'action', 'create' ) ); ?>" class="mrz-gle-btn mrz-gle-btn-secondary">
                        <?php esc_html_e( 'Créer une liste', 'mrz-gift-lists-exp' ); ?>
                    </a>
                <?php elseif ( ! is_user_logged_in() ) : ?>
                    <a href="<?php echo esc_url( Frontend::get_account_url() ); ?>" class="mrz-gle-btn mrz-gle-btn-secondary">
                        <?php esc_html_e( 'Se connecter pour créer une liste', 'mrz-gift-lists-exp' ); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche une carte de liste
     *
     * @param object $list Liste
     */
    private function render_list_row( object $list ): void {
        $stats     = $this->item_repo->get_list_stats( $list->id );
        $list_url  = \Mrzgle\Plugin::instance()->get_list_url( $list->access_key );
        $is_active = ( 'active' === $list->status );
        ?>
        <li class="mrz-gle-list-row">
            <a class="mrz-gle-row-thumb" href="<?php echo esc_url( $list_url ); ?>">
                <?php if ( $list->image_id ) : ?>
                    <?php echo wp_get_attachment_image( $list->image_id, 'thumbnail' ); ?>
                <?php endif; ?>
            </a>

            <div class="mrz-gle-row-main">
                <a class="mrz-gle-row-title" href="<?php echo esc_url( $list_url ); ?>"><?php echo esc_html( $list->title ); ?></a>
                <div class="mrz-gle-row-stats">
                    <span><?php echo esc_html( $stats->total_items ); ?> <?php esc_html_e( 'produits', 'mrz-gift-lists-exp' ); ?></span>
                    <?php if ( $stats->purchased_items > 0 ) : ?>
                        <span><?php echo esc_html( $stats->purchased_items ); ?> <?php esc_html_e( 'achetés', 'mrz-gift-lists-exp' ); ?></span>
                    <?php endif; ?>
                    <?php if ( ! $is_active ) : ?>
                        <span class="mrz-gle-row-inactive"><?php esc_html_e( 'désactivée', 'mrz-gift-lists-exp' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mrz-gle-row-actions">
                <a class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon" href="<?php echo esc_url( $list_url ); ?>" title="<?php esc_attr_e( 'Voir', 'mrz-gift-lists-exp' ); ?>"><?php echo $this->icon( 'eye', __( 'Voir', 'mrz-gift-lists-exp' ) ); ?></a>
                <a class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon" href="<?php echo esc_url( add_query_arg( [ 'action' => 'edit', 'list_id' => $list->id ], \Mrzgle\Plugin::instance()->get_lists_page_url() ) ); ?>" title="<?php esc_attr_e( 'Modifier', 'mrz-gift-lists-exp' ); ?>"><?php echo $this->icon( 'edit', __( 'Modifier', 'mrz-gift-lists-exp' ) ); ?></a>
                <form method="post" class="mrz-gle-list-manage">
                    <?php wp_nonce_field( 'mrzgle_owner_list_action_' . $list->id, 'mrzgle_list_nonce' ); ?>
                    <input type="hidden" name="mrzgle_list_id" value="<?php echo esc_attr( $list->id ); ?>">
                    <input type="hidden" name="mrzgle_return" value="my-lists">
                    <?php if ( $is_active ) : ?>
                        <button type="submit" name="mrzgle_list_action" value="deactivate" class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon" title="<?php esc_attr_e( 'Désactiver', 'mrz-gift-lists-exp' ); ?>"><?php echo $this->icon( 'pause-circle', __( 'Désactiver', 'mrz-gift-lists-exp' ) ); ?></button>
                    <?php else : ?>
                        <button type="submit" name="mrzgle_list_action" value="activate" class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon" title="<?php esc_attr_e( 'Activer', 'mrz-gift-lists-exp' ); ?>"><?php echo $this->icon( 'play-circle', __( 'Activer', 'mrz-gift-lists-exp' ) ); ?></button>
                    <?php endif; ?>
                    <button type="submit" name="mrzgle_list_action" value="delete" class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon mrz-gle-btn-danger" title="<?php esc_attr_e( 'Supprimer', 'mrz-gift-lists-exp' ); ?>"
                            onclick="return confirm('<?php echo esc_js( __( 'Supprimer définitivement cette liste ?', 'mrz-gift-lists-exp' ) ); ?>');"><?php echo $this->icon( 'trash', __( 'Supprimer', 'mrz-gift-lists-exp' ) ); ?></button>
                </form>
            </div>
        </li>
        <?php
    }

    /**
     * Affiche mes listes
     */
    private function render_my_lists(): void {
        if ( ! is_user_logged_in() ) {
            echo '<p>' . wp_kses_post( sprintf(
                __( 'Vous devez <a href="%s">vous connecter</a> pour voir vos listes.', 'mrz-gift-lists-exp' ),
                esc_url( Frontend::get_account_url() )
            ) ) . '</p>';
            return;
        }

        $lists = $this->list_repo->find_by_user( get_current_user_id() );
        ?>
        <div class="mrz-gle-my-lists">
            <div class="mrz-gle-header-with-action">
                <h2><?php esc_html_e( 'Mes listes', 'mrz-gift-lists-exp' ); ?></h2>

                <?php if ( Frontend::can_create_list() ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'action', 'create' ) ); ?>" class="mrz-gle-btn mrz-gle-btn-primary">
                        <?php echo $this->icon( 'add', '' ); ?> <?php esc_html_e( 'Nouvelle liste', 'mrz-gift-lists-exp' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if ( empty( $lists ) ) : ?>
                <div class="mrz-gle-empty-state">
                    <p><?php esc_html_e( 'Vous n\'avez pas encore de liste.', 'mrz-gift-lists-exp' ); ?></p>
                    <?php if ( Frontend::can_create_list() ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'action', 'create' ) ); ?>" class="mrz-gle-btn mrz-gle-btn-primary">
                            <?php esc_html_e( 'Créer ma première liste', 'mrz-gift-lists-exp' ); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <ul class="mrz-gle-list-rows">
                    <?php foreach ( $lists as $list ) : ?>
                        <?php $this->render_list_row( $list ); ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Affiche le formulaire de création
     */
    private function render_create_form(): void {
        if ( ! Frontend::can_create_list() ) {
            echo '<div class="mrz-gle-notice mrz-gle-notice-error">';
            echo wp_kses_post( Frontend::get_cannot_create_message() );
            echo '</div>';
            return;
        }

        // Traitement du formulaire
        $errors = [];
        $success = false;

        if ( isset( $_POST['mrzgle_create_list'] ) && isset( $_POST['mrzgle_create_nonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mrzgle_create_nonce'] ) ), 'mrzgle_create_list' ) ) {
                $result = $this->process_create_form();
                if ( is_wp_error( $result ) ) {
                    $errors = $result->get_error_messages();
                } else {
                    // Redirige vers la liste créée
                    wp_safe_redirect( \Mrzgle\Plugin::instance()->get_list_url( $result ) );
                    exit;
                }
            } else {
                $errors[] = __( 'Erreur de sécurité. Veuillez réessayer.', 'mrz-gift-lists-exp' );
            }
        }

        ?>
        <div class="mrz-gle-create-form">
            <h2><?php esc_html_e( 'Créer une liste', 'mrz-gift-lists-exp' ); ?></h2>

            <?php if ( ! empty( $errors ) ) : ?>
                <div class="mrz-gle-notice mrz-gle-notice-error">
                    <?php foreach ( $errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'mrzgle_create_list', 'mrzgle_create_nonce' ); ?>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_title"><?php esc_html_e( 'Titre de la liste', 'mrz-gift-lists-exp' ); ?> *</label>
                    <input type="text" name="mrzgle_title" id="mrzgle_title" required
                           value="<?php echo esc_attr( wp_unslash( $_POST['mrzgle_title'] ?? '' ) ); ?>"
                           placeholder="<?php esc_attr_e( 'Ex: Liste de mariage de Marie et Jean', 'mrz-gift-lists-exp' ); ?>">
                </div>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_event_date"><?php esc_html_e( 'Date de fin de l\'événement', 'mrz-gift-lists-exp' ); ?> *</label>
                    <input type="date" name="mrzgle_event_date" id="mrzgle_event_date" required
                           value="<?php echo esc_attr( wp_unslash( $_POST['mrzgle_event_date'] ?? '' ) ); ?>">
                    <p class="mrz-gle-form-help"><?php esc_html_e( 'Date limite de publication de votre liste.', 'mrz-gift-lists-exp' ); ?></p>
                </div>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_description"><?php esc_html_e( 'Description', 'mrz-gift-lists-exp' ); ?></label>
                    <textarea name="mrzgle_description" id="mrzgle_description" rows="4"
                              placeholder="<?php esc_attr_e( 'Décrivez votre événement...', 'mrz-gift-lists-exp' ); ?>"><?php echo esc_textarea( wp_unslash( $_POST['mrzgle_description'] ?? '' ) ); ?></textarea>
                </div>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_image"><?php esc_html_e( 'Image', 'mrz-gift-lists-exp' ); ?> *</label>
                    <input type="file" name="mrzgle_image" id="mrzgle_image" accept="image/*" required>
                    <p class="mrz-gle-form-help"><?php esc_html_e( 'Image d\'illustration pour votre liste (JPG, PNG, max 2 Mo)', 'mrz-gift-lists-exp' ); ?></p>
                </div>

                <div class="mrz-gle-form-actions">
                    <button type="submit" name="mrzgle_create_list" class="mrz-gle-btn mrz-gle-btn-primary">
                        <?php esc_html_e( 'Créer la liste', 'mrz-gift-lists-exp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( remove_query_arg( 'action' ) ); ?>" class="mrz-gle-btn mrz-gle-btn-outline">
                        <?php esc_html_e( 'Annuler', 'mrz-gift-lists-exp' ); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Traite le formulaire de création
     *
     * @return string|\WP_Error Clé d'accès ou erreur
     */
    private function process_create_form() {
        $title = isset( $_POST['mrzgle_title'] ) ? sanitize_text_field( wp_unslash( $_POST['mrzgle_title'] ) ) : '';

        if ( empty( $title ) ) {
            return new \WP_Error( 'empty_title', __( 'Le titre est obligatoire.', 'mrz-gift-lists-exp' ) );
        }

        // Date de fin obligatoire (date limite de publication de la liste)
        $event_date = isset( $_POST['mrzgle_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mrzgle_event_date'] ) ) : '';
        if ( empty( $event_date ) ) {
            return new \WP_Error( 'empty_event_date', __( 'La date de fin de l\'événement est obligatoire.', 'mrz-gift-lists-exp' ) );
        }

        // Image obligatoire
        if ( empty( $_FILES['mrzgle_image']['name'] ) ) {
            return new \WP_Error( 'empty_image', __( 'Une image est obligatoire.', 'mrz-gift-lists-exp' ) );
        }
        $image_id = $this->handle_image_upload();
        if ( is_wp_error( $image_id ) ) {
            return $image_id;
        }

        // Génère la clé d'accès
        $access_key = $this->list_repo->generate_access_key();

        // Données de la liste
        $data = [
            'user_id'     => get_current_user_id(),
            'title'       => $title,
            'description' => isset( $_POST['mrzgle_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mrzgle_description'] ) ) : '',
            'event_date'  => $event_date,
            'image_id'    => $image_id,
            'access_key'  => $access_key,
            'status'      => 'active',
        ];

        $list_id = $this->list_repo->create( $data );

        if ( ! $list_id ) {
            return new \WP_Error( 'create_failed', __( 'Erreur lors de la création de la liste.', 'mrz-gift-lists-exp' ) );
        }

        // Notification admin
        do_action( 'mrzgle_list_created', $list_id );

        return $access_key;
    }

    /**
     * Gère l'upload d'image
     *
     * @return int|\WP_Error ID de l'attachment ou erreur
     */
    private function handle_image_upload() {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Vérifie le type de fichier
        $allowed_types = [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ];
        if ( ! in_array( $_FILES['mrzgle_image']['type'], $allowed_types, true ) ) {
            return new \WP_Error( 'invalid_type', __( 'Type de fichier non autorisé.', 'mrz-gift-lists-exp' ) );
        }

        // Vérifie la taille (2 Mo max)
        if ( $_FILES['mrzgle_image']['size'] > 2 * 1024 * 1024 ) {
            return new \WP_Error( 'file_too_large', __( 'L\'image est trop volumineuse (max 2 Mo).', 'mrz-gift-lists-exp' ) );
        }

        // Whitelist serveur des types réels (vérifiés via magic bytes par
        // wp_check_filetype_and_ext), indépendante du Content-Type client et de
        // la config du site : bloque SVG, fichiers polyglottes, etc.
        $overrides = [
            'test_form' => false,
            'mimes'     => [
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
                'gif'      => 'image/gif',
                'webp'     => 'image/webp',
            ],
        ];

        $attachment_id = media_handle_upload( 'mrzgle_image', 0, [], $overrides );

        return $attachment_id;
    }

    /**
     * Affiche le formulaire de modification
     */
    private function render_edit_form(): void {
        if ( ! is_user_logged_in() ) {
            echo '<p>' . esc_html__( 'Vous devez être connecté.', 'mrz-gift-lists-exp' ) . '</p>';
            return;
        }

        $list_id = isset( $_GET['list_id'] ) ? absint( $_GET['list_id'] ) : 0;
        $list = $list_id ? $this->list_repo->find( $list_id ) : null;

        if ( ! $list || (int) $list->user_id !== get_current_user_id() ) {
            echo '<p>' . esc_html__( 'Liste non trouvée ou accès non autorisé.', 'mrz-gift-lists-exp' ) . '</p>';
            return;
        }

        // Traitement du formulaire
        $errors = [];

        if ( isset( $_POST['mrzgle_edit_list'] ) && isset( $_POST['mrzgle_edit_nonce'] ) ) {
            if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mrzgle_edit_nonce'] ) ), 'mrzgle_edit_list_' . $list->id ) ) {
                $result = $this->process_edit_form( $list );
                if ( is_wp_error( $result ) ) {
                    $errors = $result->get_error_messages();
                } else {
                    // Redirige vers la liste modifiée
                    wp_safe_redirect( \Mrzgle\Plugin::instance()->get_list_url( $list->access_key ) );
                    exit;
                }
            } else {
                $errors[] = __( 'Erreur de sécurité. Veuillez réessayer.', 'mrz-gift-lists-exp' );
            }
        }

        // Valeurs pré-remplies : POST (repopulation après erreur) sinon la liste existante.
        $val_title = isset( $_POST['mrzgle_title'] ) ? wp_unslash( $_POST['mrzgle_title'] ) : $list->title;
        $val_date  = isset( $_POST['mrzgle_event_date'] )
            ? wp_unslash( $_POST['mrzgle_event_date'] )
            : ( ( $list->event_date && '0000-00-00' !== $list->event_date ) ? $list->event_date : '' );
        $val_desc  = isset( $_POST['mrzgle_description'] ) ? wp_unslash( $_POST['mrzgle_description'] ) : $list->description;
        ?>
        <div class="mrz-gle-edit-form">
            <h2><?php esc_html_e( 'Modifier la liste', 'mrz-gift-lists-exp' ); ?></h2>

            <?php if ( ! empty( $errors ) ) : ?>
                <div class="mrz-gle-notice mrz-gle-notice-error">
                    <?php foreach ( $errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'mrzgle_edit_list_' . $list->id, 'mrzgle_edit_nonce' ); ?>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_title"><?php esc_html_e( 'Titre de la liste', 'mrz-gift-lists-exp' ); ?> *</label>
                    <input type="text" name="mrzgle_title" id="mrzgle_title" required
                           value="<?php echo esc_attr( $val_title ); ?>">
                </div>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_event_date"><?php esc_html_e( 'Date de fin de l\'événement', 'mrz-gift-lists-exp' ); ?> *</label>
                    <input type="date" name="mrzgle_event_date" id="mrzgle_event_date" required
                           value="<?php echo esc_attr( $val_date ); ?>">
                    <p class="mrz-gle-form-help"><?php esc_html_e( 'Date limite de publication de votre liste.', 'mrz-gift-lists-exp' ); ?></p>
                </div>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_description"><?php esc_html_e( 'Description', 'mrz-gift-lists-exp' ); ?></label>
                    <textarea name="mrzgle_description" id="mrzgle_description" rows="4"><?php echo esc_textarea( $val_desc ); ?></textarea>
                </div>

                <div class="mrz-gle-form-row">
                    <label for="mrzgle_image"><?php esc_html_e( 'Image', 'mrz-gift-lists-exp' ); ?></label>
                    <?php if ( $list->image_id ) : ?>
                        <div class="mrz-gle-edit-current-image"><?php echo wp_get_attachment_image( $list->image_id, 'thumbnail' ); ?></div>
                    <?php endif; ?>
                    <input type="file" name="mrzgle_image" id="mrzgle_image" accept="image/*">
                    <p class="mrz-gle-form-help"><?php esc_html_e( 'Laissez vide pour conserver l\'image actuelle (JPG, PNG, max 2 Mo).', 'mrz-gift-lists-exp' ); ?></p>
                </div>

                <div class="mrz-gle-form-actions">
                    <button type="submit" name="mrzgle_edit_list" class="mrz-gle-btn mrz-gle-btn-primary">
                        <?php esc_html_e( 'Enregistrer les modifications', 'mrz-gift-lists-exp' ); ?>
                    </button>
                    <a href="<?php echo esc_url( \Mrzgle\Plugin::instance()->get_list_url( $list->access_key ) ); ?>" class="mrz-gle-btn mrz-gle-btn-outline">
                        <?php esc_html_e( 'Annuler', 'mrz-gift-lists-exp' ); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * Traite le formulaire de modification.
     *
     * @param object $list Liste à modifier (ownership déjà vérifié).
     * @return true|\WP_Error
     */
    private function process_edit_form( object $list ) {
        $title = isset( $_POST['mrzgle_title'] ) ? sanitize_text_field( wp_unslash( $_POST['mrzgle_title'] ) ) : '';

        if ( empty( $title ) ) {
            return new \WP_Error( 'empty_title', __( 'Le titre est obligatoire.', 'mrz-gift-lists-exp' ) );
        }

        // Date de fin obligatoire (date limite de publication de la liste)
        $event_date = isset( $_POST['mrzgle_event_date'] ) ? sanitize_text_field( wp_unslash( $_POST['mrzgle_event_date'] ) ) : '';
        if ( empty( $event_date ) ) {
            return new \WP_Error( 'empty_event_date', __( 'La date de fin de l\'événement est obligatoire.', 'mrz-gift-lists-exp' ) );
        }

        $data = [
            'title'       => $title,
            'event_date'  => $event_date,
            'description' => isset( $_POST['mrzgle_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mrzgle_description'] ) ) : '',
        ];

        // Image facultative en édition : on ne la remplace que si un nouveau fichier est fourni.
        if ( ! empty( $_FILES['mrzgle_image']['name'] ) ) {
            $image_id = $this->handle_image_upload();
            if ( is_wp_error( $image_id ) ) {
                return $image_id;
            }
            $data['image_id'] = $image_id;
        }

        $updated = $this->list_repo->update( $list->id, $data );

        if ( false === $updated ) {
            return new \WP_Error( 'update_failed', __( 'Erreur lors de la mise à jour de la liste.', 'mrz-gift-lists-exp' ) );
        }

        return true;
    }

    /**
     * Affiche une liste unique
     */
    private function render_single_list(): void {
        $list_key = isset( $_GET['mrzgle_list_key'] ) ? sanitize_key( $_GET['mrzgle_list_key'] ) : '';

        if ( empty( $list_key ) ) {
            echo '<p>' . esc_html__( 'Liste non trouvée.', 'mrz-gift-lists-exp' ) . '</p>';
            return;
        }

        $list = $this->list_repo->find_by_access_key( $list_key );

        if ( ! $list ) {
            echo '<p>' . esc_html__( 'Liste non trouvée.', 'mrz-gift-lists-exp' ) . '</p>';
            return;
        }

        $is_owner = is_user_logged_in() && get_current_user_id() === (int) $list->user_id;

        // Liste expirée : la date de fin est dépassée (disponible toute la journée
        // de la date de fin, expirée à partir du lendemain).
        $is_expired = ( $list->event_date && '0000-00-00' !== $list->event_date )
            && $list->event_date < current_time( 'Y-m-d' );

        // Une liste désactivée OU expirée n'est visible que par son propriétaire (ou un admin).
        if ( ( 'active' !== $list->status || $is_expired ) && ! $is_owner && ! current_user_can( 'manage_options' ) ) {
            echo '<p>' . esc_html__( 'Cette liste n\'est plus disponible.', 'mrz-gift-lists-exp' ) . '</p>';
            return;
        }

        $items = $this->item_repo->find_by_list( $list->id );
        $stats = $this->item_repo->get_list_stats( $list->id );
        $user = get_user_by( 'id', $list->user_id );
        ?>
        <div class="mrz-gle-single-list">

            <!-- En-tête : pastille image + titre + auteur (empilés, centrés) -->
            <?php $avatar_url = $list->image_id ? wp_get_attachment_image_url( $list->image_id, 'medium' ) : ''; ?>
            <div class="mrz-gle-single-header">
                <div class="mrz-gle-single-avatar<?php echo $avatar_url ? '' : ' mrz-gle-single-avatar--empty'; ?>">
                    <?php if ( $avatar_url ) : ?>
                        <img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php echo esc_attr( $list->title ); ?>">
                    <?php endif; ?>
                </div>
                <h1 class="mrz-gle-single-title"><?php echo esc_html( $list->title ); ?></h1>
                <?php if ( $user ) : ?>
                    <p class="mrz-gle-single-owner"><?php echo esc_html( sprintf( __( 'Par %s', 'mrz-gift-lists-exp' ), $user->display_name ) ); ?></p>
                <?php endif; ?>
                <?php $event_ts = ( $list->event_date && '0000-00-00' !== $list->event_date ) ? strtotime( $list->event_date ) : false; ?>
                <?php if ( $event_ts ) : ?>
                    <p class="mrz-gle-single-date">📅 <?php echo esc_html( sprintf( __( 'Date de fin : %s', 'mrz-gift-lists-exp' ), wp_date( get_option( 'date_format' ), $event_ts ) ) ); ?></p>
                <?php endif; ?>
            </div>

            <!-- Actions propriétaire -->
            <?php if ( $is_owner ) : ?>
                <?php if ( 'active' !== $list->status ) : ?>
                    <div class="mrz-gle-notice">
                        <p><?php esc_html_e( 'Cette liste est désactivée : elle n\'est pas visible par les personnes qui ont le lien.', 'mrz-gift-lists-exp' ); ?></p>
                    </div>
                <?php elseif ( $is_expired ) : ?>
                    <div class="mrz-gle-notice mrz-gle-notice-warning">
                        <p><?php echo esc_html( sprintf( __( 'Cette liste a expiré le %s et n\'est plus visible par vos invités.', 'mrz-gift-lists-exp' ), wp_date( get_option( 'date_format' ), strtotime( $list->event_date ) ) ) ); ?></p>
                    </div>
                <?php endif; ?>

                <div class="mrz-gle-owner-actions">
                    <a href="<?php echo esc_url( $this->get_shop_url() ); ?>" class="mrz-gle-btn mrz-gle-btn-primary">
                        <?php echo $this->icon( 'add', '' ); ?> <?php esc_html_e( 'Ajouter un produit', 'mrz-gift-lists-exp' ); ?>
                    </a>

                    <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'edit', 'list_id' => $list->id ], \Mrzgle\Plugin::instance()->get_lists_page_url() ) ); ?>" class="mrz-gle-btn">
                        <?php echo $this->icon( 'edit', '' ); ?> <?php esc_html_e( 'Modifier', 'mrz-gift-lists-exp' ); ?>
                    </a>

                    <form method="post" class="mrz-gle-list-manage">
                        <?php wp_nonce_field( 'mrzgle_owner_list_action_' . $list->id, 'mrzgle_list_nonce' ); ?>
                        <input type="hidden" name="mrzgle_list_id" value="<?php echo esc_attr( $list->id ); ?>">
                        <input type="hidden" name="mrzgle_return" value="list">
                        <?php if ( 'active' === $list->status ) : ?>
                            <button type="submit" name="mrzgle_list_action" value="deactivate" class="mrz-gle-btn"><?php echo $this->icon( 'pause-circle', '' ); ?> <?php esc_html_e( 'Désactiver', 'mrz-gift-lists-exp' ); ?></button>
                        <?php else : ?>
                            <button type="submit" name="mrzgle_list_action" value="activate" class="mrz-gle-btn"><?php echo $this->icon( 'play-circle', '' ); ?> <?php esc_html_e( 'Activer', 'mrz-gift-lists-exp' ); ?></button>
                        <?php endif; ?>
                        <button type="submit" name="mrzgle_list_action" value="delete" class="mrz-gle-btn mrz-gle-btn-danger"
                                onclick="return confirm('<?php echo esc_js( __( 'Supprimer définitivement cette liste ?', 'mrz-gift-lists-exp' ) ); ?>');"><?php echo $this->icon( 'trash', '' ); ?> <?php esc_html_e( 'Supprimer', 'mrz-gift-lists-exp' ); ?></button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Partage -->
            <?php $this->render_share_buttons( $list ); ?>

            <!-- Description -->
            <?php if ( $list->description ) : ?>
                <div class="mrz-gle-list-description"><?php echo wp_kses_post( nl2br( $list->description ) ); ?></div>
            <?php endif; ?>

            <!-- Produits -->
            <?php $percent = $stats->total_items > 0 ? round( ( $stats->purchased_items / $stats->total_items ) * 100 ) : 0; ?>
            <div class="mrz-gle-list-products">
                <h2><?php esc_html_e( 'Produits', 'mrz-gift-lists-exp' ); ?> (<?php echo esc_html( count( $items ) ); ?>)</h2>

                <?php if ( ! empty( $items ) ) : ?>
                    <div class="mrz-gle-notice mrz-gle-notice-warning">
                        <p>⚠️ <?php esc_html_e( 'Pour offrir un cadeau, cliquez sur « Offrir » ici même. N\'ajoutez pas le produit au panier depuis sa fiche : l\'achat ne serait alors pas rattaché à cette liste.', 'mrz-gift-lists-exp' ); ?></p>
                    </div>

                    <!-- Progression : nombre de produits déjà offerts -->
                    <div class="mrz-gle-list-progress">
                        <div class="mrz-gle-progress-bar">
                            <div class="mrz-gle-progress-fill" style="width: <?php echo esc_attr( $percent ); ?>%;"></div>
                        </div>
                        <p class="mrz-gle-progress-text"><?php echo esc_html( sprintf( __( '%1$d sur %2$d produits achetés (%3$d%%)', 'mrz-gift-lists-exp' ), $stats->purchased_items, $stats->total_items, $percent ) ); ?></p>
                    </div>

                    <ul class="mrz-gle-products-list">
                        <?php foreach ( $items as $item ) : ?>
                            <?php $this->render_product_item( $item, $list, $is_owner ); ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="mrz-gle-empty-state">
                        <p><?php esc_html_e( 'Aucun produit dans cette liste.', 'mrz-gift-lists-exp' ); ?></p>
                        <?php if ( $is_owner ) : ?>
                            <p><?php esc_html_e( 'Commencez par ajouter des produits depuis votre boutique.', 'mrz-gift-lists-exp' ); ?></p>
                            <p class="mrz-gle-empty-actions">
                                <a href="<?php echo esc_url( $this->get_shop_url() ); ?>" class="mrz-gle-btn mrz-gle-btn-primary">
                                    <?php echo $this->icon( 'add', '' ); ?> <?php esc_html_e( 'Ajouter un produit', 'mrz-gift-lists-exp' ); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche un produit de la liste
     *
     * @param object $item     Item
     * @param object $list     Liste
     * @param bool   $is_owner Est propriétaire
     */
    private function render_product_item( object $item, object $list, bool $is_owner ): void {
        $is_purchased = $item->is_fully_purchased;
        $classes      = 'mrz-gle-product-row';
        $classes     .= $is_purchased ? ' mrz-gle-product-purchased' : '';
        $classes     .= $item->priority ? ' mrz-gle-product-fav' : '';
        ?>
        <li class="<?php echo esc_attr( $classes ); ?>" data-item-id="<?php echo esc_attr( $item->id ); ?>">

            <a class="mrz-gle-product-thumb" href="<?php echo esc_url( $item->product_url ); ?>">
                <img src="<?php echo esc_url( $item->product_image ); ?>" alt="<?php echo esc_attr( $item->product_name ); ?>">
                <?php if ( $item->priority ) : ?>
                    <span class="mrz-gle-product-priority" title="<?php esc_attr_e( 'Favori', 'mrz-gift-lists-exp' ); ?>">⭐</span>
                <?php endif; ?>
            </a>

            <div class="mrz-gle-product-main">
                <span class="mrz-gle-product-name"><?php echo esc_html( $item->product_name ); ?></span>
                <div class="mrz-gle-product-meta">
                    <span class="mrz-gle-product-price"><?php echo wp_kses_post( $item->product_price_html ); ?></span>
                    <?php if ( $is_purchased ) : ?>
                        <span class="mrz-gle-product-badge"><?php esc_html_e( 'Offert', 'mrz-gift-lists-exp' ); ?><?php echo $item->purchased_by ? ' · ' . esc_html( $item->purchased_by ) : ''; ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mrz-gle-product-actions">
                <?php if ( ! $is_purchased && $item->product_exists ) : ?>
                    <button type="button"
                            class="mrz-gle-btn mrz-gle-btn-small mrz-gle-add-to-cart"
                            data-product-id="<?php echo esc_attr( $item->variation_id ?: $item->product_id ); ?>"
                            data-list-id="<?php echo esc_attr( $list->id ); ?>"
                            data-item-id="<?php echo esc_attr( $item->id ); ?>">
                        <?php esc_html_e( 'Offrir', 'mrz-gift-lists-exp' ); ?>
                    </button>
                <?php endif; ?>

                <a class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon" href="<?php echo esc_url( $item->product_url ); ?>" title="<?php esc_attr_e( 'Voir le produit', 'mrz-gift-lists-exp' ); ?>">
                    <?php echo $this->icon( 'eye', __( 'Voir le produit', 'mrz-gift-lists-exp' ) ); ?>
                </a>

                <?php if ( $is_owner ) : ?>
                    <button type="button"
                            class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon mrz-gle-toggle-priority"
                            data-item-id="<?php echo esc_attr( $item->id ); ?>"
                            title="<?php esc_attr_e( 'Favori (afficher en haut)', 'mrz-gift-lists-exp' ); ?>">
                        <?php echo $this->icon( $item->priority ? 'star-active' : 'star', __( 'Favori', 'mrz-gift-lists-exp' ) ); ?>
                    </button>
                    <button type="button"
                            class="mrz-gle-btn mrz-gle-btn-small mrz-gle-btn-icon mrz-gle-btn-danger mrz-gle-remove-item"
                            data-item-id="<?php echo esc_attr( $item->id ); ?>"
                            title="<?php esc_attr_e( 'Retirer de la liste', 'mrz-gift-lists-exp' ); ?>">
                        <?php echo $this->icon( 'trash', __( 'Retirer de la liste', 'mrz-gift-lists-exp' ) ); ?>
                    </button>
                <?php endif; ?>
            </div>
        </li>
        <?php
    }

    /**
     * Retourne l'URL de la page boutique (cible du bouton « Ajouter un produit »).
     *
     * @return string
     */
    private function get_shop_url(): string {
        $page_id = (int) get_option( 'mrzgle_shop_page_id', 0 );

        if ( ! $page_id && function_exists( 'wc_get_page_id' ) ) {
            $page_id = (int) wc_get_page_id( 'shop' );
        }

        $url = $page_id > 0 ? get_permalink( $page_id ) : '';

        return $url ?: home_url( '/' );
    }

    /**
     * Retourne la balise <img> d'une icône du plugin (assets/img/<name>.svg).
     *
     * @param string $name  Nom du fichier, sans extension.
     * @param string $label Texte alternatif (accessibilité).
     * @return string
     */
    private function icon( string $name, string $label ): string {
        return sprintf(
            '<img class="mrz-gle-icon" src="%s" alt="%s">',
            esc_url( MRZGLE_PLUGIN_URL . 'assets/img/' . $name . '.svg' ),
            esc_attr( $label )
        );
    }

    /**
     * Affiche les boutons de partage
     *
     * @param object $list Liste
     */
    private function render_share_buttons( object $list ): void {
        $list_url = \Mrzgle\Plugin::instance()->get_list_url( $list->access_key );
        ?>
        <div class="mrz-gle-share">
            <p class="mrz-gle-share-intro">
                <?php esc_html_e( 'Pour partager votre liste, envoyez ce lien à vos proches par message, e-mail ou sur les réseaux sociaux :', 'mrz-gift-lists-exp' ); ?>
            </p>
            <div class="mrz-gle-share-link">
                <input type="text" class="mrz-gle-share-url" value="<?php echo esc_url( $list_url ); ?>" readonly onclick="this.select();">
                <button type="button" class="mrz-gle-btn mrz-gle-share-copy" data-url="<?php echo esc_attr( $list_url ); ?>">
                    <?php echo $this->icon( 'copy-alt', '' ); ?> <?php esc_html_e( 'Copier le lien', 'mrz-gift-lists-exp' ); ?>
                </button>
            </div>
        </div>
        <?php
    }

}
