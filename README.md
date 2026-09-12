# HDWebmobile Bulk Pricing

Quantity-tiered discounts for any WooCommerce product. Rule labels are always escaped at output, and configuration is capability- and nonce-gated.

- **WordPress.org:** https://wordpress.org/plugins/hdwebmobile-bulk-pricing/
- **Requires:** WordPress 6.9+, WooCommerce, PHP 7.4+
- **License:** GPLv2 or later

## Description

A percentage off when a customer buys enough of the same product — "Buy 3+ save 10%", "Buy 10+ save 20%" — applied store-wide, with a per-product opt-out.

## Why this plugin exists

"Quantity Dynamic Pricing & Bulk Discounts for WooCommerce" (≤ 4.0.3) shipped CVE-2025-31598 — stored XSS via an unescaped saved rule.

Closed by construction:

* **One write path** — `HDBP_Repository::save_tiers()`, behind `manage_woocommerce` + a verified nonce.
* **Every label sanitised on save, escaped on output** — no rich-text field anywhere.
* **The discount is never a request value** — computed only from the product's current price × the matching tier's admin-set percentage.

## Features

* Any number of tiers, each with threshold, percentage, and label
* Applies automatically in the cart by quantity — no coupon code
* Per-product opt-out
* Tier summary shown on the product page
* Classic and block Cart/Checkout (applied at cart-calculation level)

## Limitations

* Tiers are store-wide (a product is included or fully excluded, not per-category)
* Percentage off the current price, not a separate stacked coupon line item
* No "buy X of category Y" tiers — quantity is per individual product

## Installation

1. Upload to `/wp-content/plugins/hdwebmobile-bulk-pricing`, or install through the WordPress plugins screen.
2. Activate. WooCommerce must already be installed and active.
3. Go to **WooCommerce > HDWebmobile > Bulk Pricing**.

## License

GPLv2 or later — https://www.gnu.org/licenses/gpl-2.0.html
