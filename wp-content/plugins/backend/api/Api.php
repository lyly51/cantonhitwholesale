<?php

namespace CTHWP\Api;

use CTHWP\Api\Controller\CustomerController;
use CTHWP\Api\Controller\SellersController;
use WP_REST_Controller;

class Api extends WP_REST_Controller
{
    /**
     * Construct Function
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register API routes
     */
    public function register_routes() {
        // api register
        ( new SellersController() )->register_routes();
        ( new CustomerController() )->register_routes();
    }

}