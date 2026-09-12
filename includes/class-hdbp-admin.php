<?php

namespace htrxuan\hdbp;

if (!defined('ABSPATH')) {
    exit;
}

class HDBP_Admin
{
    const NONCE_ACTION = 'hdbp_save_tiers';

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
        require_once HDBP_PLUGIN_DIR . 'includes/class-hdbp-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_post_hdbp_save_tiers', array($this, 'save_tiers'));

        add_action('woocommerce_product_options_general_product_data', array($this, 'render_product_field'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_field'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs['bulk-pricing'] = array(
            'label'  => __('Bulk Pricing', 'hdwebmobile-bulk-pricing'),
            'order'  => 51,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    /**
     * The ONLY caller of HDBP_Repository::save_tiers(). Both the capability check AND the
     * nonce check run before a single byte of $_POST is read.
     */
    public function save_tiers()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-bulk-pricing'));
        }
        if (!isset($_POST['hdbp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hdbp_nonce'])), self::NONCE_ACTION)) {
            wp_die(esc_html__('Security check failed. Please try again.', 'hdwebmobile-bulk-pricing'));
        }

        $submitted = isset($_POST['hdbp_tier']) && is_array($_POST['hdbp_tier'])
            ? (array) wp_unslash($_POST['hdbp_tier']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- every field is individually sanitised/validated inside HDBP_Repository::save_tiers(), which never trusts caller input regardless of this authorization gate.
            : array();

        $rows = array();
        foreach ($submitted as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = array(
                'min_qty' => $row['min_qty'] ?? 0,
                'percent' => $row['percent'] ?? 0,
                'label'   => $row['label'] ?? '',
            );
        }

        HDBP_Repository::save_tiers($rows);

        wp_safe_redirect(admin_url('admin.php?page=hdwebmobile&tab=bulk-pricing&updated=1'));
        exit;
    }

    /* ---------- per-product opt-out ---------- */

    public function render_product_field()
    {
        global $post;
        woocommerce_wp_checkbox(array(
            'id'          => 'hdbp_exclude',
            'label'       => __('Exclude from bulk pricing', 'hdwebmobile-bulk-pricing'),
            'description' => __('Never apply quantity-tier discounts to this product.', 'hdwebmobile-bulk-pricing'),
            'value'       => get_post_meta($post->ID, HDBP_Repository::META_EXCLUDE, true) ?: 'no',
        ));
    }

    public function save_product_field($post_id)
    {
        if (!isset($_POST['woocommerce_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
            return;
        }
        update_post_meta($post_id, HDBP_Repository::META_EXCLUDE, isset($_POST['hdbp_exclude']) ? 'yes' : 'no');
    }

    /* ---------- hub tab ---------- */

    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-bulk-pricing'));
        }

        $tiers = array_reverse(HDBP_Repository::get_tiers()); // ascending for a natural editing order
        ?>
        <p><?php esc_html_e('Offer a percentage off when a customer buys enough of the same product. Tiers apply store-wide; opt a specific product out from its own Product data panel.', 'hdwebmobile-bulk-pricing'); ?></p>

        <?php if (!empty($_GET['updated'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only success flag. ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Bulk pricing tiers saved.', 'hdwebmobile-bulk-pricing'); ?></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="hdbp_save_tiers" />
            <?php wp_nonce_field(self::NONCE_ACTION, 'hdbp_nonce'); ?>
            <table class="widefat striped" style="max-width:760px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Minimum quantity', 'hdwebmobile-bulk-pricing'); ?></th>
                        <th><?php esc_html_e('Discount %', 'hdwebmobile-bulk-pricing'); ?></th>
                        <th><?php esc_html_e('Label shown to customers', 'hdwebmobile-bulk-pricing'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rows = $tiers; $rows[] = array('min_qty' => '', 'percent' => '', 'label' => ''); ?>
                    <?php foreach ($rows as $i => $tier) : ?>
                        <tr>
                            <td><input type="number" min="2" step="1" name="hdbp_tier[<?php echo (int) $i; ?>][min_qty]" value="<?php echo esc_attr($tier['min_qty']); ?>" style="width:6em;" /></td>
                            <td><input type="number" min="0.01" max="99" step="0.01" name="hdbp_tier[<?php echo (int) $i; ?>][percent]" value="<?php echo esc_attr($tier['percent']); ?>" style="width:7em;" /></td>
                            <td><input type="text" name="hdbp_tier[<?php echo (int) $i; ?>][label]" value="<?php echo esc_attr($tier['label']); ?>" placeholder="<?php esc_attr_e('Buy 3 or more and save', 'hdwebmobile-bulk-pricing'); ?>" style="width:100%;" /></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description"><?php esc_html_e('Leave the last row\'s quantity blank to skip it. Only one tier per quantity threshold is kept.', 'hdwebmobile-bulk-pricing'); ?></p>
            <?php submit_button(__('Save Bulk Pricing Tiers', 'hdwebmobile-bulk-pricing')); ?>
        </form>
        <?php
    }
}
