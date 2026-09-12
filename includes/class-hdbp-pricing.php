<?php

namespace htrxuan\hdbp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Applies the admin-configured quantity tiers to the cart. The discount is always computed
 * from the product's own current price (whatever Currency Switcher, Wholesale Pricing, or an
 * active sale already resolved it to) times the matching tier's admin-set percentage -- never
 * a value taken from the request. There is no field anywhere a shopper can submit a discount
 * amount or a tier threshold.
 */
final class HDBP_Pricing
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_tiers'), 20);
        add_filter('woocommerce_get_item_data', array($this, 'cart_item_notice'), 10, 2);
    }

    public function apply_tiers($cart)
    {
        if (!$cart instanceof \WC_Cart) {
            return;
        }
        // woocommerce_before_calculate_totals can fire more than once in the same request
        // (e.g. once for shipping, once for final totals); without this guard the discount
        // would be re-applied to an already-discounted price and compound. This is the same
        // guard convention used across the WooCommerce extension ecosystem for this exact hook.
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            $product = isset($cart_item['data']) ? $cart_item['data'] : null;
            if (!$product instanceof \WC_Product) {
                continue;
            }
            if (HDBP_Repository::is_excluded($product->get_id())) {
                continue;
            }
            $tier = HDBP_Repository::get_tier_for_quantity($cart_item['quantity']);
            if (!$tier) {
                continue;
            }
            $current_price = (float) $product->get_price();
            if ($current_price <= 0) {
                continue;
            }
            $product->set_price($current_price * (1 - $tier['percent'] / 100));
        }
    }

    public function cart_item_notice($item_data, $cart_item)
    {
        if (empty($cart_item['product_id']) || HDBP_Repository::is_excluded($cart_item['product_id'])) {
            return $item_data;
        }
        $tier = HDBP_Repository::get_tier_for_quantity($cart_item['quantity']);
        if (!$tier) {
            return $item_data;
        }
        $item_data[] = array(
            'key'     => __('Bulk discount', 'hdwebmobile-bulk-pricing'),
            /* translators: %s: discount percentage */
            'value'   => sprintf(__('%s%% off applied', 'hdwebmobile-bulk-pricing'), rtrim(rtrim(number_format((float) $tier['percent'], 2, '.', ''), '0'), '.')),
            'display' => '',
        );
        return $item_data;
    }
}
