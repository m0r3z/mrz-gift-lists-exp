=== MRZ Gift Lists ===
Contributors: mrzxp
Tags: gift list, wishlist, woocommerce, gift registry, wedding registry
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 0.10.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Gift lists for WooCommerce: your customers build a list of products to receive, share the link, and their friends and family buy them.

== Description ==

MRZ Gift Lists is a simple, minimalist gift list plugin for WooCommerce. A customer creates a list, adds any store products to it, and shares a link. Visitors open the list and buy the items straight from your shop — no account required to purchase.

The plugin ships with deliberately minimal, unstyled front-end CSS (layout and functional states only) so it blends into any theme; you style it to match your brand through your own CSS.

= Features =

* Customers create one or more gift lists and add any WooCommerce products.
* Private lists, accessible only via the share link you send — no public browsing.
* Simple copy-link sharing: a read-only link field with a one-click copy button.
* "Add to a list" control on the product page.
* E-mail notification when an item is purchased, sent to the list owner (and, optionally, to the site admin).
* Configurable maximum number of gift lists per user.
* Dedicated "My gift lists" endpoint in the WooCommerce My Account area.
* Minimal, theme-friendly front-end CSS; admin UI included.
* Translation-ready (text-domain `mrz-gift-lists-exp`).

= Requirements =

* WordPress 6.0 or later, PHP 8.0 or later.
* WooCommerce 8.0 or later (required).

= Privacy / external calls =

MRZ Gift Lists makes no external HTTP calls, loads no remote resources, and collects no telemetry. Everything runs locally on your site.

= Source code and contributions =

Development happens publicly on GitHub: https://github.com/m0r3z/mrz-gift-lists-exp

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin to `/wp-content/plugins/mrz-gift-lists-exp` and activate it.
3. Create a page and add the `[mrz_gift_lists]` shortcode to display and manage lists.
4. Adjust the options under the "MRZ Gift Lists" admin menu.

== Frequently Asked Questions ==

= Do buyers need an account? =

No. Anyone with the share link can buy items from a list. Creating a list requires being logged in.

= Does it work with any theme? =

Yes. The front-end CSS is intentionally minimal (structure only), so it inherits your theme's look. Style the `.mrz-gle-*` classes in your own CSS to match your brand.

== Changelog ==

= 0.10.1 =
* Redesigned list page: banner header, products as a list, per-item actions (offer, view product, favourite, remove) and a notice to offer from this page.
* "My lists" shown as a list view; owner can activate, deactivate or delete a list from the listing or the list page.
* Products are added from the shop (configurable shop page) — inline product search removed.
* Sharing reduced to a copy-link field (social and e-mail sharing removed).
* Private lists, accessible via the share link only — public browsing and list search removed.
* SVG button icons; login points to the WooCommerce account page; admins can always create lists.
* Fixes: empty event date no longer displayed; the list refreshes after offering or removing a product.

= 0.10.0 =
* First public beta under the MRZ / Morez.co identity.
* Gift lists for WooCommerce: create, share, and buy items from a list.
* Private lists shared by a copy-link field, and owner e-mail notifications.
* "Add to a list" control on product pages and a My Account endpoint.
* Minimal, theme-friendly front-end CSS.
* Translation-ready (text-domain `mrz-gift-lists-exp`).
