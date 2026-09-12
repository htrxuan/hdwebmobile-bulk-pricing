<?php

namespace htrxuan\hdbp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shows the admin-configured quantity tiers on the product page (e.g. "Buy 3+ and save 10%").
 * Every label was already sanitize_text_field()'d on save (HDBP_Repository::save_tiers());
 * it is escaped again here at the point of output regardless.
 */
final class HDBP_Frontend
{

    private static $instance = null;
    private static $rendered = false;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('woocommerce_single_product_summary', array($this, 'render'), 25);
        add_filter('render_block', array($this, 'block_fallback'), 10, 2);
    }

    public function render()
    {
        if (self::$rendered) {
            return;
        }
        global $product;
        if (!$product instanceof \WC_Product) {
            return;
        }
        $html = $this->get_html($product->get_id());
        if ('' === $html) {
            return;
        }
        self::$rendered = true;
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_html() escapes every dynamic value at output.
    }

    public function block_fallback($block_content, $block)
    {
        if (self::$rendered) {
            return $block_content;
        }
        $name = is_array($block) && isset($block['blockName']) ? $block['blockName'] : '';
        if ('woocommerce/add-to-cart-form' !== $name || !is_singular('product')) {
            return $block_content;
        }
        $html = $this->get_html(get_queried_object_id());
        if ('' === $html) {
            return $block_content;
        }
        self::$rendered = true;
        return $block_content . $html;
    }

    private function get_html($product_id)
    {
        if (HDBP_Repository::is_excluded($product_id)) {
            return '';
        }
        $tiers = HDBP_Repository::get_tiers();
        if (empty($tiers)) {
            return '';
        }
        // Show ascending by quantity for a natural reading order, even though get_tiers()
        // itself is sorted descending for matching purposes.
        $tiers = array_reverse($tiers);

        $html = '<ul class="hdbp-tiers">';
        foreach ($tiers as $tier) {
            $html .= '<li>' . esc_html($tier['label']) . ' &mdash; ' . esc_html(rtrim(rtrim(number_format((float) $tier['percent'], 2, '.', ''), '0'), '.')) . '%</li>';
        }
        $html .= '</ul>';
        return $html;
    }
}
