<?php

namespace ASENHA\Classes;

/**
 * Class for Disable XML-RPC module
 *
 * @since 6.9.5
 */
class Disable_XML_RPC {

    /**
     * Remove XML RPC link in head
     * 
     * @since 6.2.2
     */
    public function remove_xmlrpc_link() {
        remove_action('wp_head', 'rsd_link');
    }

    /**
     * Disable the XML-RPC component
     *
     * @since 2.2.0
     */
    public function maybe_disable_xmlrpc( $data ) {

        http_response_code(403);
        exit('You don\'t have permission to access this file.');

    }

}