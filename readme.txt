=== HDWebmobile Bulk Pricing ===
Contributors: htrxuan
Donate link: https://paypal.me/htrxuan/20
Tags: woocommerce, bulk discount, quantity discount, tiered pricing, volume pricing
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Quantity-tiered discounts for any product. Rule labels are always escaped at output, and configuration is capability- and nonce-gated.

== Description ==

HDWebmobile Bulk Pricing gives every customer a percentage off when they buy enough of the same product -- "Buy 3 or more and save 10%", "Buy 10 or more and save 20%". Tiers apply store-wide; any individual product can opt out from its own Product data panel.

= Why this plugin exists =
"Quantity Dynamic Pricing & Bulk Discounts for WooCommerce" (versions up to and including 4.0.3) shipped CVE-2025-31598: a stored Cross-Site Scripting vulnerability where a saved rule's text was later rendered to storefront visitors without escaping.

This plugin is built so that mistake has nowhere to happen:

* **One write path, properly gated.** `HDBP_Repository::save_tiers()` is called from exactly one place -- the settings-save handler -- behind `current_user_can('manage_woocommerce')` **and** a verified nonce.
* **Every label is sanitised on save and escaped again on output.** There is no rich-text field anywhere in this plugin; a tier's label is plain text, `sanitize_text_field()`'d when saved and `esc_html()`'d every time it's displayed.
* **The discount is never a value taken from a request.** It is always computed from the product's own current price times the matching tier's admin-set percentage -- there is no "discount amount" or "tier threshold" field a shopper's request can influence.

= Key Features =
* Any number of quantity tiers, each with its own threshold, percentage, and customer-facing label
* Applies automatically in the cart based on quantity -- no coupon code needed
* Per-product opt-out
* A tier summary shown automatically on the product page
* Works on classic and block-based Cart and Checkout (the discount is applied at the cart-calculation level, not a template hook)

= Limitations (please read before installing) =
* Tiers are store-wide, not per-product or per-category (a product can only be included or fully excluded)
* The discount is a percentage off the product's current price (including any active sale or other price adjustment already applied) -- it is not stacked as a separate line-item coupon
* No "buy X of category Y" style tiers in this version -- quantity is counted per individual product

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/hdwebmobile-bulk-pricing` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Bulk Pricing** to add your first tier.

== How to Use ==

= 1. Add tiers =
On the Bulk Pricing tab, set a minimum quantity, a discount percentage, and a label for each tier.

= 2. Customers see it automatically =
The product page shows the available tiers, and the discount applies the moment enough units are in the cart.

= 3. Exclude a product =
On that product's Product data panel (General tab), tick "Exclude from bulk pricing".

== Screenshots ==

1. The Bulk Pricing settings tab.
2. The tier summary on a product page.
3. The discount applied in the cart.

== Changelog ==

= 1.0.0 =
* Initial release: store-wide quantity-tiered discounts with per-product opt-out, capability- and nonce-gated configuration, every label sanitised and escaped.
