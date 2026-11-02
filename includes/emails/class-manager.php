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
            /* translators: %s: Site name. */
            __( '[%s] New gift list created', 'mrz-gift-lists-exp' ),
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
            /* translators: %s: Gift list title. */
            __( '🎁 A gift has been purchased from your list "%s"', 'mrz-gift-lists-exp' ),
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
                /* translators: %s: Site name. */
                __( '[%s] Purchase from a gift list', 'mrz-gift-lists-exp' ),
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
                    /* translators: 1: Site name, 2: Gift list title, 3: Creator display name, 4: Creator email address, 5: Gift list URL, 6: Site name. */
                    __( "Hello,\n\nA new gift list has been created on %1\$s.\n\nTitle: %2\$s\nCreator: %3\$s (%4\$s)\n\nView the list: %5\$s\n\nBest regards,\n%6\$s", 'mrz-gift-lists-exp' ),
                    $site_name,
                    $list->title,
                    $user ? $user->display_name : __( 'Unknown', 'mrz-gift-lists-exp' ),
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
                    /* translators: 1: Gift list title, 2: Product name, 3: Buyer name. */
                    __( "Hello,\n\nGood news! A gift has been purchased from your list \"%1\$s\".\n\nProduct: %2\$s\nGifted by: %3\$s", 'mrz-gift-lists-exp' ),
                    $list->title,
                    $product ? $product->get_name() : __( 'Product', 'mrz-gift-lists-exp' ),
                    $buyer_name
                );

                if ( $gift_message ) {
                    $message .= sprintf(
                        /* translators: %s: Gift message left by the buyer. */
                        __( "\n\nMessage: %s", 'mrz-gift-lists-exp' ),
                        $gift_message
                    );
                }

                $message .= sprintf(
                    /* translators: %s: Site name. */
                    __( "\n\nBest regards,\n%s", 'mrz-gift-lists-exp' ),
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
                    /* translators: 1: Gift list title, 2: List owner display name, 3: List owner email address, 4: Product name, 5: Buyer name, 6: Order number, 7: Site name. */
                    __( "Hello,\n\nA purchase has been made from a gift list.\n\nList: %1\$s\nOwner: %2\$s (%3\$s)\nProduct: %4\$s\nBuyer: %5\$s\nOrder: #%6\$d\n\nBest regards,\n%7\$s", 'mrz-gift-lists-exp' ),
                    $list->title,
                    $owner->display_name,
                    $owner->user_email,
                    $product ? $product->get_name() : __( 'Product', 'mrz-gift-lists-exp' ),
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
        <p>' . sprintf( /* translators: %s: Site name, linked to the site home page. */ __( 'This email was sent by %s', 'mrz-gift-lists-exp' ), '<a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a>' ) . '</p>
    </div>
</body>
</html>';

        return $html;
    }
}
