<?php
namespace MG\Instamojo\GiveWP\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    wp_die( 'Cheating Huh?' );
}

/** Version-specific Instamojo transport. No requests are retried across API versions. */
class ApiClient {
    private $version;
    private $environment;

    public function __construct( $version, $environment ) {
        $this->version = 'v2' === $version ? 'v2' : '1.1';
        $this->environment = 'test' === $environment ? 'test' : 'live';
    }

    private function base_url() {
        if ( 'v2' === $this->version ) {
            return 'test' === $this->environment ? 'https://test.instamojo.com/v2/' : 'https://api.instamojo.com/v2/';
        }
        return 'test' === $this->environment ? 'https://test.instamojo.com/api/1.1/' : 'https://www.instamojo.com/api/1.1/';
    }

    private function option( $suffix ) {
        return trim( (string) give_get_option( 'mg_instamojo_v2_' . $this->environment . '_' . $suffix ) );
    }

    /** Public credential identifier; never persist the secret or access token. */
    public function account_fingerprint() {
        $identifier = 'v2' === $this->version ? $this->option( 'client_id' ) : trim( (string) give_get_option( 'instamojo_get_' . $this->environment . '_api_key' ) );
        return '' === $identifier ? '' : hash( 'sha256', $this->version . ':' . $this->environment . ':' . $identifier );
    }

    public function is_configured() {
        if ( 'v2' === $this->version ) {
            return '' !== $this->option( 'client_id' ) && '' !== $this->option( 'client_secret' ) && '' !== $this->option( 'webhook_salt' );
        }
        $headers = $this->headers();
        return '' !== $headers['X-Api-Key'] && '' !== $headers['X-Auth-Token'];
    }

    private function headers() {
        if ( '1.1' === $this->version ) {
            return array(
                'X-Api-Key' => trim( (string) give_get_option( 'instamojo_get_' . $this->environment . '_api_key' ) ),
                'X-Auth-Token' => trim( (string) give_get_option( 'instamojo_get_' . $this->environment . '_auth_token' ) ),
            );
        }
        $token = $this->access_token();
        return $token ? array( 'Authorization' => 'Bearer ' . $token ) : false;
    }

    private function access_token() {
        $id = $this->option( 'client_id' );
        $secret = $this->option( 'client_secret' );
        if ( '' === $id || '' === $secret ) {
            return '';
        }
        $cache_key = $this->token_cache_key();
        $cached = get_transient( $cache_key );
        if ( is_string( $cached ) && '' !== $cached ) {
            return $cached;
        }
        $url = 'test' === $this->environment ? 'https://test.instamojo.com/oauth2/token/' : 'https://api.instamojo.com/oauth2/token/';
        $response = wp_remote_post( $url, array( 'timeout' => 20, 'body' => array( 'grant_type' => 'client_credentials', 'client_id' => $id, 'client_secret' => $secret ) ) );
        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return '';
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) || empty( $body['access_token'] ) || ! is_string( $body['access_token'] ) ) {
            return '';
        }
        $expires = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 0;
        if ( $expires > 90 ) {
            set_transient( $cache_key, $body['access_token'], $expires - 60 );
        }
        return $body['access_token'];
    }

    private function token_cache_key() {
        return 'mg_instamojo_v2_' . md5( $this->environment . ':' . $this->option( 'client_id' ) . ':' . $this->option( 'client_secret' ) );
    }

    private function send( $method, $url, $body = null ) {
        $headers = $this->headers();
        if ( ! $headers || in_array( '', $headers, true ) ) {
            return new \WP_Error( 'instamojo_credentials', 'Instamojo credentials are incomplete.' );
        }
        $args = array( 'timeout' => 20, 'headers' => $headers );
        if ( 'POST' === $method ) {
            $args['body'] = $body;
            $response = wp_remote_post( $url, $args );
        } else {
            $response = wp_remote_get( $url, $args );
        }
        // HTTP 401 is a definite rejection. Refresh once on the same API and account.
        if ( 'v2' === $this->version && ! is_wp_error( $response ) && 401 === (int) wp_remote_retrieve_response_code( $response ) ) {
            delete_transient( $this->token_cache_key() );
            $headers = $this->headers();
            if ( ! $headers ) {
                return $response;
            }
            $args['headers'] = $headers;
            return 'POST' === $method ? wp_remote_post( $url, $args ) : wp_remote_get( $url, $args );
        }
        return $response;
    }

    public function create( $data ) {
        $path = 'v2' === $this->version ? 'payment_requests/' : 'payment-requests/';
        return $this->send( 'POST', $this->base_url() . $path, $data );
    }

    public function payment( $payment_id ) {
        if ( ! preg_match( '/^[A-Za-z0-9_-]+$/', $payment_id ) ) {
            return new \WP_Error( 'instamojo_payment_request', 'Instamojo payment details cannot be requested.' );
        }
        $url = $this->base_url() . 'payments/' . rawurlencode( $payment_id ) . ( 'v2' === $this->version ? '/' : '' );
        return $this->send( 'GET', $url );
    }

    public function parse_create( $response ) {
        if ( is_wp_error( $response ) ) {
            return false;
        }
        $code = (int) wp_remote_retrieve_response_code( $response );
        if ( ( 'v2' === $this->version && 200 !== $code && 201 !== $code ) || ( '1.1' === $this->version && 201 !== $code ) ) {
            return false;
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $request = '1.1' === $this->version && isset( $body['payment_request'] ) ? $body['payment_request'] : $body;
        if ( ! is_array( $request ) || empty( $request['id'] ) || empty( $request['longurl'] ) || ! is_string( $request['id'] ) || ! is_string( $request['longurl'] ) || 0 !== strpos( $request['longurl'], 'https://' ) ) {
            return false;
        }
        return array( 'id' => $request['id'], 'url' => $request['longurl'] );
    }

    public function parse_payment( $response ) {
        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }
        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $payment = '1.1' === $this->version && isset( $body['payment'] ) ? $body['payment'] : $body;
        return is_array( $payment ) ? $payment : false;
    }
}
