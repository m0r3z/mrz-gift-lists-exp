<?php
/**
 * Gestionnaire d'emails
 *
 * @package Mrzgle
 */

namespace Mrzgle\Emails;

use Mrzgle\Repositories\ListRepository;
use Mrzgle\Repositories\ItemRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Classe Manager
 * Gère l'envoi des emails automatiques
 */
class Manager {

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
     * Initialise les hooks
     */
    private function init_hooks(): void {
        // Notification quand une liste est créée
        add_action( 'mrzgle_list_created', [ $this, 'send_list_created_notification' ] );

        // Notification quand un produit est acheté
        add_action( 'mrzgle_item_purchased', [ $this, 'send_item_purchased_notification' ], 10, 4 );
    }

    /**
     * Envoie une notification de création de liste
     *
     * @param int $list_id ID de la liste
     */
    public function send_list_created_notification( int $list_id ): void {
        $admin_email = get_option( 'mrzgle_admin_email', '' );

        if ( empty( $admin_email ) ) {
            return;
        }

        $list = $this->list_repo->find( $list_id );

        if ( ! $list ) {
            return;
        }

        $user = get_user_by( 'id', $list->user_id );
        $list_url = \Mrzgle\Plugin::instance()->get_list_url( $list->access_key );

        $subject = sprintf(
            __( '[%s] Nouvelle liste de cadeaux créée', 'mrz-gift-lists-exp' ),
            get_bloginfo( 'name' )
        );

        $message = $this->get_fallback_template('list-created-admin', [
            'list'     => $list,
            'user'     => $user,
            'list_url' => $list_url,
        ] );

        $this->send( $admin_email, $subject, $message );
    }

    /**
     * Envoie une notification d'achat au créateur de la liste
     *
     * @param int    $item_id    ID de l'item
     * @param int    $list_id    ID de la liste
     * @param int    $order_id   ID de la commande
     * @param string $buyer_name Nom de l'acheteur
     */
    public function send_item_purchased_notification( int $item_id, int $list_id, int $order_id, string $buyer_name ): void {
        // Vérifie si les notifications sont activées
        if ( ! get_option( 'mrzgle_notify_owner_on_purchase', 1 ) ) {
            return;
        }

        $list = $this->list_repo->find( $list_id );

        if ( ! $list ) {
            return;
        }

        $user = get_user_by( 'id', $list->user_id );

        if ( ! $user ) {
            return;
        }

        $item = $this->item_repo->find( $item_id );
        $order = wc_get_order( $order_id );

        if ( ! $item || ! $order ) {
            return;
        }

        // Récupère le produit WooCommerce
        $product_id = $item->variation_id ?: $item->product_id;
        $product = wc_get_product( $product_id );

        // Récupère le message cadeau si présent
        $gift_message = $order->get_meta( '_mrzgle_gift_message' );

        $subject = sprintf(
            __( '🎁 Un cadeau a été offert depuis votre liste "%s"', 'mrz-gift-lists-exp' ),
            $list->title
        );

        $message = $this->get_fallback_template('item-purchased-owner', [
            'list'         => $list,
            'item'         => $item,
            'product'      => $product,
            'buyer_name'   => $buyer_name,
            'gift_message' => $gift_message,
            'order'        => $order,
        ] );

        $this->send( $user->user_email, $subject, $message );

        // Notification admin également
        $admin_email = get_option( 'mrzgle_admin_email', '' );

        if ( $admin_email && $admin_email !== $user->user_email ) {
            $admin_subject = sprintf(
                __( '[%s] Achat depuis une liste de cadeaux', 'mrz-gift-lists-exp' ),
                get_bloginfo( 'name' )
            );

            $admin_message = $this->get_fallback_template('item-purchased-admin', [
                'list'       => $list,
                'item'       => $item,
                'product'    => $product,
                'buyer_name' => $buyer_name,
                'order'      => $order,
                'owner'      => $user,
            ] );

            $this->send( $admin_email, $admin_subject, $admin_message );
        }
    }

    /**
     * Construit le corps d'un e-mail (texte).
     *
     * @param string $template Nom du template
     * @param array  $data     Données
     * @return string
     */
    private function get_fallback_template( string $template, array $data ): string {
        $site_name = get_bloginfo( 'name' );

        switch ( $template ) {
            case 'list-created-admin':
                $list = $data['list'];
                $user = $data['user'];
                $list_url = $data['list_url'];

                return sprintf(
                    __( "Bonjour,\n\nUne nouvelle liste de cadeaux a été créée sur %s.\n\nTitre : %s\nCréateur : %s (%s)\n\nVoir la liste : %s\n\nCordialement,\n%s", 'mrz-gift-lists-exp' ),
                    $site_name,
                    $list->title,
                    $user ? $user->display_name : __( 'Inconnu', 'mrz-gift-lists-exp' ),
                    $user ? $user->user_email : '',
                    $list_url,
                    $site_name
                );

            case 'item-purchased-owner':
                $list = $data['list'];
                $product = $data['product'];
                $buyer_name = $data['buyer_name'];
                $gift_message = $data['gift_message'] ?? '';

                $message = sprintf(
                    __( "Bonjour,\n\nBonne nouvelle ! Un cadeau a été offert depuis votre liste \"%s\".\n\nProduit : %s\nOffert par : %s", 'mrz-gift-lists-exp' ),
                    $list->title,
                    $product ? $product->get_name() : __( 'Produit', 'mrz-gift-lists-exp' ),
                    $buyer_name
                );

                if ( $gift_message ) {
                    $message .= sprintf(
                        __( "\n\nMessage : %s", 'mrz-gift-lists-exp' ),
                        $gift_message
                    );
                }

                $message .= sprintf(
                    __( "\n\nCordialement,\n%s", 'mrz-gift-lists-exp' ),
                    $site_name
                );

                return $message;

            case 'item-purchased-admin':
                $list = $data['list'];
                $product = $data['product'];
                $buyer_name = $data['buyer_name'];
                $order = $data['order'];
                $owner = $data['owner'];

                return sprintf(
                    __( "Bonjour,\n\nUn achat a été effectué depuis une liste de cadeaux.\n\nListe : %s\nPropriétaire : %s (%s)\nProduit : %s\nAcheteur : %s\nCommande : #%d\n\nCordialement,\n%s", 'mrz-gift-lists-exp' ),
                    $list->title,
                    $owner->display_name,
                    $owner->user_email,
                    $product ? $product->get_name() : __( 'Produit', 'mrz-gift-lists-exp' ),
                    $buyer_name,
                    $order->get_id(),
                    $site_name
                );

            default:
                return '';
        }
    }

    /**
     * Envoie un email
     *
     * @param string $to      Destinataire
     * @param string $subject Sujet
     * @param string $message Message
     * @return bool
     */
    private function send( string $to, string $subject, string $message ): bool {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
        ];

        // Convertit le texte en HTML simple
        $html_message = $this->text_to_html( $message );

        return wp_mail( $to, $subject, $html_message, $headers );
    }

    /**
     * Convertit le texte en HTML
     *
     * @param string $text Texte brut
     * @return string
     */
    private function text_to_html( string $text ): string {
        $site_name = get_bloginfo( 'name' );
        $site_url = home_url();

        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #f8f9fa; padding: 20px; text-align: center; border-bottom: 3px solid #2271b1; }
        .content { padding: 30px 20px; }
        .footer { background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; }
        a { color: #2271b1; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">' . esc_html( $site_name ) . '</h1>
    </div>
    <div class="content">' . nl2br( esc_html( $text ) ) . '</div>
    <div class="footer">
        <p>' . sprintf( __( 'Cet email a été envoyé par %s', 'mrz-gift-lists-exp' ), '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>' ) . '</p>
    </div>
</body>
</html>';

        return $html;
    }
}
