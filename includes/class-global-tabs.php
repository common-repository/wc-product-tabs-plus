<?php
namespace WPTP;

/**
 * Class GlobalTabs
 *
 * @since 1.0.0
 */
class GlobalTabs {

    /**
     * post type slug
     * @var string
     */
    private static $post_type = 'wptp-global';

    /**
     * Initiliaze
     */
    public static function init() {
        // Add Plugin Menu
        add_action( 'admin_menu', array(__CLASS__,'sd_register_top_level_menu'));

        add_action('init', array(__CLASS__, 'register_post_type'));

        add_action('admin_menu', array(__CLASS__, 'add_submenu'));

        add_action( 'wptp_settings_tab', array(__CLASS__, 'general_tab'), 1);

        //add_action( 'wptp_settings_tab', array(__CLASS__, 'customization_tab'), 2);

        add_action( 'wptp_settings_content', array(__CLASS__, 'general_render_page'));

        add_action( 'wptp_settings_content', array(__CLASS__, 'customization_render_page'));

        // Save Field ID for current tab
        add_action('save_post_' . self::$post_type, array(__CLASS__, 'save_post'), 10, 3);

        // Render Field ID to the publish metabox
        add_action('post_submitbox_misc_actions', array(__CLASS__, 'minor_actions'));

        // Add Help Tab
        add_action('current_screen', array(__CLASS__, 'add_tabs'));

        add_action( 'admin_post_nopriv_wptp_general_save', array(__CLASS__,'wptp_general_save') );

        add_action( 'admin_post_wptp_general_save', array(__CLASS__,'wptp_general_save') );

        add_action( 'admin_notices', [__CLASS__, 'notice_settings_saved'], 50 );

    }

  

   
    public static function sd_register_top_level_menu(){
        add_menu_page(
            'WC Product Tabs',
            'WC Product Tabs',
            'manage_options',
            'wc-product-tabs',
            'sd_display_top_level_menu_page',
            '',
            6
        );
    }

    /**
     * Register post type
     */
    public static function register_post_type() {
        $labels = array(
            'name'               => _x('Global Product Tabs', 'post type general name', 'wptp'),
            'singular_name'      => _x('Global Product Tab', 'post type singular name', 'wptp'),
            'menu_name'          => _x('Global Product Tabs', 'admin menu', 'wptp'),
            'name_admin_bar'     => _x('Global Product Tab', 'add new on admin bar', 'wptp'),
            'add_new'            => _x('Add New', 'book', 'wptp'),
            'add_new_item'       => __('Add New Global Product Tab', 'wptp'),
            'new_item'           => __('New Global Product Tab', 'wptp'),
            'edit_item'          => __('Edit Global Product Tab', 'wptp'),
            'view_item'          => __('View Global Product Tab', 'wptp'),
            'all_items'          => __('Global Product Tabs', 'wptp'),
            'search_items'       => __('Search Global Product Tabs', 'wptp'),
            'parent_item_colon'  => __('Parent Global Product Tabs:', 'wptp'),
            'not_found'          => __('No Global Product Tabs found.', 'wptp'),
            'not_found_in_trash' => __('No Global Product Tabs found in Trash.', 'wptp')
        );

        register_post_type(self::$post_type, array(
            'labels' => $labels,
            'description' => __('This is where you can add global tabs.', 'wptp'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title', 'editor'),
            'show_in_menu' => 'wc-product-tabs',
            'show_in_nav_menus'   => true
        ));
    }

    public static function add_submenu() {
        add_submenu_page( 'wc-product-tabs', 'Tab Settings', 'Tab Settings',
            'manage_options', 'wctp-tab-settings',array(__CLASS__, 'submenu_page_callback'));
    }

    public static function submenu_page_callback() {
        global $sd_active_tab;
        $sd_active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general'; ?>
        <div class="wrap">
		    <div id="wrap" class="wptp-settings-wrapper">
			    <?php settings_errors(); ?>
                <div id="icon-options-general" class="icon32"></div>
                <h1><?php echo __( 'WC Product Tabs Plus', 'wptp' ); ?></h1>
                <div class="nav-tab-wrapper">
                    <?php do_action( 'wptp_settings_tab' ); ?>
                </div>
                <?php do_action( 'wptp_settings_content' );?>
            </div>
        </div>
        <?php
    }

    public static function general_tab(){
        global $sd_active_tab; ?>
        <a class="nav-tab <?php echo $sd_active_tab == 'general' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=tab-settings&tab=general' ); ?>"><?php _e( 'General', 'sd' ); ?> </a>
        <?php
    }

    public static function customization_tab(){
        global $sd_active_tab; ?>
        <a class="nav-tab <?php echo $sd_active_tab == 'customization' || '' ? 'nav-tab-active' : ''; ?>" href="<?php echo admin_url( 'admin.php?page=tab-settings&tab=customization' ); ?>"><?php _e( 'Customization', 'sd' ); ?> </a>
        <?php
    }

    public static function general_render_page() {
        global $sd_active_tab, $wpdb;
        if ( '' || 'general' != $sd_active_tab )
            return;

        $options = [];
        $product_cats = [];
        $product_tags = [];

        $all_product_data = $wpdb->get_results("SELECT ID,post_title,post_content,post_author,post_date_gmt FROM `" . $wpdb->prefix . "posts` WHERE post_type='product' AND post_status = 'publish'");
        $all_cats = $wpdb->get_results("SELECT t.term_id,t.name FROM `" . $wpdb->prefix . "term_taxonomy` as tt INNER JOIN `" . $wpdb->prefix . "terms` as t ON tt.term_id=t.term_id WHERE tt.taxonomy='product_cat'");
        $all_tags = $wpdb->get_results("SELECT t.term_id,t.name FROM `" . $wpdb->prefix . "term_taxonomy` as tt INNER JOIN `" . $wpdb->prefix . "terms` as t ON tt.term_id=t.term_id WHERE tt.taxonomy='product_tag'");
        
        $tab_type_option = get_option('wptp_tab_type');
        $tab_type_value = get_option('wptp_tab_type_value');
        $duration_option = get_option('wptp_tab_duration');
        
        foreach($all_product_data as $product_data):
            if(isset($tab_type_value) && is_array($tab_type_value) && in_array($product_data->post_title,$tab_type_value)) {
                $options[] = '<option value="'.$product_data->post_title.'" selected="selected">' . $product_data->post_title . '</option>';
            }
            else{
                $options[] = '<option value="'.$product_data->post_title.'">' . $product_data->post_title . '</option>';
            }
        endforeach;

        foreach($all_cats as $all_cat):
            if(isset($tab_type_value) && is_array($tab_type_value) &&  in_array($all_cat->term_id,$tab_type_value)) {
                $product_cats[] = '<option value="'.$all_cat->term_id.'" selected="selected">'.$all_cat->name.'</option>';
            }
            else{
                $product_cats[] = '<option value="'.$all_cat->term_id.'">'.$all_cat->name.'</option>';
            }
        endforeach;

    foreach($all_tags as $all_tag):
        
            if( isset($tab_type_value) && is_array($tab_type_value) && in_array($all_tag->term_id,$tab_type_value)) {
                $product_tags[] = '<option value="'.$all_tag->term_id.'" selected="selected">'.$all_tag->name.'</option>';
            }
            else{
                $product_tags[] = '<option value="'.$all_tag->term_id.'">'.$all_tag->name.'</option>';
            }
        
    endforeach;



        ?>

        <div id="wc-product-tabs-plus">
            <form name="wptp_settings_form" method="POST" action="<?php echo esc_attr( admin_url('admin-post.php') ); ?>">
                <input type="hidden" name="action" value="wptp_general_save">
                <table class="form-table">
                    <tr>
                        <th><label><?php _e('Display product tab(s)'); ?></label></th>
                        <td>
                            <label>
                                <input type="radio" id="display-none-product-tab-radio" name="wptp_tab_type" value="hide" <?php checked(true, $tab_type_option === 'hide', true); ?> />
                                <strong><?php _e( 'None', 'wptp' ) ?></strong>
                            </label><br>

                            <label>
                                <input type="radio" id="display-all-product-tab-radio" name="wptp_tab_type" value="all" <?php echo (($tab_type_option=="all")?'checked="checked"':''); ?> />
                                <strong><?php _e( 'All', 'wptp' ) ?></strong>
                            </label><br>

                            <label>
                                <input type="radio" id="display-specific-products-product-tab-radio" name="wptp_tab_type" value="specific_products" <?php echo (($tab_type_option=="specific_products")?'checked="checked"':''); ?> />
                                <strong><?php _e( 'Specific Product(s)', 'wptp' ) ?></strong>
                            </label><br>

                            <label>
                                <input type="radio" id="display-specific-categories-product-tab-radio" name="wptp_tab_type" value="specific_categories" <?php echo (($tab_type_option=="specific_categories")?'checked="checked"':''); ?> />
                                <strong><?php _e( 'Specific Category(ies)', 'wptp' ) ?></strong>
                            </label><br>

                            <label>
                                <input type="radio" id="display-specific-tags-tab-radio" name="wptp_tab_type" value="specific_tags" <?php echo (($tab_type_option=="specific_tags")?'checked="checked"':''); ?> />
                                <strong><?php _e( 'Specific Tag(s)', 'wptp' ) ?></strong>
                            </label><br><br>

                            <p class="description"><?php _e('Select where to show product tabs on.','wptp'); ?></p>
                        </td>
                    </tr>
                    <tr id="if-show-on-products" <?php echo ($tab_type_option != 'specific_products') ? 'style="display:none;"' : ''; ?>>
                        <th><label><?php _e('Select product(s)'); ?></label></th>
                        <td><select id="show_on_specific_products_select" name="show_on_specific_products_select[]" multiple="multiple" style="width: 100%" data-placeholder="<?php echo sprintf(__('Type %s title to search', 'wptp'), 'product'); ?>" >
		                        <?php foreach($all_product_data as $product_data) {
		                                $selected_product = (isset($tab_type_value) && is_array($tab_type_value) && in_array($product_data->post_title,$tab_type_value)); ?>
		                                <option value="<?php echo $product_data->post_title; ?>" <?php selected(true, $selected_product, true); ?>><?php echo $product_data->post_title; ?></option>
                                <?php } ?>
                            </select>
                            <p class="description"><?php _e('Show tabs on selected products only.','wptp'); ?></p>
                        </td>
                    </tr>
                    <tr id="if-show-on-categories" <?php echo ($tab_type_option != 'specific_categories') ? 'style="display:none;"' : ''; ?>>
                        <th><label><?php _e('Select product category(ies)'); ?></label></th>
                        <td><select id="show_on_categories_select" name="show_on_categories_select[]" multiple="multiple" style="width: 100%" data-placeholder="<?php echo sprintf(__('Type %s category to search', 'wptp'), 'product'); ?>" >
		                        <?php foreach($all_cats as $all_cat){
		                                $selected_category = (isset($tab_type_value) && is_array($tab_type_value) &&  in_array($all_cat->term_id,$tab_type_value)); ?>
		                                <option value="<?php echo $all_cat->term_id; ?>" <?php selected(true, $selected_category, true); ?>><?php echo $all_cat->name; ?></option>
                                <?php } ?>
                            </select>
                            <p class="description"><?php _e('Show tabs on selected products categories only.','wptp'); ?></p>
                        </td>
                    </tr>

                    <tr id="if-show-on-tags" <?php echo ($tab_type_option != 'specific_tags') ? 'style="display:none;"' : ''; ?>>
                        <th><label><?php _e('Select product tag(s)'); ?></label></th>
                        <td><select id="show_on_tags_select" name="show_on_tags_select[]" multiple="multiple" style="width: 100%" data-placeholder="<?php echo sprintf(__('Type %s tags to search', 'wptp'), 'product'); ?>" >
		                        <?php foreach($all_tags as $all_tag){
                                    if( $tab_type_value != '' )
                                    {

                                        $selected_tag = (in_array($all_tag->term_id,$tab_type_value)); 
                                    }else{
                                        $selected_tag = '';
                                    }
                                        ?>
                                    
		                                <option value="<?php echo $all_tag->term_id; ?>" 
                                            <?php selected(true, $selected_tag, true); ?>>
                                            <?php echo $all_tag->name; ?>
                                        </option>
                                <?php } ?>
                            </select>
                            <p class="description"><?php _e('Show tabs on selected products tags only.','wptp'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label><?php _e( 'Show for specific duration', 'wptp' ) ?></label></th>
                        <td>
                            <input type="checkbox" id="show-for-duration" name="wptp_show_duration" value="show" <?php checked(true, !empty($duration_option), true); ?>>
                            <table style="display:<?php echo ((!empty($duration_option))?'block':'none'); ?>" class="form-table if-show-for-duration">
                                <tr >
                                    <td><label for="show"><strong><?php _e( 'Show From', 'wptp' ) ?></strong>: </label></td>
                                    <td><input type="date" name="wptp_show_duration_from" value="<?php echo $duration_option['wptp_duration_from']??''; ?>" id="wptp_show_duration_from"></td>
                                </tr>
                                <tr>
                                    <td><label for="show"><strong><?php _e( 'Show To', 'wptp' ) ?></strong>:</label></td>
                                    <td><input type="date" name="wptp_show_duration_to" value="<?php echo $duration_option['wptp_duration_to']??''; ?>" id="wptp_show_duration_to"></td>
                                </tr>
                            </table>
                            <p class="description"><?php _e('If selected, show tab for specific duration only.','wptp'); ?></p>
                        </td>
                    </tr>

                </table>
                <p class="submit">
	                <?php submit_button(__('Save Settings', 'wptp')); ?>
                </p>
	            <?php wp_nonce_field( 'wptp_settings_general', 'wptp_settings_general_field' ); ?>
            </form>
        </div>
        <?php
    }

	public static function notice_settings_saved() {

		if( !isset($_GET['wptp_settings_saved']) || absint($_GET['wptp_settings_saved']) !== 1 ) return;

		?>
        <div class="notice notice-success is-dismissible">
            <p><strong><?php _e( 'Settings saved.' ); ?></strong></p>
        </div>
		<?php
	}

    public static function wptp_general_save() {

	    if ( isset($_POST['wptp_settings_general_field']) && check_admin_referer('wptp_settings_general','wptp_settings_general_field') ) {
            $wptp_tab_type      = ( isset( $_POST['wptp_tab_type'] ) ? sanitize_text_field( $_POST['wptp_tab_type'] ) : '' );
		    $wptp_show_duration = ( isset( $_POST['wptp_show_duration'] ) ? sanitize_text_field( $_POST['wptp_show_duration'] ) : '' );

		    $show_on_specific_products_select = ( isset( $_POST['show_on_specific_products_select'] ) ? $_POST['show_on_specific_products_select'] : '' );
		    $show_on_categories_select        = ( isset( $_POST['show_on_categories_select'] ) ? $_POST['show_on_categories_select'] : '' );
		    $show_on_tags_select              = ( isset( $_POST['show_on_tags_select'] ) ? $_POST['show_on_tags_select'] : '' );

		    $show_value = '';
		    if ( $wptp_tab_type == "specific_products" ) {
			    $show_value = $show_on_specific_products_select;
		    }

		    if ( $wptp_tab_type == "specific_categories" ) {
			    $show_value = $show_on_categories_select;
		    }

		    if ( $wptp_tab_type == "specific_tags" ) {
			    $show_value = $show_on_tags_select;
		    }

		    if ( ! empty( $wptp_tab_type ) ) {
			    if ( get_option( 'wptp_tab_type' ) !== false ) {
				    update_option( 'wptp_tab_type', $wptp_tab_type );
				    update_option( 'wptp_tab_type_value', $show_value );

			    } else {
				    $deprecated = null;
				    $autoload   = 'no';
				    add_option( 'wptp_tab_type', $wptp_tab_type, $deprecated, $autoload );
				    add_option( 'wptp_tab_type_value', $show_value, $deprecated, $autoload );
			    }
		    }

		    if ( ! empty( $wptp_show_duration ) ) {
			    $wptp_show_duration_from = ( isset( $_POST['wptp_show_duration_from'] ) ? sanitize_text_field( $_POST['wptp_show_duration_from'] ) : '' );
			    $wptp_show_duration_to   = ( isset( $_POST['wptp_show_duration_to'] ) ? sanitize_text_field( $_POST['wptp_show_duration_to'] ) : '' );

			    $duration_array = array(
				    'wptp_duration_from' => $wptp_show_duration_from,
				    'wptp_duration_to'   => $wptp_show_duration_to,
			    );
			    if ( get_option( 'wptp_tab_duration' ) !== false ) {
				    update_option( 'wptp_tab_duration', $duration_array );

			    } else {
				    $deprecated = null;
				    $autoload   = 'no';
				    add_option( 'wptp_tab_duration', $duration_array, $deprecated, $autoload );
			    }


		    } else {
			    if ( get_option( 'wptp_tab_duration' ) !== false ) {
				    delete_option( 'wptp_tab_duration' );
			    }
		    }
	    }

	    $site_url = add_query_arg( 'wptp_settings_saved', '1', site_url( 'wp-admin/admin.php?page=wctp-tab-settings' ) );
	    wp_redirect( $site_url );
	    die;
    }

    public static function customization_render_page() {
        global $sd_active_tab;
        if ( '' || 'customization' != $sd_active_tab )
            return;
        ?>

        <h3><?php _e( 'Customization', 'sd' ); ?></h3>
        <!-- Put your content here -->
        <?php
    }

    /**
     * Get post type for Global tabs
     * @return string
     */
    public static function get_posttype() {
        return self::$post_type;
    }

    /**
     * Hooked into save_post_{$post_type}
     * @param $post_id
     * @return void
     */
    public static function save_post($post_id, $post, $update) {
        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Don't save revisions and autosaves
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if (!$update) return;

        // Check if the field id is not save already
        if (!get_post_meta($post_id, '_wptp_field', true)) {
            update_post_meta($post_id, '_wptp_field', uniqid('wptp'));
        }
    }

    public static function minor_actions() {
        global $post;

        if ($post->post_type !== self::$post_type) return;

        $fieldID = get_post_meta($post->ID, '_wptp_field', true);

        echo '<div class="misc-pub-section misc-pub-post-uniqid">Field ID: <span id="post-status-display">' . ($fieldID ? $fieldID : 'N/A') . '</span>';
    }

    public static function add_tabs() {
        $screen = get_current_screen();

        if ( ! $screen || ! in_array( $screen->id, array('edit-wptp-global', 'wptp-global') ) ) {
            return;
        }

        $screen->add_help_tab(array(
            'id' => 'wptp-global-tabs',
            'title' => 'Global Tabs',
            'content' =>
                '<h2>' . __('Global Tabs', 'wptp') . '</h2>' .
                '<p>' . __('Global tabs are reusable tabs which will be displayed under every product. Let’s say you add a global tab named “Product Details”, now that tab will be displayed under each products edit screen and also on single product page.', 'wptp') . '</p>' .
                '<p>' . __('You can identify Global Tabs on Product edit screen by its non editable title and the edit (pencil) icon. Clicking on it, will redirect you to Global Tab edit screen.', 'wptp') . '</p>' .
                '<p>' . sprintf('<strong>%s</strong> %s', __('Note: ', 'wptp'), __('New Global Tabs are automatically appended to the list of tabs in the individual Products i.e. will always appear after all the tabs you have added to the Product.', 'wptp') . '</p>') .
                '<h3>' . __('Warning!', 'wptp') . '</h3>' .
                '<p>' . __('Please do not clone/draft a global tab from the Global Tabs table listing', 'wptp') . '</p>' .
                '<p>' . __('When you clone, the unique field ID of the tab remains same, which fails to list global tabs on product edit screen. To make sure new (unique) Field ID is generated, always use “Add new” button to create new tabs.', 'wptp') . '</p>'
        ));

        $screen->add_help_tab(array(
            'id' => 'wptp-action-hooks',
            'title' => 'Action/Filter Hooks',
            'content' =>
                '<h2>' . __('Action/Filter Hooks', 'wptp') . '</h2>' .
                '<p>' . __('Action/Filter hooks are provided to allow any plugin or theme to change content output. You can customize the rendered HTML for title/contents to be displayed on the front end, for all or on a per tab basis.', 'wptp') . '</p>' .
                '<h3>' . __('Per Tab', 'wptp') . '</h3>' .
                "<p><pre>
                add_action('wptp_tab_{\$field_ID}', function(\$product) {
                    // do something with \$product-> ....
                });</pre></p>" .

                '<p>' . __('See Field ID help tab to read how to fetch "$field_ID" for selective tabs.', 'wptp') . '</p>' .

                '<h3>' . __('All Tabs', 'wptp') . '</h3>' .
                "<p><pre>
                add_action('wptp_tab', function(\$product) {
                    // do something with \$product-> ....
                });</pre></p>" .

                '<h3>' . __('Modify Tab object', 'wptp') . '</h3>' .
                "<p><pre>
                add_filter('wptp_tab_object', function(\$product_tab) {
                    // do something with \$product_tab-> ....
                    return \$product_tab;
                });</pre></p>"
        ));

        $screen->add_help_tab(array(
            'id' => 'wptp-field-id',
            'title' => 'Field ID',
            'content' =>
                '<h2>' . __('Field ID', 'wptp') . '</h2>' .
                '<p>' . __('Field id is a unique identification for tabs added both globally and those added within the product. It is used to add hook for single selective tab.', 'wptp') . '</p>' .
                '<h3>' . __('Get Field ID', 'wptp') . '</h3>' .
                '<p>' . __('To find the field id for a Global Tab, edit that Global Tab and, while still on Global Tab edit screen, you can see the "Field ID" in the Publish metabox on far right as shown below.', 'wptp') . '</p>'
        ));
    }
}
