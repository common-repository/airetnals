<?php

/**
 * Plugin Name: AIRentals plugin
 * Description: Plugin that import menu items and images from AIRentals
 * Version: 1.0
 * Author: Agendum team
 * Author URI: https://www.crorent.com/
 */

global $pagenow;

add_action('activated_plugin','save_error');

add_action('admin_menu', 'airentals_admin_add_page');

function airentals_admin_add_page() {
  add_options_page('AIRentals plugin', 'AIRentals plugin', 'manage_options', 'airentals_plugin', 'airentals_plugin_options_page'); //settings menu page
}

function airentals_plugin_section_text() {
    echo '<p>Here you can set all the options for plugin.</p>';
}

function airentals_plugin_setting_airentals_wp_code() {
    $options = get_option( 'airentals_plugin_options' );
    echo "<input id='airentals_plugin_setting_airentals_wp_code' name='airentals_plugin_options[airentals_wp_code]' type='text' value='".esc_attr( $options['airentals_wp_code'] )."' />";
	echo "<br /><span>Please enter WP code from AIRentals</span>";
}

function airentals_plugin_setting_property_id() {
    $options = get_option( 'airentals_plugin_options' );
    echo "<input id='airentals_plugin_setting_property_id' name='airentals_plugin_options[property_id]' type='text' value='".esc_attr( $options['property_id'] )."' />";
	echo "<br /><span>Please enter ID Property from AIRentals system - Sample (for HR-02470 enter 2470)</span>";
}

function airentals_plugin_setting_menu_item_name() {
    $options = get_option( 'airentals_plugin_options' );
    echo "<input id='airentals_plugin_setting_menu_item_name' name='airentals_plugin_options[menu_item_name]' type='text' value='".esc_attr( $options['menu_item_name'] )."' />";
}

function airentals_plugin_setting_results_limit() {
    $options = get_option( 'airentals_plugin_options' );
    echo "<input id='airentals_plugin_setting_results_limit' name='airentals_plugin_options[redni_broj_u_menu]' type='text' value='".esc_attr($options['redni_broj_u_menu'] )."' />";
}

function airentals_plugin_setting_id_parent_item() {
    $options = get_option( 'airentals_plugin_options' );
    echo "<input id='airentals_plugin_setting_id_parent_item' name='airentals_plugin_options[id_parent_item]' type='text' value='".esc_attr( $options['id_parent_item'] )."' />";
}

function airentals_register_settings() {
    register_setting( 'airentals_plugin_options', 'airentals_plugin_options', 'airentals_plugin_options_validate' );
    add_settings_section( 'airentals_plugin_settings', 'Plugin settings', 'airentals_plugin_section_text', 'airentals_plugin' );

	add_settings_field( 'airentals_plugin_setting_airentals_wp_code', 'AIRentals WP code', 'airentals_plugin_setting_airentals_wp_code', 'airentals_plugin', 'airentals_plugin_settings' );
    add_settings_field( 'airentals_plugin_setting_property_id', 'Property id', 'airentals_plugin_setting_property_id', 'airentals_plugin', 'airentals_plugin_settings' );
	add_settings_field( 'airentals_plugin_setting_menu_item_name', 'Menu item name', 'airentals_plugin_setting_menu_item_name', 'airentals_plugin', 'airentals_plugin_settings' );
    add_settings_field( 'airentals_plugin_setting_results_limit', 'Redni broj u menu', 'airentals_plugin_setting_results_limit', 'airentals_plugin', 'airentals_plugin_settings' );
    add_settings_field( 'airentals_plugin_setting_id_parent_item', 'ID parent item menu', 'airentals_plugin_setting_id_parent_item', 'airentals_plugin', 'airentals_plugin_settings', array('class'=>'hidden') );
}
add_action( 'admin_init', 'airentals_register_settings' );

function airentals_plugin_options_validate( $input ) {
    return $input;
}



// apply tags to attachments
function airentals_add_tags_to_attachments() {
    register_taxonomy_for_object_type( 'post_tag', 'attachment' );
}
add_action( 'init' , 'airentals_add_tags_to_attachments' );

add_filter('upload_dir', 'airentals_upload_dir'); 


function airentals_plugin_options_page() {
	
	$locale = get_locale();
	$ll = explode("_",$locale);
	$language = $ll[0];

	$plugin_options = get_option('airentals_plugin_options');
	
	// API for checking wp_key 
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'AAA_vP8RBsKjDUbNGmXg',
		)

	);
	$response = wp_remote_post( 'https://api.atrade.hr/v1/get_property_wp_key/'.$plugin_options['property_id'], $args );
	$property_wp_key = json_decode(wp_remote_retrieve_body( $response ), TRUE );

	echo "<h2>AIRentals - menu and image synchronization</h2><div>";
	
	// API for getting intro text
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'AAA_vP8RBsKjDUbNGmXg',
		)

	);
	$response = wp_remote_post( 'https://api.atrade.hr/v1/get_payment_template?language='.$language, $args );
	$intro_text = json_decode(wp_remote_retrieve_body( $response ), TRUE );

	
	echo $intro_text[0]['translation'];
	
	echo '<a href="https://cms.airentals.net/login.php" target="_blank">AIRentals property setup</a>';
	
	?>
	<form action="options.php" method="post">
        <?php 
        settings_fields( 'airentals_plugin_options' );
        do_settings_sections( 'airentals_plugin' ); ?>
		<?=submit_button('Save')?>
    </form><hr>
	<?php
	

	//HTML and PHP for Plugin Admin Page
	// Check whether the button has been pressed AND also check the nonce,
	
	if (isset($_POST['sync_menu_button']) && check_admin_referer('sync_menu_button_clicked')) {
	// the button has been pressed AND we've passed the security check
		airentals_sync_menu_button_action($plugin_options);
	}
	
	if (isset($_POST['sync_images_button']) && check_admin_referer('sync_images_button_clicked')) {
	// the button has been pressed AND we've passed the security check
		airentals_sync_images_button_action($plugin_options);
	}
	
	
	if($property_wp_key[0]['wp_key'] == $plugin_options['airentals_wp_code'])
	{
		echo '<p style="color:green;"><b>OK. To continue click on buttons bellow.</b></p>';
		
		echo '<form action="options-general.php?page=airentals_plugin" method="post">';

		wp_nonce_field('sync_menu_button_clicked');
		echo '<input type="hidden" value="true" name="sync_menu_button" />';
		submit_button('Sync menu');
		echo '</form><hr>';
		
		echo '<form action="options-general.php?page=airentals_plugin" method="post">';

		wp_nonce_field('sync_images_button_clicked');
		echo '<input type="hidden" value="true" name="sync_images_button" />';
		submit_button('Sync images');
		echo '</form>';

		echo '</div>';
		
	}
	else
	{
		echo '<p style="color:red;"><b>AIRentals code and Property ID dosn\'t match!</b></p>';
		
	}
	
}

function airentals_upload_dir($dirs)                                    
{                                                             
	$dirs['subdir'] = '/airetnals_images';                  
	$dirs['path'] = $dirs['basedir'] . '/airetnals_images'; 
	$dirs['url'] = $dirs['baseurl'] . '/airetnals_images';  

	return $dirs;                                         
}                                                             


function airentals_sync_images_button_action(&$plugin_options)
{
	global $wpdb;
	$added_file = "";
	
	$posts_to_delete = $wpdb->get_results("SELECT DISTINCT ID
	FROM CrCm_posts
	LEFT JOIN CrCm_term_relationships
	ON CrCm_term_relationships.object_id = CrCm_posts.ID
	LEFT JOIN CrCm_terms
	ON CrCm_terms.term_id = CrCm_term_relationships.term_taxonomy_id
	WHERE CrCm_terms.slug = 'airentals'
	AND CrCm_posts.post_type = 'attachment'");

	foreach($posts_to_delete as $key=>$ptd)
	{
		wp_delete_attachment($ptd->ID);
	}
	
	// API for getting pictures
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'AAA_vP8RBsKjDUbNGmXg',
		)

	);
	$response = wp_remote_post( 'https://api.atrade.hr/v1/property_images/'.$plugin_options['property_id'], $args );
	$property_images = json_decode(wp_remote_retrieve_body( $response ), TRUE );

	foreach($property_images as $single_image)
	{

		$image_url = $single_image['real_path'].$single_image['dir'].'/'.$single_image['photofile'];

		$upload_dir = wp_upload_dir();

		$response2 = wp_remote_get( $image_url );
		$image_data = wp_remote_retrieve_body( $response2 );
		
		$filename = basename( $image_url );

		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
		  $file = $upload_dir['path'] . '/' . $filename;
		}
		else {
		  $file = $upload_dir['basedir'] . '/' . $filename;
		}

		file_put_contents( $file, $image_data );
		
		$wp_filetype = wp_check_filetype( $filename, null );

		$attachment = array(
		  'post_mime_type' => $wp_filetype['type'],
		  'post_title' => sanitize_file_name( $filename ),
		  'post_content' => '',
		  'post_status' => 'inherit'
		);
		
		$added_file .= '
		'.$file;
		
		$attach_id = wp_insert_attachment( $attachment, $file );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		wp_update_attachment_metadata( $attach_id, $attach_data );	

		wp_set_post_tags( $attach_id, 'airentals');
		
	}
	
	remove_filter('upload_dir', 'airentals_upload_dir');
	
	
	?>
	<script>
	window.location = window.location.href;
	</script>
	<?php	
	
}

function airentals_sync_menu_button_action(&$plugin_options)
{

	global $wpdb;
	
	$id_parent_item = $plugin_options['id_parent_item'];
	
	// API for getting units
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'AAA_vP8RBsKjDUbNGmXg',
		)

	);
	$response = wp_remote_post( 'https://api.atrade.hr/v1/webshop_units/'.$plugin_options['property_id'], $args );
	$property_units = json_decode(wp_remote_retrieve_body( $response ), TRUE );

	
	// DELETE ALL CHILD MENU ITEMS
	$posts_to_delete = $wpdb->get_results("SELECT ID
	FROM CrCm_posts
	LEFT JOIN CrCm_postmeta 
	ON CrCm_postmeta.post_id = CrCm_posts.ID
	LEFT JOIN CrCm_term_relationships
	ON CrCm_term_relationships.object_id = CrCm_posts.ID
	WHERE CrCm_term_relationships.term_taxonomy_id = 9
	AND CrCm_postmeta.meta_key = '_menu_item_menu_item_parent'
	AND CrCm_postmeta.meta_value = ".$id_parent_item);
	
	foreach($posts_to_delete as $key=>$ptd)
	{
		wp_delete_post($ptd->ID);
	}
	
	// DELETE ALL UNIT PAGES
	$posts_to_delete = $wpdb->get_results("SELECT ID
	FROM CrCm_posts
	LEFT JOIN CrCm_postmeta 
	ON CrCm_postmeta.post_id = CrCm_posts.ID
	WHERE CrCm_postmeta.meta_key = 'unit_code'
	AND CrCm_postmeta.meta_value != ''");

	
	foreach($posts_to_delete as $key=>$ptd)
	{
		wp_delete_post($ptd->ID);
	}
	
	// DELETE PARENT MENU ITEM
	wp_delete_post($id_parent_item);
 

	// UNOS PARENT ITEM
	$menu_item_name_trim = preg_replace('/\W+/', '-', strtolower($plugin_options['menu_item_name']));
	

	$id_parent_item = wp_insert_post(
        array(
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
        'post_author'    => 1,
        'post_title'     => $plugin_options['menu_item_name'],
        'post_name'      => $menu_item_name_trim,
        'post_status'    => 'publish',
        'post_type'      => 'nav_menu_item',
        'post_parent'    => 0,
		'menu_order'	 => $plugin_options['redni_broj_u_menu']
        )
    );
	
	
	$multidimensional_options = array(
		'airentals_wp_code'=> $plugin_options['airentals_wp_code'],
		'property_id'=> $plugin_options['property_id'],
		'redni_broj_u_menu' => $plugin_options['redni_broj_u_menu'],
		'id_parent_item' => $id_parent_item,
		'menu_item_name' => $plugin_options['menu_item_name']
	);
  
	//Update entire array
	update_option('airentals_plugin_options', $multidimensional_options);
	
	if($id_parent_item > 0 )
	{

		update_post_meta( $id_parent_item, '_menu_item_type', 'custom' );
		update_post_meta( $id_parent_item, '_menu_item_menu_item_parent', '0' );
		update_post_meta( $id_parent_item, '_menu_item_object_id', $id_parent_item );
		update_post_meta( $id_parent_item, '_menu_item_object', 'custom' );
		update_post_meta( $id_parent_item, '_menu_item_target', '' );
		update_post_meta( $id_parent_item, '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}' );
		update_post_meta( $id_parent_item, '_menu_item_xfn', '' );
		update_post_meta( $id_parent_item, '_menu_item_url', '#' );
		update_post_meta( $id_parent_item, '_wpml_location_migration_done', '1' );
		
		$wpdb->query(
			$wpdb->prepare(
			"
			INSERT INTO $wpdb->term_relationships
			( object_id, term_taxonomy_id, term_order )
			VALUES ( %d, %d, %d )
			",
			array(
				$id_parent_item,
				9,
				0
				)
			)
		);
	}
	
	
	// EO UNOS PARENT ITEM
	
	//CUSTOM PAGE
	// BOOK NOW
	$page_id = wp_insert_post(
		array(
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_author'    => 1,
		'post_title'     => 'Book Now'.' AUTO GENERATED BY AIRentals PLUGIN',
		'post_name'      => 'book-now',
		'post_status'    => 'publish',
		'post_type'      => 'page',
		'post_parent'    => 0
		)
	);

	update_post_meta( $page_id, '_wp_page_template', 'customBook.php' );
	update_post_meta( $page_id, '_et_pb_post_hide_nav', 'default' );
	update_post_meta( $page_id, '_et_pb_page_layout', 'et_no_sidebar' );
	update_post_meta( $page_id, '_et_pb_side_nav', 'off' );
	update_post_meta( $page_id, '_et_pb_first_image', '' );
	update_post_meta( $page_id, '_et_pb_truncate_post', '' );
	update_post_meta( $page_id, '_et_pb_truncate_post_date', '' );
	update_post_meta( $page_id, '_et_pb_old_content', '' );
	update_post_meta( $page_id, 'unit_code', 'HR-BOOK-NOW' );
	update_post_meta( $page_id, '_thumbnail_id', '560' );
	
	//SHOPPING BASKET
	
	$page_id = wp_insert_post(
		array(
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
		'post_author'    => 1,
		'post_title'     => 'Shopping basket'.' AUTO GENERATED BY AIRentals PLUGIN',
		'post_name'      => 'shopping-basket',
		'post_status'    => 'publish',
		'post_type'      => 'page',
		'post_parent'    => 0
		)
	);

	update_post_meta( $page_id, '_wp_page_template', 'customBasket.php' );
	update_post_meta( $page_id, '_et_pb_post_hide_nav', 'default' );
	update_post_meta( $page_id, '_et_pb_page_layout', 'et_no_sidebar' );
	update_post_meta( $page_id, '_et_pb_side_nav', 'off' );
	update_post_meta( $page_id, '_et_pb_first_image', '' );
	update_post_meta( $page_id, '_et_pb_truncate_post', '' );
	update_post_meta( $page_id, '_et_pb_truncate_post_date', '' );
	update_post_meta( $page_id, '_et_pb_old_content', '' );
	update_post_meta( $page_id, 'unit_code', 'HR-SHOPPING BASKET' );
	update_post_meta( $page_id, '_thumbnail_id', '560' );
	
	$i = 0;
	foreach($property_units as $single_unit)
	{
		$post_name = preg_replace('/\W+/', '-', strtolower($single_unit['name']));
		
		$page_id = wp_insert_post(
			array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_author'    => 1,
			'post_title'     => $single_unit['name'] .' AUTO GENERATED BY AIRentals PLUGIN FOR UNIT - '.$single_unit['atd_unit_code'],
			'post_name'      => $post_name,
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_parent'    => 0
			)
		);
		
		update_post_meta( $page_id, '_wp_page_template', 'customObject.php' );
		update_post_meta( $page_id, '_et_pb_post_hide_nav', 'default' );
		update_post_meta( $page_id, '_et_pb_page_layout', 'et_no_sidebar' );
		update_post_meta( $page_id, '_et_pb_side_nav', 'off' );
		update_post_meta( $page_id, '_et_pb_first_image', '' );
		update_post_meta( $page_id, '_et_pb_truncate_post', '' );
		update_post_meta( $page_id, '_et_pb_truncate_post_date', '' );
		update_post_meta( $page_id, '_et_pb_old_content', '' );
		update_post_meta( $page_id, 'unit_code', $single_unit['atd_unit_code'] );
		update_post_meta( $page_id, '_thumbnail_id', '560' );
		
		$inserted_id_post = 0;
		$i++;
		
		
		
		$inserted_id_post = wp_insert_post(
			array(
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
			'post_author'    => 1,
			'post_title'     => $single_unit['name'],
			'post_name'      => $post_name,
			'post_status'    => 'publish',
			'post_type'      => 'nav_menu_item',
			'post_parent'    => 0,
			'menu_order'	 => $i
			)
		);
		
		if($inserted_id_post > 0 )
		{
			
			update_post_meta( $inserted_id_post, '_menu_item_type', 'custom' );
			update_post_meta( $inserted_id_post, '_menu_item_menu_item_parent', $id_parent_item );
			update_post_meta( $inserted_id_post, '_menu_item_object_id', $inserted_id_post );
			update_post_meta( $inserted_id_post, '_menu_item_object', 'custom' );
			update_post_meta( $inserted_id_post, '_menu_item_target', '' );
			update_post_meta( $inserted_id_post, '_menu_item_classes', 'a:1:{i:0;s:0:\"\";}' );
			update_post_meta( $inserted_id_post, '_menu_item_xfn', '' );
			update_post_meta( $inserted_id_post, '_menu_item_url', get_permalink($page_id) );
			update_post_meta( $inserted_id_post, '_wpml_location_migration_done', '1' );
			
			
			$wpdb->query(
				$wpdb->prepare(
				"
				INSERT INTO $wpdb->term_relationships
				( object_id, term_taxonomy_id, term_order )
				VALUES ( %d, %d, %d )
				",
				array(
					$inserted_id_post,
					9,
					0
					)
				)
			);
			
			if($uneseni_id_post != "")
			{
				$uneseni_id_post .= ' - '.$single_unit['id_unit'];
			}
			else
			{
				$uneseni_id_post .= $single_unit['id_unit'];
			}
		
		};
	};
	
	//echo '<div id="message" class="updated fade"><p>Uneseni unit id je - '. $uneseni_id_post . '</p></div>';
	
	?>
	<script>
	alert("<?='Uneseni unit id je - '. $uneseni_id_post?>");
	window.location = window.location.href;
	</script>
	<?php
	
}  

add_action( 'admin_enqueue_scripts', 'airentals_add_tab_script' );

/**
* Load the JavaScript on admin
*/
function airentals_add_tab_script() {
    // Adding the file with the plugin_dir_url. The file admin.js is in root of our plugin folder
    // Change the URL for your project
    wp_enqueue_script( 'admin-js', plugin_dir_url( __FILE__ ) . '/admin.js', array( 'jquery' ), '', true );       
}

add_filter( 'ajax_query_attachments_args', 'airentals_query_attachments', 10, 1);

/**
* Change the query used to retrieve attachments
* The Query will retrieve 5 random attachments
*/
function airentals_query_attachments( $args ) {
   
    if( isset( $_POST['query']['airentals'] ) ) {
        $args['tag'] = 'airentals';

        unset( $_POST['query']['airentals'] );
    }
    return $args;
}


?>
