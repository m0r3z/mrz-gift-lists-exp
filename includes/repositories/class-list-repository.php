<?php
/**
 * Repository des listes
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Classe ListRepository
 * Gestion des listes de cadeaux
 */
class ListRepository extends BaseRepository {

    /**
     * Constructeur
     */
    public function __construct() {
        parent::__construct();
        $this->table = MRZ_GIFTS_LISTS_EXP_TABLE_LISTS;
    }

    /**
     * Récupère une liste par sa clé d'accès
     *
     * @param string $access_key Clé d'accès
     * @return object|null
     */
    public function find_by_access_key( string $access_key ): ?object {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE access_key = %s",
            $access_key
        );

        $result = $this->db->get_row( $sql );

        return $result ?: null;
    }

    /**
     * Récupère les listes d'un utilisateur
     *
     * @param int   $user_id ID utilisateur
     * @param array $args    Arguments
     * @return array
     */
    public function find_by_user( int $user_id, array $args = [] ): array {
        $defaults = [
            'status'  => null,
            'orderby' => 'created_at',
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args( $args, $defaults );

        $sql = $this->db->prepare(
            "SELECT l.* FROM {$this->table} l WHERE l.user_id = %d",
            $user_id
        );

        if ( $args['status'] ) {
            $sql .= $this->db->prepare( ' AND l.status = %s', $args['status'] );
        }

        $sql .= $this->build_order_clause( 'l.' . $args['orderby'], $args['order'] );
        $sql .= $this->build_limit_clause( $args['limit'], $args['offset'] );

        return $this->db->get_results( $sql );
    }

    /**
     * Recherche des listes
     *
     * @param string $search Terme de recherche
     * @param array  $args   Arguments
     * @return array
     */
    public function search( string $search, array $args = [] ): array {
        $defaults = [
            'search_by' => 'both', // 'email', 'name', 'both'
            'status'    => 'active',
            'limit'     => 20,
            'offset'    => 0,
        ];

        $args = wp_parse_args( $args, $defaults );

        $search = '%' . $this->db->esc_like( $search ) . '%';

        $sql = "SELECT l.*, u.display_name as owner_name
                FROM {$this->table} l
                LEFT JOIN {$this->db->users} u ON l.user_id = u.ID
                WHERE l.status = %s";

        $params = [ $args['status'] ];

        switch ( $args['search_by'] ) {
            case 'email':
                $sql .= ' AND u.user_email = %s';
                $params[] = str_replace( '%', '', $search ); // Email exact
                break;

            case 'name':
                $sql .= ' AND (l.title LIKE %s OR u.display_name LIKE %s)';
                $params[] = $search;
                $params[] = $search;
                break;

            default: // both
                $sql .= ' AND (l.title LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
                $params[] = $search;
                $params[] = $search;
                $params[] = $search;
                break;
        }

        $sql .= ' ORDER BY l.created_at DESC';
        $sql .= $this->build_limit_clause( $args['limit'], $args['offset'] );

        return $this->db->get_results( $this->db->prepare( $sql, $params ) );
    }

    /**
     * Génère une clé d'accès unique
     *
     * @return string
     */
    public function generate_access_key(): string {
        do {
            $key = wp_generate_password( 12, false );
        } while ( $this->find_by_access_key( $key ) );

        return $key;
    }

    /**
     * Vérifie si un utilisateur possède une liste
     *
     * @param int $list_id ID de la liste
     * @param int $user_id ID de l'utilisateur
     * @return bool
     */
    public function user_owns_list( int $list_id, int $user_id ): bool {
        $sql = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE id = %d AND user_id = %d LIMIT 1",
            $list_id,
            $user_id
        );

        return null !== $this->db->get_var( $sql );
    }

    /**
     * Compte les listes d'un utilisateur
     *
     * @param int    $user_id ID utilisateur
     * @param string $status  Statut optionnel
     * @return int
     */
    public function count_by_user( int $user_id, string $status = '' ): int {
        $sql = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d",
            $user_id
        );

        if ( $status ) {
            $sql .= $this->db->prepare( ' AND status = %s', $status );
        }

        return (int) $this->db->get_var( $sql );
    }

    /**
     * Met à jour le statut d'une liste
     *
     * @param int    $id     ID de la liste
     * @param string $status Nouveau statut
     * @return bool
     */
    public function update_status( int $id, string $status ): bool {
        $valid_statuses = [ 'active', 'archived', 'completed' ];

        if ( ! in_array( $status, $valid_statuses, true ) ) {
            return false;
        }

        return $this->update( $id, [ 'status' => $status ] );
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
                case 'user_id':
                case 'image_id':
                    $sanitized[ $key ] = absint( $value );
                    break;

                case 'title':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;

                case 'description':
                    $sanitized[ $key ] = sanitize_textarea_field( $value );
                    break;

                case 'access_key':
                    $sanitized[ $key ] = sanitize_key( $value );
                    break;

                case 'status':
                    $sanitized[ $key ] = sanitize_key( $value );
                    break;

                case 'event_date':
                    $value = sanitize_text_field( $value );
                    // Vide → NULL, pour éviter une date zéro (0000-00-00) en base.
                    $sanitized[ $key ] = ( '' === $value ) ? null : $value;
                    break;

                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }

        return $sanitized;
    }
}
