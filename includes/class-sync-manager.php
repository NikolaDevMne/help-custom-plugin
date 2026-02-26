<?php

namespace HelpCustomPlugin;

use Automattic\WooCommerce\Client;

class Sync_Manager {
    private $api_client;
    private $woo_client;

    public function __construct() {
        $this->api_client = new External_API_Client();

        // Use the constants from wp-config.php
        $this->woo_client = new Client(
            get_site_url(),
            HELP_WC_CONSUMER_KEY,
            HELP_WC_CONSUMER_SECRET,
            ['version' => 'wc/v3']
        );

        if (is_admin() && !wp_doing_ajax()) {
            add_action('admin_init', [$this, 'check_for_sync_trigger']);
        }
    }

    public function check_for_sync_trigger() {
        if (isset($_GET['help_run_sync'])) {
            $this->handle_sync();
            wp_redirect(admin_url('index.php?help_sync_complete=1'));
            exit;
        }
    }

    public function handle_sync() {
        error_log('--- Help Sync: Fetching Products ---');

        $external_data = $this->api_client->fetch_products();

        if (is_wp_error($external_data)) {
            error_log('Help Sync Error: ' . $external_data->get_error_message());
            return;
        }

        // The API returns an array of objects: { Ident, Name }
        // We will process them in batches of 100 (WooCommerce limit)
        if (!empty($external_data) && is_array($external_data)) {
            $this->sync_to_woocommerce($external_data);
        }

        error_log('--- Help Sync: Finished ---');
    }

    private function sync_to_woocommerce($items) {
        $create_batch = [];

        foreach ($items as $item) {
            $create_batch[] = [
                'name' => $item['Name'],
                'type' => 'simple',
                'sku'  => $item['Ident'], // Using Ident as SKU to prevent duplicates
                'regular_price' => '0',   // Placeholder price
            ];

            // WooCommerce Batch API limit is 100 items per request
            if (count($create_batch) >= 100) {
                $this->send_batch($create_batch);
                $create_batch = [];
            }
        }

        // Send remaining items
        if (!empty($create_batch)) {
            $this->send_batch($create_batch);
        }
    }

    private function send_batch($batch) {
        try {
            $data = ['create' => $batch];
            $result = $this->woo_client->post('products/batch', $data);
            error_log('Help Sync: Synced a batch of ' . count($batch) . ' products.');
        } catch (\Exception $e) {
            error_log('Help Sync Batch Error: ' . $e->getMessage());
        }
    }
}
