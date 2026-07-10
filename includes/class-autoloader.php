<?php
/**
 * Autoloader pour le plugin MRZ Gift Lists
 *
 * @package MrzGiftsListsExp
 */

namespace MrzGiftsListsExp;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Autoloader
 * Gère le chargement automatique des classes du plugin
 */
class Autoloader {

    /**
     * Namespace de base du plugin
     *
     * @var string
     */
    private const NAMESPACE_PREFIX = 'MrzGiftsListsExp\\';

    /**
     * Enregistre l'autoloader
     */
    public static function register(): void {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    /**
     * Charge automatiquement une classe
     *
     * @param string $class_name Nom complet de la classe
     */
    public static function autoload( string $class_name ): void {
        // Vérifie si la classe appartient à notre namespace
        if ( strpos( $class_name, self::NAMESPACE_PREFIX ) !== 0 ) {
            return;
        }

        // Retire le namespace de base
        $relative_class = substr( $class_name, strlen( self::NAMESPACE_PREFIX ) );

        // Convertit en chemin de fichier
        $file_path = self::get_file_path( $relative_class );

        // Charge le fichier s'il existe
        if ( $file_path && file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }

    /**
     * Détermine le chemin du fichier selon le type de classe
     *
     * @param string $relative_class Classe relative (ex: Admin\Admin, Integrations\WooCommerce)
     * @return string|null
     */
    private static function get_file_path( string $relative_class ): ?string {
        $base_dir = MRZ_GIFTS_LISTS_EXP_PLUGIN_DIR . 'includes/';

        // Sépare le namespace et le nom de classe
        $parts = explode( '\\', $relative_class );
        $class_name = array_pop( $parts );

        // Construit le sous-dossier depuis le namespace
        $sub_dir = '';
        if ( ! empty( $parts ) ) {
            $sub_dir = strtolower( implode( '/', $parts ) ) . '/';
        }

        // Convertit le nom de classe en nom de fichier WordPress
        // Ex: WooCommerce -> woo-commerce -> class-woo-commerce.php
        // Ex: Admin -> admin -> class-admin.php
        // Ex: ListTable -> list-table -> class-list-table.php
        $file_name = self::class_to_filename( $class_name );

        // Chemin complet
        $full_path = $base_dir . $sub_dir . $file_name;

        if ( file_exists( $full_path ) ) {
            return $full_path;
        }

        return null;
    }

    /**
     * Convertit un nom de classe en nom de fichier WordPress
     *
     * @param string $class_name Nom de la classe
     * @return string
     */
    private static function class_to_filename( string $class_name ): string {
        // Insère un tiret avant chaque majuscule (sauf la première)
        $name = preg_replace( '/([a-z])([A-Z])/', '$1-$2', $class_name );
        // Convertit en minuscules
        $name = strtolower( $name );
        // Remplace les underscores par des tirets
        $name = str_replace( '_', '-', $name );

        return 'class-' . $name . '.php';
    }
}
