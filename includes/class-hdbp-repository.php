<?php

namespace htrxuan\hdbp;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The only place quantity-discount tiers are ever written.
 *
 * CVE-2025-31598 (Stored XSS) in "Quantity Dynamic Pricing & Bulk Discounts for WooCommerce"
 * (<= 4.0.3): a rule's saved text was later rendered to storefront visitors without escaping.
 *
 * This class closes that by construction:
 *   - save_tiers() is called from exactly one place (HDBP_Admin's settings-save handler),
 *     behind current_user_can('manage_woocommerce') and a verified nonce.
 *   - Every stored label is sanitize_text_field()'d here regardless of caller, and is only
 *     ever displayed through esc_html() at the point of output (HDBP_Frontend) -- there is no
 *     rich-text/HTML field anywhere in this plugin.
 *   - The discount itself is never a value taken from a request. HDBP_Pricing computes the
 *     price purely from the product's OWN regular price and the matching tier's admin-set
 *     percentage -- there is no "discount amount" field a cart request can influence.
 */
class HDBP_Repository
{
    const OPTION_KEY = 'hdbp_tiers';
    const META_EXCLUDE = '_hdbp_exclude';

    /**
     * @return array<int, array{min_qty:int, percent:float, label:string}> Sorted by min_qty
     *         descending, so the first tier a caller finds that qualifies is the best one.
     */
    public static function get_tiers()
    {
        $tiers = get_option(self::OPTION_KEY, array());
        if (!is_array($tiers)) {
            return array();
        }
        usort($tiers, function ($a, $b) {
            return $b['min_qty'] <=> $a['min_qty'];
        });
        return $tiers;
    }

    /**
     * The best (highest-percent, highest-qualifying) tier for a given quantity, or null.
     *
     * @param int $quantity
     * @return array|null
     */
    public static function get_tier_for_quantity($quantity)
    {
        $quantity = (int) $quantity;
        foreach (self::get_tiers() as $tier) {
            if ($quantity >= $tier['min_qty']) {
                return $tier;
            }
        }
        return null;
    }

    /**
     * The only write path. Every value is validated here regardless of caller.
     *
     * @param array $rows Each: ['min_qty'=>?, 'percent'=>?, 'label'=>?]
     * @return array The stored, cleaned tier list.
     */
    public static function save_tiers(array $rows)
    {
        $tiers = array();
        $seen_qty = array();

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $min_qty = isset($row['min_qty']) ? absint($row['min_qty']) : 0;
            $percent = isset($row['percent']) ? (float) $row['percent'] : 0.0;

            if ($min_qty < 2 || $percent <= 0 || $percent >= 100) {
                continue; // A single-item "discount" or a 0%/100%+ figure is never valid.
            }
            if (isset($seen_qty[$min_qty])) {
                continue; // One rule per quantity threshold; first one wins, no silent overwrite.
            }

            $label = isset($row['label']) ? sanitize_text_field($row['label']) : '';
            if ('' === $label) {
                /* translators: %d: minimum quantity */
                $label = sprintf(__('Buy %d or more and save', 'hdwebmobile-bulk-pricing'), $min_qty);
            }

            $tiers[] = array(
                'min_qty' => $min_qty,
                'percent' => round($percent, 2),
                'label'   => $label,
            );
            $seen_qty[$min_qty] = true;
        }

        update_option(self::OPTION_KEY, $tiers);
        return self::get_tiers();
    }

    public static function is_excluded($product_id)
    {
        return 'yes' === get_post_meta($product_id, self::META_EXCLUDE, true);
    }
}
