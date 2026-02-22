<?php

namespace HelpCustomPlugin;

class Sync_Manager {

    public function __construct() {
        if (is_admin() && !wp_doing_ajax()) {
            add_action('admin_init', [$this, 'check_for_sync_trigger']);
        }
    }

    public function check_for_sync_trigger() {
        // Triggered by: /wp-admin/?help_run_sync=1
        if (isset($_GET['help_run_sync'])) {
            $this->handle_sync();

            // Redirect back to dashboard so the URL clears, 
            // preventing accidental double-triggers on refresh.
            wp_redirect(admin_url('index.php?help_sync_complete=1'));
            exit;
        }

        // Optional: Show a small notice that it finished
        if (isset($_GET['help_sync_complete'])) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>Help Sync: Check debug.log for results.</p></div>';
            });
        }
    }

    public function handle_sync() {
        $username = defined('HELP_API_USERNAME') ? HELP_API_USERNAME : '';
        $password = defined('HELP_API_PASSWORD') ? HELP_API_PASSWORD : '';

        if (empty($username) || empty($password)) {
            error_log('Help Sync Error: Credentials missing');
            return;
        }

        error_log('--- Help Sync Started ---');

        $api = new External_API_Client();
        $token = $api->login($username, $password);

        if (is_wp_error($token)) {
            error_log('Help Sync Auth Error: ' . $token->get_error_message());
            return;
        }

        error_log('Help Sync Auth Success. Token: ' . $token);

        // NEXT STEP: $this->fetch_and_sync_products($token);

        error_log('--- Help Sync Finished ---');
    }
}
