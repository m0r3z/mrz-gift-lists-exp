<?php
/**
 * Repository de base
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Classe BaseRepository
 * Méthodes CRUD communes
 */
abstract class BaseRepository {

    /**
     * Nom de la table
     *
     * @var string
     */
    protected string $table;

    /**
     * Clé primaire
     *
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * Instance wpdb
     *
     * @var \wpdb
     */
    protected \wpdb $db;

    /**
     * Constructeur
     */
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * Récupère un enregistrement par ID
     *
     * @param int $id ID de l'enregistrement
     * @return object|null
     */
    public function find( int $id ): ?object {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primary_key} = %d",
            $id
        );

        $result = $this->db->get_row( $sql );

        return $result ?: null;
    }

    /**
     * Récupère tous les enregistrements
     *
     * @param array $args Arguments de requête
     * @return array
     */
    public function all( array $args = [] ): array {
        $defaults = [
            'orderby' => $this->primary_key,
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args( $args, $defaults );

        $sql = "SELECT * FROM {$this->table}";
        $sql .= $this->build_order_clause( $args['orderby'], $args['order'] );
        $sql .= $this->build_limit_clause( $args['limit'], $args['offset'] );

        return $this->db->get_results( $sql );
    }

    /**
     * Récupère des enregistrements par critères
     *
     * @param array $criteria Critères de recherche
     * @param array $args     Arguments additionnels
     * @return array
     */
    public function find_by( array $criteria, array $args = [] ): array {
        $defaults = [
            'orderby' => $this->primary_key,
            'order'   => 'DESC',
            'limit'   => 0,
            'offset'  => 0,
        ];

        $args = wp_parse_args( $args, $defaults );

        $where = $this->build_where_clause( $criteria );

        $sql = "SELECT * FROM {$this->table} WHERE {$where['clause']}";
        $sql .= $this->build_order_clause( $args['orderby'], $args['order'] );
        $sql .= $this->build_limit_clause( $args['limit'], $args['offset'] );

        if ( ! empty( $where['values'] ) ) {
            $sql = $this->db->prepare( $sql, $where['values'] );
        }

        return $this->db->get_results( $sql );
    }

    /**
     * Récupère un seul enregistrement par critères
     *
     * @param array $criteria Critères de recherche
     * @return object|null
     */
    public function find_one_by( array $criteria ): ?object {
        $results = $this->find_by( $criteria, [ 'limit' => 1 ] );
        return ! empty( $results ) ? $results[0] : null;
    }

    /**
     * Crée un enregistrement
     *
     * @param array $data Données à insérer
     * @return int|false ID inséré ou false
     */
    public function create( array $data ) {
        $data = $this->sanitize_data( $data );

        $result = $this->db->insert( $this->table, $data, $this->get_format( $data ) );

        if ( false === $result ) {
            return false;
        }

        return $this->db->insert_id;
    }

    /**
     * Met à jour un enregistrement
     *
     * @param int   $id   ID de l'enregistrement
     * @param array $data Données à mettre à jour
     * @return bool
     */
    public function update( int $id, array $data ): bool {
        $data = $this->sanitize_data( $data );

        $result = $this->db->update(
            $this->table,
            $data,
            [ $this->primary_key => $id ],
            $this->get_format( $data ),
            [ '%d' ]
        );

        return false !== $result;
    }

    /**
     * Supprime un enregistrement
     *
     * @param int $id ID de l'enregistrement
     * @return bool
     */
    public function delete( int $id ): bool {
        $result = $this->db->delete(
            $this->table,
            [ $this->primary_key => $id ],
            [ '%d' ]
        );

        return false !== $result;
    }

    /**
     * Compte les enregistrements
     *
     * @param array $criteria Critères optionnels
     * @return int
     */
    public function count( array $criteria = [] ): int {
        $sql = "SELECT COUNT(*) FROM {$this->table}";

        if ( ! empty( $criteria ) ) {
            $where = $this->build_where_clause( $criteria );
            $sql .= " WHERE {$where['clause']}";

            if ( ! empty( $where['values'] ) ) {
                $sql = $this->db->prepare( $sql, $where['values'] );
            }
        }

        return (int) $this->db->get_var( $sql );
    }

    /**
     * Vérifie si un enregistrement existe
     *
     * @param int $id ID de l'enregistrement
     * @return bool
     */
    public function exists( int $id ): bool {
        $sql = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE {$this->primary_key} = %d LIMIT 1",
            $id
        );

        return null !== $this->db->get_var( $sql );
    }

    /**
     * Construit la clause WHERE
     *
     * @param array $criteria Critères
     * @return array
     */
    protected function build_where_clause( array $criteria ): array {
        $clauses = [];
        $values = [];

        foreach ( $criteria as $column => $value ) {
            if ( is_null( $value ) ) {
                $clauses[] = "`{$column}` IS NULL";
            } elseif ( is_array( $value ) ) {
                $placeholders = implode( ', ', array_fill( 0, count( $value ), '%s' ) );
                $clauses[] = "`{$column}` IN ({$placeholders})";
                $values = array_merge( $values, $value );
            } else {
                $clauses[] = "`{$column}` = %s";
                $values[] = $value;
            }
        }

        return [
            'clause' => implode( ' AND ', $clauses ),
            'values' => $values,
        ];
    }

    /**
     * Construit la clause ORDER BY
     *
     * @param string $orderby Colonne
     * @param string $order   Direction
     * @return string
     */
    protected function build_order_clause( string $orderby, string $order ): string {
        $order = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
        $orderby = sanitize_sql_orderby( "{$orderby} {$order}" );

        return $orderby ? " ORDER BY {$orderby}" : '';
    }

    /**
     * Construit la clause LIMIT
     *
     * @param int $limit  Limite
     * @param int $offset Offset
     * @return string
     */
    protected function build_limit_clause( int $limit, int $offset ): string {
        if ( $limit <= 0 ) {
            return '';
        }

        if ( $offset > 0 ) {
            return $this->db->prepare( ' LIMIT %d, %d', $offset, $limit );
        }

        return $this->db->prepare( ' LIMIT %d', $limit );
    }

    /**
     * Retourne le format des colonnes pour wpdb
     *
     * @param array $data Données
     * @return array
     */
    protected function get_format( array $data ): array {
        $format = [];

        foreach ( $data as $value ) {
            if ( is_int( $value ) ) {
                $format[] = '%d';
            } elseif ( is_float( $value ) ) {
                $format[] = '%f';
            } else {
                $format[] = '%s';
            }
        }

        return $format;
    }

    /**
     * Sanitize les données (à surcharger)
     *
     * @param array $data Données
     * @return array
     */
    protected function sanitize_data( array $data ): array {
        return $data;
    }
}
