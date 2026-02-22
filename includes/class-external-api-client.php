<?php

namespace HelpCustomPlugin;

class External_API_Client {
    private $base_url = 'http://188.245.183.14:3008';

    public function login($username, $password) {
        $response = wp_remote_post($this->base_url . '/login', [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => json_encode([
                'username' => $username,
                'password' => $password,
            ]),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Handle specific HTTP error codes
        if ($code === 401) {
            return new \WP_Error('auth_fail', 'Invalid Username or Password (401).');
        }

        if ($code !== 200) {
            return new \WP_Error('api_fail', 'API Error: Received code ' . $code);
        }

        // Return the token if it exists, otherwise the whole body for debugging
        return $body['token'] ?? 'Authentication successful, but no token was found in the response.';
    }
}
