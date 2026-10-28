=== MRZ Gift Lists Exp ===
Contributors: m0r3z
Tags: gift list, wishlist, woocommerce, gift registry, wedding registry
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.11.5
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
3. Create a page and add the `[mrzgle_lists]` shortcode to display and manage lists.
4. Select that page as "Page des listes" under "MRZ Gift Lists > Réglages" (required: share links and redirects are built from it), then adjust the other options.

== Frequently Asked Questions ==

= Do buyers need an account? =

No. Anyone with the share link can buy items from a list. Creating a list requires being logged in.

= Does it work with any theme? =

Yes. The front-end CSS is intentionally minimal (structure only), so it inherits your theme's look. Style the `.mrz-gle-*` classes in your own CSS to match your brand.

== Changelog ==

= 0.11.5 =
* Fix: styles and scripts now load wherever the [mrzgle_lists] shortcode is rendered, including inside page builder blocks.
* Change: activation no longer creates a lists page. Select it under "MRZ Gift Lists > Réglages"; an admin notice is shown until it is set.

= 0.11.4 =
* Declared compatibility with WordPress 7.1.

= 0.11.3 =
* Removed the redundant load_plugin_textdomain() call — WordPress.org loads translations automatically since WordPress 4.6.

= 0.11.2 =
* Renamed all PHP identifiers (functions, classes, namespace, constants, options, hooks, meta keys) to a consistent 4+ character prefix ("mrzgle") to meet WordPress.org naming guidelines and avoid conflicts with other plugins.
* The shortcode is now `[mrzgle_lists]` (previously `[mrz_gift_lists]`).

= 0.11.1 =
* WordPress.org compliance pass: wp_unslash() applied to all request data before sanitising, escaped wp_die() messages, and "Tested up to" updated.
* The "Gift list" order column now also works on HPOS (High-Performance Order Storage), not just the classic orders screen.
* Removed dead code: the unused email-template loader (extract()), leftover "type" repository stubs and a phantom database table, and non-functional bulk actions in the admin list table.

= 0.11.0 =
* New: edit an existing list (title, end date, description, image) from the list page or the "My lists" view.
* New: lists expire automatically after their end date — they are no longer visible to guests once it has passed (the owner keeps access and sees an "expired" notice).
* The end date and an image are now required when creating a list; the "event date" is relabelled "end date" and acts as the list's publication deadline.
* Fix: apostrophes in list titles and descriptions are no longer stored/displayed with a stray backslash.
* An "Add a product" button is now shown in the empty-list state.
* Removed the "Gift lists" heading from the home view.

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
