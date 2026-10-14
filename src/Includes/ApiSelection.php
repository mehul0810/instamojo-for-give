<?php
namespace MG\Instamojo\GiveWP\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    wp_die( 'Cheating Huh?' );
}

/** Selects the API for new donations without changing legacy credentials. */
class ApiSelection {
    public static function has_legacy_credentials() {
        foreach ( array( 'live', 'test' ) as $environment ) {
            if ( trim( (string) give_get_option( 'instamojo_get_' . $environment . '_api_key' ) ) && trim( (string) give_get_option( 'instamojo_get_' . $environment . '_auth_token' ) ) ) {
                return true;
            }
        }
        return false;
    }

    public static function for_new_donation() {
        $selected = give_get_option( 'mg_instamojo_api_version' );
        if ( '1.1' === $selected || 'v2' === $selected ) {
            return $selected;
        }
        $selected = self::has_legacy_credentials() ? '1.1' : 'v2';
        $filtered = apply_filters( 'instamojo_for_give_api_version', $selected );
        return '1.1' === $filtered || 'v2' === $filtered ? $filtered : $selected;
    }

    public static function for_donation( $donation_id ) {
        $stored = give_get_meta( $donation_id, 'MG_instamojo_for_give_api_version', true );
        return 'v2' === $stored ? 'v2' : '1.1';
    }

    public static function environment_for_donation( $donation_id ) {
        $stored = give_get_meta( $donation_id, 'MG_instamojo_for_give_environment', true );
        if ( 'live' === $stored || 'test' === $stored ) {
            return $stored;
        }
        $mode = give_get_meta( $donation_id, '_give_payment_mode', true );
        if ( 'live' === $mode || 'test' === $mode ) {
            return $mode;
        }
        return '';
    }
}
