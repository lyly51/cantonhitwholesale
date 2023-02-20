<?php

namespace CTHWP\Api\Controller;

use WP_REST_Controller;
use WP_REST_Server;
use HttpClientException;
use WP_REST_Request;

class CustomerController extends WP_REST_Controller
{
    protected $namespace;
    protected $rest_base;

    public function __construct()
    {
        $this->namespace = 'cthwp/v1';
        $this->rest_base = '/customer';
    }

    /**
     * Register Routes
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            $this->rest_base,
            [
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'register_customer'],
                    'permission_callback' => [$this, 'reg_customer_permissions_check'],
                    'args' => []
                ]
            ]
        );
    }

    public function reg_customer_permissions_check()
    {
        return true;
    }

    public function register_customer(WP_REST_Request $request)
    {
        $body = $request->get_body();
        $bodyObject = json_decode($body);
        $username = sanitize_text_field($bodyObject->username);
        $password = sanitize_text_field($bodyObject->password);
        $email = sanitize_text_field($bodyObject->email);
        $code = sanitize_text_field($bodyObject->code);
        $first_name = sanitize_text_field($bodyObject->firstname);
        $last_name = sanitize_text_field($bodyObject->lastname);
        $user_data = [
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => 'customer',
            'meta_input' => ["seller_code" => $code]
        ];
        $user_id = wp_insert_user($user_data);
        if (!is_wp_error($user_id)) {
            return rest_ensure_response(["status" => 0, "message" => "Success"]);
        } else {
            return rest_ensure_response(["status" => 1, "message" => $user_id->get_error_message()]);
        }
        return rest_ensure_response(["status" => 1, "message" => "Fails"]);
    }
}