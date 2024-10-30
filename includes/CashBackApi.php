<?php
/**
 * @author    Hotshopper <info@hotshopper.nl>
 * @license   GPL-2.0+
 * @date 2016.
 * @link      https://www.hotshopper.nl
 */


class CashBackApi {

    /**
     * Wrapper for get calls
     * @param $call
     * @param null $parameters
     * @return string
     */
    public static function get($call, $parameters = null ){
        $arguments = array(
            'body' => $parameters,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'cookies' => array()
        );
        $response = wp_remote_get( "https://api.hotshopper.nl/api/".$call, $arguments );
        $body = wp_remote_retrieve_body( $response );
        return $body;
    }

    /**
     * Wrapper for post calls
     * @param $call
     * @param null $parameters
     * @return string
     */
    public static function post($call, $parameters = null){
        $arguments = array(
            'body' => $parameters,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'cookies' => array()
        );
        $response = wp_remote_post( "https://api.hotshopper.nl/api/".$call, $arguments );
        $body = wp_remote_retrieve_body( $response );
        return $body;
    }

    /**
     * Custom Method calls
     * @param $call
     * @param null $post
     * @param null $method
     *
     * @return mixed|string
     */
    public static function call($call, $post = null, $method = null){
        $arguments = array(
            'body' => $post,
            'method' => $method,
            'timeout' => '5',
            'redirection' => '5',
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'cookies' => array()
        );
        $response = wp_remote_post( "https://api.hotshopper.nl/api/".$call, $arguments );
        $body = wp_remote_retrieve_body( $response );

        return $body;
    }
}