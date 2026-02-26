<?php

namespace HelpCustomPlugin;

class External_API_Client {
    private $base_url = 'http://188.245.183.14:3008';

    /**
     * Get token from cache or authenticate if expired
     */
    public function get_token() {
        $token = get_transient('help_api_token');

        if (false === $token) {
            error_log('Help Sync: Token expired or missing. Re-authenticating...');
            $token = $this->login(HELP_API_USERNAME, HELP_API_PASSWORD);

            if (!is_wp_error($token)) {
                // Store token for 55 minutes (assuming 1hr expiry)
                set_transient('help_api_token', $token, 55 * MINUTE_IN_SECONDS);
            }
        }

        return $token;
    }

    public function login($username, $password) {
        $response = wp_remote_post($this->base_url . '/login', [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode(['username' => $username, 'password' => $password]),
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) return $response;

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['token'] ?? new \WP_Error('no_token', 'No token in login response');
    }

    /**
     * Fetch products from the /get endpoint
     */
    public function fetch_products() {
        $token = $this->get_token();
        if (is_wp_error($token)) return $token;

        $response = wp_remote_post($this->base_url . '/get', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'body' => json_encode([
                'method'  => 'getIdent',
                'filters' => [
                    'adTimeChg' => [
                        'operator' => '>=',
                        'value'    => '2020-01-01'
                    ]
                ],
                'offset' => 0,
                'limit'  => -1
            ]),
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) return $response;

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}
