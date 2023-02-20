<?php

/**
 * Plugin Name:       cantonhitwholesale
 * Description:       这是一个cantonhitwholesale的相关功能
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Version:           0.1.0
 * Author:            Albert
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cantonhitwholesale
 */

if (!defined('ABSPATH')) : exit(); endif; // No direct access allowed.

require_once 'vendor/autoload.php';

use CTHWP\Api\Api;
use CTHWP\Api\InstallAction;

final class Cantonhitwholesale_Backend
{
    /**
     * Define Plugin Version
     */
    const VERSION = '1.0.0';

    /**
     * Construct Function
     */
    public function __construct()
    {
        $this->index_page_slug = 'sellers';
        $this->plugin_constants();
        $this->plugin_domain = "cantonhitwholesale";
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        add_action('plugins_loaded', [$this, 'init_plugin']);
        $this->pages = [
            (object)[
                'title' => __('Sellers', $this->plugin_domain),
                'path' => $this->index_page_slug,
            ]
        ];
        $installAction = new InstallAction();
        $installAction->register_actions();

    }

    /**
     * Plugin Constants
     * @since 1.0.0
     */
    public function plugin_constants()
    {
        define('CTHWP_VERSION', self::VERSION);
        define('CTHWS_PATH', trailingslashit(plugin_dir_path(__FILE__)));
        define('CTHWS_URL', trailingslashit(plugins_url('/', __FILE__)));
        define('CTHWP_NONCE', 'b?le*;K7.T2jk_*(+3&[G[xAc8O~Fv)2T/Zk9N:GKBkn$piN0.N%N~X91VbCn@.4');
    }

    /**
     * Singletone Instance
     * @since 1.0.0
     */
    public static function init()
    {
        static $instance = false;

        if (!$instance) {
            $instance = new self();
        }

        return $instance;
    }

    /**
     * On Plugin Activation
     * @since 1.0.0
     */
    public function activate()
    {
        $is_installed = get_option('cthwp_is_installed');

        if (!$is_installed) {
            update_option('cthwp_is_installed', time());
        }

        update_option('cthwp_is_installed', CTHWP_VERSION);
    }

    /**
     * On Plugin De-actiavtion
     * @since 1.0.0
     */
    public function deactivate()
    {
        // On plugin deactivation
    }

    /**
     * Init Plugin
     * @since 1.0.0
     */
    public function init_plugin()
    {
        // init
        new Api();
    }

    public function init_plugin_ui_menu()
    {
        add_action('admin_menu', [$this, 'cantonhitwholesale_init_menu']);

        add_action('admin_enqueue_scripts', [$this, 'cantonhitwholesale_admin_enqueue_scripts']);
    }

    /**
     * Init Admin Menu.
     *
     * @return void
     */
    function cantonhitwholesale_init_menu(): void
    {
        $capability = 'manage_options';
        foreach ($this->pages as $index => $page) {
            if ($index === 0) {
                add_menu_page(__('店铺后台', $this->plugin_domain),
                    __('店铺后台', $this->plugin_domain),
                    $capability,
                    $page->path,
                    [$this, 'cantonhitwholesale_init_menu_admin_page'],
                    'dashicons-admin-post');
            }
        }

        if (current_user_can("cantonhitwholesale_seller")) {
            add_menu_page(__('你的订单', $this->plugin_domain),
                __('你的订单', 'cantonhitwholesale'),
                'manage_options',
                $page->path,
                [$this, 'cantonhitwholesale_init_menu_admin_page'],
                'dashicons-admin-post');
        }
    }

    /**
     * Init Admin Page.
     *
     * @return void
     */
    function cantonhitwholesale_init_menu_admin_page(): void
    {
        $user = wp_get_current_user();
        $roles = $user->roles;
        if (in_array('administrator',$roles)) {
            require_once plugin_dir_path(__FILE__) . 'templates/index.php';
        } else {
            echo "you can not access this page";
            exit();
        }

    }

    /**
     * Init Admin Page.
     *
     * @return void
     */
    function cantonhitwholesale_init_menu_seller_orders_page(): void
    {
        require_once plugin_dir_path(__FILE__) . 'templates/seller_orders.php';
    }

    /**
     * Enqueue scripts and styles.
     *
     * @return void
     */
    function cantonhitwholesale_admin_enqueue_scripts(): void
    {
        wp_enqueue_script('cantonhitwholesale-script', CTHWS_URL . 'frontenddist/index.js', ['jquery', 'wp-element'], wp_rand(), true);
        wp_localize_script('cantonhitwholesale-script', 'appLocalizer', [
            'apiUrl' => home_url('/wp-json'),
            'nonce' => wp_create_nonce('wp_rest'),
            'pages' => array_map(function ($page) {
                if ($page->path === $this->index_page_slug) {
                    return (object)[
                        'path' => '/',
                        'title' => $page->title,
                    ];
                }

//                if (Str::startsWith($page->path, 'demo#/')) {
//                    return (object) [
//                        'path' => Str::after($page->path, 'demo#'),
//                        'title' => $page->title,
//                    ];
//                }

                return null;
            }, $this->pages),
        ]);
    }
}

/**
 * Initialize Main Plugin
 * @since 1.0.0
 */
function wp_cantonhitwholesale_backend_start()
{
    return Cantonhitwholesale_Backend::init();
}

// Run the Plugin
$plugin_instance = wp_cantonhitwholesale_backend_start();

$plugin_instance->init_plugin_ui_menu();