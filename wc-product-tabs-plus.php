<?php
/**
 * Plugin Name:       WC Product Tabs Plus
 * Plugin URI:        https://wordpress.org/plugins/wc-product-tabs-plus/
 * Description:       Advance tab management for WooCommerce tabs on single product page
 * Requires at least: 5.1
 * Requires PHP:      7.2
 * Version:           1.1.1
 * Author:            WooNinjas
 * Author URI:        https://wooninjas.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wptp
 * Domain Path:       /languages
 */

namespace WPTP;

if (!defined("ABSPATH")) exit;

// Directory
define('WPTP\DIR', plugin_dir_path(__FILE__));
define('WPTP\DIR_FILE', DIR . basename(__FILE__));
define('WPTP\INCLUDES_DIR', trailingslashit(DIR . 'includes'));
define('WPTP\TEMPLATES', trailingslashit(DIR . 'templates'));

// URLS
define('WPTP\URL', trailingslashit(plugins_url('', __FILE__)));
define('WPTP\ASSETS_URL', trailingslashit(URL . 'assets'));

// Load WC dependency class
if (!class_exists('WC_Dependencies')) {
    require_once DIR . 'woo-includes/class-wc-dependencies.php';
};

// Check if WooCommerce active
// if (!WPTP\WC_Dependencies::woocommerce_active_check()) {
//     return;
// }


//Loading files
require_once INCLUDES_DIR . 'class-global-tabs.php';
require_once INCLUDES_DIR . 'class-product-data.php';
require_once INCLUDES_DIR . 'class-tabs.php';
require_once INCLUDES_DIR . 'functions.php';

/**
 * Class Main for plugin initiation
 *
 * @since 1.0
 */
final class Main
{
    public static $version = '1.1.1';

    // Main instance
    protected static $_instance = null;

    protected function __construct() {
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
        // Upgrade
        add_action('plugins_loaded', array($this, 'upgrade'));

        GlobalTabs::init();
        ProductData::init();

        // Adding settings tab
        add_filter('plugin_action_links_' . plugin_basename(DIR_FILE), function($links) {
            return array_merge($links, array(
                sprintf(
                    '<a href="%s">Global tabs</a>',
                    admin_url('edit.php?post_type='.GlobalTabs::get_posttype())
                ),
            ));
        });

        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // Render product tabs on Single product
        add_filter('woocommerce_product_tabs', array($this, 'render_frontend'));

        add_filter('wp_ajax_wptip_delete_tab_data', array($this, 'wptip_delete_tab_data'));
        add_filter('wp_ajax_nopriv_wptip_delete_tab_data', array($this, 'wptip_delete_tab_data'));
        
    }
    
    function wptip_delete_tab_data(){
        $post_id = $_POST['post'];
        if ( isset( $post_id ) && !empty( $post_id ) ){
            delete_post_meta( $post_id, '_wptp' );
        }
        wp_send_json_success(
            array('DATA'=> 'DELETED')
    );
    }
    /**
     * @return $this
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Activation function hook
     *
     * @return void
     */
    public static function activation() {
        if (!current_user_can('activate_plugins'))
            return;

        update_option('wptp_version', self::$version);
    }

    /**
     * Deactivation function hook
     * No used in this plugin
     *
     * @return void
     */
    public static function deactivation() {}

    public static function upgrade() {
        if (get_option('wptp_version') != self::$version) {
            wptp_upgrade();
        }
    }

    /**
     * Enqueue scripts on admin
     */
    public static function admin_enqueue_scripts() {
        global $post_type;
        $screens = array('product');

        $deps = array(
            'jquery',
            'jquery-ui-core',
            'backbone',
            'editor'
        );

        if( isset($_GET['page']) && $_GET['page'] == 'wctp-tab-settings' ){
            wp_enqueue_style('wptp-css', ASSETS_URL . 'css/wptp.css', array(), time());
            wp_enqueue_script('wptp-tabs-js', ASSETS_URL . 'js/tabs.js', $deps, time(), true);
           
            wp_enqueue_style('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css' );
            wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
        }

        if (!in_array($post_type, $screens)) return;

        wp_enqueue_script('wptp-js', ASSETS_URL . 'js/wptp.js', $deps, time(), true);

    }

    /**
     * Render tabs on front
     * @param $tabs array
     * @return mixed
     */
    public static function render_frontend($tabs)
    {
        global $product, $wpdb;
        $product_id = $product->get_id();

        $term_taxanomy = $wpdb->get_results('SELECT * FROM `' . $wpdb->prefix . 'term_relationships` WHERE object_id = ' . $product_id);

        $tab_type_option = get_option('wptp_tab_type');
        $tab_type_value = get_option('wptp_tab_type_value');
        $duration_option = get_option('wptp_tab_duration');
        $today = date("Y-m-d");

        if(!empty($duration_option)) {
            if (($today < $duration_option['wptp_duration_from']) || ($today > $duration_option['wptp_duration_to'])) {
                return;
            }
        }

        if ($tab_type_option == "hide") {
            return;
        } else {
            if ($tab_type_option == "all") {
                return self::tabs_func();
            }

            if ($tab_type_option == "specific_products") {
                if (!empty($tab_type_value)) {
                    $product_name = $product->get_name();
                    foreach( $tab_type_value as $tab_type_valuess ){
                        if ($product_name == $tab_type_valuess ) {
                            
                            return self::tabs_func();
                        }
                    }
                }
            }

            if ($tab_type_option == "specific_categories") {
                if (!empty($term_taxanomy)) {
                    foreach ($term_taxanomy as $taxonomy) {
                        if (!empty($tab_type_value)) {
                            foreach( $tab_type_value as $tab_type_valuess ){
                                if ( $taxonomy->term_taxonomy_id == $tab_type_valuess ) {
                                    return self::tabs_func();
                                }
                            }
                        }
                    }
                }
            }

            if ($tab_type_option == "specific_tags") {
                if (!empty($term_taxanomy)) {
                    foreach ($term_taxanomy as $taxonomy) {
                        if (!empty($tab_type_value)) {
                            foreach( $tab_type_value as $tab_type_valuess ){
                                if ( $taxonomy->term_taxonomy_id == $tab_type_valuess ) {
                                    return self::tabs_func();
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public static function tabs_func()
    {
        $product_tabs = wptp_get_all_tabs();
        $i = 1;
        foreach ($product_tabs as $product_tab) {
            if ($product_tab->hide || empty($product_tab->title)) continue;

            $tab_properties = array(
                'title' 	=> $product_tab->title,
                'priority' 	=> 50 + $i,
                'callback' 	=> function() use ($product_tab) {
                    /**
                     * Tab specific
                     * @since 1.0.1
                     */
                    do_action( "wptp_tab_{$product_tab->fieldID}", $product_tab );

                    /**
                     * Global tab
                     * @since 1.0.1
                     */
                    do_action( "wptp_tab", $product_tab );

                    /**
                     * Global tab object
                     * @since 1.0.3
                     */

                    $product_tab = apply_filters( 'wptp_tab_object', $product_tab );

                    if ( isset( $product_tab->title ) ) {
                        echo "<h2>{$product_tab->title}</h2>";
                    }

                    if ( isset( $product_tab->content ) ) {
                        echo $product_tab->content;
                    }
                }
            );
            $tabs[$product_tab->fieldID] = $tab_properties;
            $i++;
        }
        
        if( isset( $tabs ) ){
            return $tabs;
        }else{
            return;
        }
    }
}

/**
 * Main instance
 *
 * @return Main
 */
function WPTP() {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    
    if( !is_plugin_active( 'woocommerce/woocommerce.php' ) )
    {
        if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
            include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        
        add_action( "admin_notices", function(){
            unset($_GET['activate']);  //unset this to hide default Plugin activated. notice
            
                $class = 'notice notice-error is-dismissible';
                $message = sprintf( __( '%s requires <a href="https://woocommerce.com/">woocommerce</a> plugin to be activated.', 'wn-learndash-feedback-pro' ), 'wc-product-tabs-plus' );
                printf ( "<div id='message' class='%s'> <p>%s</p></div>", $class, $message );
        } );
        return;
    }else{

        return Main::instance();
    }
}

WPTP();
