<?php

namespace htrxuan\hdbp;

if (!defined('ABSPATH')) {
    exit;
}

final class HDBP_Core
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
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDBP_PLUGIN_DIR . 'includes/class-hdbp-repository.php';
        require_once HDBP_PLUGIN_DIR . 'includes/class-hdbp-admin.php';
        require_once HDBP_PLUGIN_DIR . 'includes/class-hdbp-pricing.php';
        require_once HDBP_PLUGIN_DIR . 'includes/class-hdbp-frontend.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        // HDBP_Admin owns the hdwebmobile_hub_tabs registration used by the shared hub
        // page, so it must load unconditionally (not only when is_admin()).
        HDBP_Admin::get_instance();
        HDBP_Pricing::get_instance();
        HDBP_Frontend::get_instance();
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdbp_wc_missing_notice')) {
            return;
        }
        delete_transient('hdbp_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile Bulk Pricing requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-bulk-pricing'); ?>
            </p>
        </div>
        <?php
    }
}
