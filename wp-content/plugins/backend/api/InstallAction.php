<?php

namespace CTHWP\Api;

use CTHWP\Api\Controller\SellersController;
use WP_User;

class InstallAction
{
    /**
     * Construct Function
     */
    public function __construct()
    {
        $this->sellerController = new SellersController();
        $this->isShowSellerCol = false;
    }

    function register_actions()
    {
        // 进入checkout form 之前做检查处理 如果没有登录，需要跳转到登入页面
        add_action('woocommerce_before_checkout_form', [$this, 'handle_woocommerce_before_checkout_form_action']);

        // Add the field to the checkout 这里是获取客户的seller code
        add_action('woocommerce_after_order_notes', [$this, 'add_sellers_code_field_to_checkout_from']);

        // 检查post之中有没有 seller code
        add_action('woocommerce_checkout_process', [$this, 'seller_code_checkout_field_process']);

        // 将seller code 关联 客户下单的order 更新数据库
        add_action('woocommerce_checkout_update_order_meta', [$this, 'seller_code_checkout_field_update_order_meta']);

        //注册用户进行meta添加, 只对seller
        add_action('user_register', [$this, 'create_seller_code']);

        // 对订单数据进行过滤 这里是 post_type 加载之前可以加处理. post_type posts 可以做任何页面。
//        add_action('pre_get_posts', [$this, 'filter_woocommerce_orders_in_the_table'], 99, 1);

        // 重新查询orders 列表的数据
        add_filter('posts_where', [$this, 'orders_filter_by_seller_code']);

        // profile 查看自己的 code  和 注册链接
        add_action('show_user_profile', [$this, 'seller_code_reg_url_profile_fields'], 99, 20);

        // 在 profile 的 contact 信息中加入, 自定义数据。 根据meta 信息的 meta_key 来获取
        add_filter('user_contactmethods', [$this, 'show_seller_code']);

        // 加入posts 头部"筛选"新的项目 渲染
        // add_action('restrict_manage_posts', [$this, 'render_woo_orders_filters'], 10, 1);


        // 在头部操作栏添加新的form 执行新的更新（posts）
//        add_action('manage_posts_extra_tablenav', [$this, 'render_manage_posts_extra_tablenav']);

        // 在头部操作栏添加新的form 执行新的更新 （users）
//        add_action('manage_users_extra_tablenav', [$this, 'render_manage_users_extra_tablenav']);
        // 处理自定义 from 提交
//        add_action('request', [$this, 'handle_customer_move_to_request']);

        // 在获取users 前处理 一般用于列表展示前，比如filter 的功能
//        add_action('pre_get_users', [$this, 'action_customers_move_to_seller'], 99, 1);

        // bulk action user
//        add_filter('bulk_actions-users', [$this, 'register_users_move_to_bulk_actions']);


        // 加一个所属销售的栏位
//        add_filter('manage_users_custom_column', [$this, 'new_modify_user_table_row'], 10, 3);

    }


    function register_users_move_to_bulk_actions($bulk_actions)
    {
        $bulk_actions['changeto'] = __('Customers change to', 'customers_change_to');
        return $bulk_actions;
    }

    function action_customers_move_to_seller($query)
    {

        if (!is_admin()) {
            return;
        }

        $user = wp_get_current_user();
        $roles = $user->roles;
        if (!in_array('administrator', $roles)) {
            return;
        }

        global $pagenow;

        if ('users.php' === $pagenow) {
            print_r($query);
            if (isset($_GET['role']) && $_GET['role'] === 'customer') {

            }
        }

    }

    function render_manage_users_extra_tablenav($switch)
    {
        global $pagenow;
        if (!is_admin()) {
            return;
        }
        $user = wp_get_current_user();
        $roles = $user->roles;
        if (!in_array('administrator', $roles)) {
            return;
        }

        if ('users.php' === $pagenow) {
            $arg = array('number' => -1, 'role' => 'seller');
            $sellers = get_users($arg);
            ?>
            <input type="hidden" name="action" value="moveto">
            <select name="to_seller">
                <option value="0">请指定业务员</option>
                <?php foreach ($sellers as $item) {
                    $seller = new WP_User($item); ?>
                    <option value="<?php echo esc_attr($seller->ID); ?>"><?php echo esc_attr($seller->last_name . $seller->first_name); ?></option>
                <?php } ?>
            </select>
            <input type="submit" class="button" value="Move To"/>
            <?php

        }
    }

    function handle_customer_move_to_request($request)
    {
        global $pagenow;
        global $typenow;
        if (!is_admin()) {
            return;
        }
        $user = wp_get_current_user();
        $roles = $user->roles;
        if (!in_array('administrator', $roles)) {
            return;
        }
        if ('edit.php' === $pagenow && 'shop_order' === $typenow) {

        }
        return $request;
    }

    function render_manage_posts_extra_tablenav()
    {
        global $pagenow;
        global $typenow;
        if (!is_admin()) {
            return;
        }
        $user = wp_get_current_user();
        $roles = $user->roles;
        if (!in_array('administrator', $roles)) {
            return;
        }
        if ('edit.php' === $pagenow && 'shop_order' === $typenow) {
            $arg = array('number' => -1, 'role' => 'seller');
            $sellers = get_users($arg);
            ?>
            <select name="to_seller">
                <option value="0">请指定业务员</option>
                <?php foreach ($sellers as $item) {
                    $seller = new WP_User($item); ?>
                    <option value="<?php echo esc_attr($seller->ID); ?>"><?php echo esc_attr($seller->last_name . $seller->first_name); ?></option>
                <?php } ?>
            </select>
            <input type="submit" class="button" value="Move To"/>
            <?php
        }
    }

    function render_woo_orders_filters($post_type)
    {
        global $pagenow;
        if (!is_admin()) {
            return;
        }

        if ('edit.php' === $pagenow && 'shop_order' === $post_type) {
            ?>
            <select name="coupon">
                <option value="0">All coupons</option>
            </select>
            <input type="button" class="button" value="Move To"/>
            <?php
        }
    }

    function handle_woocommerce_before_checkout_form_action($checkout)
    {
        $userid = get_current_user_id();
        if ($userid == 0) {
            wp_redirect("/my-account-2");
        }
    }

    function ui_wc_login_redirect($url, $user)
    {
        // url 会传一个order id, 登录后就会跳转到对应页面
//        if ($user->ID == 1) {
//            $url = add_query_arg('user', 'admin', $url);
//        } else {
//            $url = add_query_arg('user', 'notadmin', $url);
//        }
//        return $url;
    }

    function show_seller_code($data)
    {
        $data['seller_code'] = "邀请码";
        return $data;
    }


    function add_sellers_code_field_to_checkout_from($checkout)
    {
        $current_user_id = get_current_user_id();
        if ($current_user_id != 0) {
            $seller_code = get_user_meta($current_user_id, 'seller_code');
            echo '<div style="display: block;">';
            woocommerce_form_field('seller_code', array(
                'type' => 'text',
                'default' => $seller_code[0],
                'class' => array(''),
                'label' => __('Fill in this field'),
                'placeholder' => __('Enter something'),
            ), $checkout->get_value($seller_code[0]));
            echo '</div>';
        }
    }

    function seller_code_checkout_field_process()
    {
        // Check if set, if its not set add an error.
        if (!$_POST['seller_code'])
            wc_add_notice(__('you have not seller code'), 'error');
    }

    function seller_code_checkout_field_update_order_meta($order_id)
    {
        if (!empty($_POST['seller_code'])) {
            update_post_meta($order_id, 'seller_code', sanitize_text_field($_POST['seller_code']));
        }
    }

    function orders_filter_by_seller_code($where)
    {
        global $pagenow;
        global $typenow;
        global $wpdb;
        $user = wp_get_current_user();
        $roles = $user->roles;

        $seller_code = get_user_meta(get_current_user_id(), 'seller_code', true);

        $sql = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'seller_code' AND meta_value='$seller_code'";
        $order_list_ids = $wpdb->get_results($sql);
        $order_ids = [];
        foreach ($order_list_ids as $order) {
            $order_ids[] = $order->post_id;
        }
        // This condition allows us to make sure that we're modifying a query that fires on the wp-admin/edit.php?post_type=shop_order page
        if ('edit.php' === $pagenow && 'shop_order' === $typenow) {
            if (in_array('seller', $roles)) {
                $where .= "AND ID IN ('" . implode(",", $order_ids) . "')";
            }
        }
        return $where;
    }

    function seller_code_reg_url_profile_fields($user)
    {
        if (!is_admin()) {
            return;
        }

        $seller_code = get_user_meta(get_current_user_id(), 'seller_code', true);
        $url = get_site_url() . '/views/index.html?code=' . $seller_code;
        ?>
        <h3>邀请信息</h3>
        <table class="form-table">
            <tr>
                <th><label>邀请链接</label></th>
                <td>
                    <?php echo $url; ?>
                </td>
            </tr>
        </table>
        <?php
    }


//    function filter_insert_user_data($data) {
////        $role = $data['roles'];
//        $args  = array(
//            'role' => 'seller'
//        );
//        $seller = get_users($args);
//        print_r($seller);
//        exit();
//
//        $code = $this->sellerController->gen_seller_code();
//        $data['meta_input']=array("seller_code"=>$code);
//        print_r($data);
//        exit();
//    }

    function create_seller_code($user_id)
    {
        $code = $this->sellerController->gen_seller_code();
        add_user_meta($user_id, 'seller_code', $code);
    }


//    function filter_woocommerce_orders_in_the_table($query)
//    {
//        // This condition allows us to make sure that we won't modify any query that came from the frontend
//        if (!is_admin()) {
//            return;
//        }
//        $user = wp_get_current_user();
//        $roles = $user->roles;
//        if (in_array('seller', $roles) || in_array('administrator', $roles)) {
//            global $pagenow;
//            global $typenow;
//            // This condition allows us to make sure that we're modifying a query that fires on the wp-admin/edit.php?post_type=shop_order page
//            if ('edit.php' === $pagenow && 'shop_order' === $typenow) {
//                // Our filtering logic goes here
//                // 首先要获取当前用户的seller code
//                // 然后再用seller 做对应的order query
//                $current_user_id = get_current_user_id();
//                $seller_code = get_user_meta($current_user_id, 'seller_code');
//                if ($seller_code) {
//                    $filter_args = array(
//                        'limit' => 1,
//                        'orderby' => 'date',
//                        'order' => 'DESC'
//                    );
//                    $query = new WC_Order_Query($filter_args);
//                    $orders = $query->get_orders();
//                    return $orders;
//                }
//            }
//        }
//    }
}