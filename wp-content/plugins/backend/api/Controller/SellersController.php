<?php

namespace CTHWP\Api\Controller;

use WC_Order;
use WC_Order_Item_Product;
use WC_Order_Query;
use WC_Product;
use WP_HTTP_Response;
use WP_REST_Controller;
use WP_REST_Server;
use WP_User;
use WP_REST_Request;

class SellersController extends WP_REST_Controller
{
    protected $namespace;
    protected $rest_base;

    public function __construct()
    {
        $this->namespace = 'cthwp/v1';
        $this->rest_base = '/sellers';
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
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_sellers'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                ], [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'create_seller'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                 ], [
                    'methods' => "POST",
                    'callback' => [$this, 'delete_seller'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                ]
            ]
        );

        register_rest_route(
            $this->namespace,
            $this->rest_base . '/code/orders/(?P<code>[a-zA-Z0-9]+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_orders_by_seller_code'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            $this->rest_base . '/customers/(?P<code>[a-zA-Z0-9]+)',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_customers'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                ],
            ],
        );

        register_rest_route(
            $this->namespace,
            $this->rest_base . '/customers',
            [
                [
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => [$this, 'get_customers'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                ],
                [
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => [$this, 'customers_change_to_seller'],
                    'permission_callback' => [$this, 'get_sellers_permissions_check'],
                    'args' => []
                ],
            ],
        );
    }

    public function customers_change_to_seller(WP_REST_Request $request)
    {
        $body = $request->get_body();
        $bodyObject = json_decode($body);
        $from_code = sanitize_text_field($bodyObject->from_code);
        $target_code = sanitize_text_field($bodyObject->target_code);
        $customers = $bodyObject->customers;
        // 先更新客户
//        if (!isset($from_code)||$from_code=="") {
//            return rest_ensure_response(["status" => 1, "message" => "先选择要转移的业务员"]);
//        }
        if (empty($customers)) {
            return rest_ensure_response(["status" => 1, "message" => "先选择客户"]);
        }
        if (!isset($target_code) || $target_code == "") {
            return rest_ensure_response(["status" => 1, "message" => "先选择目标业务员"]);
        }
        foreach ($customers as $customerid) {
            update_user_meta($customerid, 'seller_code', $target_code);
        }

        $query = new WC_Order_Query(array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => 'seller_code',
            'meta_value' => $from_code,
        ));
        $orders = $query->get_orders();

        foreach ($orders as $order) {
            $order_item = new WC_Order($order);
            $order_id = $order_item->get_id();
            update_post_meta($order_id, 'seller_code', $target_code);
        }
        return rest_ensure_response(["status" => 0, "message" => "Success"]);


//        $password = sanitize_text_field($bodyObject->password);
//        $email = sanitize_text_field($bodyObject->email);
//        $code = sanitize_text_field($bodyObject->code);
//        $first_name = sanitize_text_field($bodyObject->firstname);
//        $last_name = sanitize_text_field($bodyObject->lastname);
//        $user_data = [
//            'user_login' => $username,
//            'user_pass' => $password,
//            'user_email' => $email,
//            'first_name' => $first_name,
//            'last_name' => $last_name,
//            'role' => 'customer',
//            'meta_input' => ["seller_code" => $code]
//        ];
//        $user_id = wp_insert_user($user_data);
//        if (!is_wp_error($user_id)) {
//            return rest_ensure_response(["status" => 0, "message" => "Success"]);
//        } else {
//            return rest_ensure_response(["status" => 1, "message" => $user_id->get_error_message()]);
//        }
//        return rest_ensure_response(["status" => 1, "message" => "Fails"]);
    }

    public function get_customers($data)
    {
        global $wpdb;
        $code = $data['code'];
        $arg = array('number' => -1, "role" => 'customer');
        if (isset($code)) {
            $arg['meta_key'] = 'seller_code';
            $arg['meta_value'] = $code;
        }

//        $sql = "SELECT post_id FROM $wpdb->usermeta WHERE meta_key = 'seller_code' AND meta_value='$code'";
//        $order_list_ids = $wpdb->get_results($sql);

        $users = get_users($arg);
        $list = array();
        foreach ($users as $item) {
            $user = [];
            $seller = new WP_User($item);
            $code = get_user_meta($seller->ID, 'seller_code');
            $user['id'] = $seller->ID;
            $user['firstname'] = $seller->first_name;
            $user['lastname'] = $seller->last_name;
            $user['email'] = $seller->user_email;
            $user['username'] = $seller->user_login;
            if (!empty($code)) {
                $user['code'] = $code[0];
            }
            $have = false;
            foreach ($list as $have_user) {
                if ($have_user['id'] == $user['id']) {
                    $have = true;
                }
            }
            if (!$have) {
                $list[] = $user;
            }
        }
        return rest_ensure_response(["status" => 0, "message" => "成功", "data" => $list]);
    }

    public function get_sellers_permissions_check($request): bool
    {
        return true;
    }

    function random_strings($length_of_string)
    {
        $str_result = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';
        return substr(str_shuffle($str_result), 0, $length_of_string);
    }

    function gen_seller_code()
    {
        $args = array(
            'role' => 'seller'
        );
        $seller = get_users($args);
        foreach ($seller as $seller) {
            $seller = new WP_User($seller);
            $seller_code = get_user_meta($seller->ID, 'seller_code');
            if (!empty($seller_code)) {
                if ($seller_code[0] != '') {
                    $seller_codes[] = $seller_code[0];
                }
            }
        }

        while (true) {
            $code = $this->random_strings(6);
            if (empty($seller_codes)) {
                return $code;
            } else {
                if (!in_array($code, $seller_codes)) {
                    return $code;
                }
            }
        }
    }

    public function get_orders_by_seller_code($data)
    {
        $code = $data['code'];
        $query = new WC_Order_Query(array(
            'limit' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => 'seller_code',
            'meta_value' => $code,
        ));
        $orders = $query->get_orders();

        foreach ($orders as $order) {
            $item = [];
            $order_item = new WC_Order($order);
            $item['id'] = $order_item->get_id();
            $products = $order_item->get_items();

            $item['total'] = $order_item->get_formatted_order_total();
            $item['products'] = [];
            $item['shipping_address'] = $order_item->get_formatted_shipping_address();
            $item['shipping_full_name'] = $order_item->get_formatted_shipping_full_name();

//            $item['subtotal'] = $order_item->get_formatted_line_subtotal();
            $item['billing_address'] = $order_item->get_formatted_billing_address();
            $item['billing_full_name'] = $order_item->get_formatted_billing_full_name();

            foreach ($products as $product_item) {
                $p_item = new WC_Order_Item_Product($product_item);
                if($p_item->get_data()){
                    $product = new WC_Product($p_item->get_data()["product_id"]);
                    $showProduct['name'] = $product->get_name();
                    $showProduct['sku'] = $product->get_sku();
                    $showProduct['price'] = $product->get_price();
                    $showProduct['quantity'] = $p_item->get_data()["quantity"];
                    $showProduct['regular_price'] = $product->get_regular_price();
                    $item['products'][] = $showProduct;
                }
            }
            $json_result[] = $item;
        }

        return rest_ensure_response(["status" => 0, "message" => "成功", "data" => $json_result]);
    }

    public function get_sellers(): WP_HTTP_Response
    {
        $arg = array("role" => 'seller');
        $sellers = get_users($arg);
        $list = array();
        foreach ($sellers as $item) {
            $user = [];
            $seller = new WP_User($item);
            $code = get_user_meta($seller->ID, 'seller_code');
            $user['firstname'] = $seller->first_name;
            $user['lastname'] = $seller->last_name;
            $user['email'] = $seller->user_email;
            $user['username'] = $seller->user_login;
            if (!empty($code)) {
                $user['code'] = $code[0];
            }

            $list[] = $user;
        }

        return rest_ensure_response(["status" => 0, "message" => "成功", "data" => $list]);
    }
}
