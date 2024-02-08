<?php
/**
 * @package Akismet
 */
/*
Plugin Name: Ravuss Seo
Plugin URI: https://ravuss.com
Description: This is the simplest and easiest SEO Plugin for your website. This plugin is being consistently updated until it gives all the necessary features for your website.
You can use this plugin with no knowledge at all. 
Version: 0.1
Requires at least: 5.8
Requires PHP: 5.6.20
Author: Valentin Yuliyanov
Author URI: https://ravuss.com
License: GPLv2 or later
Text Domain: ravuss
*/

register_activation_hook( __FILE__, 'seo_plugin_activate' );

function seo_plugin_activate(){

  global $wpdb; 
  $charset_collate = $wpdb->get_charset_collate();

 //Check to see if the table exists already, if not, then create it
  $checkIfTableExistsQuery = $wpdb->prepare("SELECT tables LIKE %i",get_ravuss_seo_plugin_table_name());
if($wpdb->get_var( $checkIfTableExistsQuery ) != get_ravuss_seo_plugin_table_name() ) 
 {

	$sql = "CREATE TABLE ".get_ravuss_seo_plugin_table_name()." (
	        `id` int(11) NOT NULL auto_increment,
	        `seo_key` varchar(255) NOT NULL,
	        `seo_value` text(5000) NOT NULL,
	        UNIQUE KEY id (id)
	) $charset_collate;";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}

}
function ravuss_seo_plugin_admin_menu() {
  add_menu_page(
    'General Settings',           // Page title
    'SEO PLUGIN',           // Menu title
    'manage_options',       // Capability
    'ravuss_seo_plugin_general_settings_slug',    // Menu slug
    'ravuss_seo_plugin_general_settings_views' // Callback function
  );

  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Homepage Settings", "Homepage Settings", 0, "ravuss_seo_plugin_homepage_slug", "ravuss_seo_plugin_settings");
 
  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Posts Page Settings", "Posts Page Settings", 0, "ravuss_seo_plugin_posts_slug", "ravuss_seo_plugin_settings");
  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Pages Settings", "Pages Settings", 0, "ravuss_seo_plugin_pages_slug", "ravuss_seo_plugin_settings");
  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Category Settings", "Category Settings", 0, "ravuss_seo_plugin_categories_slug", "ravuss_seo_plugin_settings");

}
add_action('admin_menu', 'ravuss_seo_plugin_admin_menu');

//DB TABLE
function get_ravuss_seo_plugin_table_name(){
	global $wpdb;
	$db_table_name = $wpdb->prefix . 'seo_plugin';  // table name
	return $db_table_name;
}

function get_ravuss_table_with_shortcodes(){
	return '            
	<table style="border:1px solid #333;padding:10px;">
    	<tr>
    		<td><b>Site Title - </b></td>
    		<td>%site_title%</td>
    	</tr>
    	<tr>
    		<td><b>Post Title - </b></td>
    		<td>%post_title%</td>
    	</tr>
    	<tr>
    		<td><b>Page Title - </b></td>
    		<td>%page_title%</td>
    	</tr>
    	<tr>
    		<td><b>Separator - </b></td>
    		<td>%separator%</td>
    	</tr>
    	<tr>
    		<td><b>Content - </b></td>
    		<td>%content%</td>
    	</tr>
    </table>';
}

// SEPARATOR
function get_ravuss_seo_plugin_separator(){
	global $wpdb;

	$checkIfTableExistsQuery = $wpdb->prepare("SELECT seo_value FROM %i WHERE seo_key = %s", get_ravuss_seo_plugin_table_name(), 'separator');
	$separator = $wpdb->get_row($checkIfTableExistsQuery);
	if(!empty($separator)){
		$separator = $separator->seo_value;
		return $separator;
	}

	return;
}

function ravuss_seo_plugin_decode_text($decode=false, $seo_key=''){
	global $wpdb;

	$title = '';
	if(empty($seo_key)){
		return '';
	}
	$prepare_title_value = $wpdb->prepare("SELECT seo_value FROM %i WHERE seo_key = '%s'", get_ravuss_seo_plugin_table_name(), $seo_key);
	$home_title_value = $wpdb->get_row($prepare_title_value);
	//print_r($prepare_title_value);
	if(!empty($home_title_value)){
		if($decode){
			$home_title_value = $home_title_value->seo_value;
			$title = str_replace("%"."site_title%", get_bloginfo(), $home_title_value);
			$title = str_replace("%"."separator%", get_ravuss_seo_plugin_separator(), $title);
			$wp_get_the_content = mb_substr(wp_strip_all_tags( get_the_content() ), 0, 160);
			$title = str_replace("%"."content%", $wp_get_the_content, $title);
			if(!function_exists('get_current_screen')){
				$title = str_replace("%"."post_title%", get_the_title(), $title);
				$title = str_replace("%"."page_title%", get_the_title(), $title);

			}

		}else{
			$title = $home_title_value->seo_value;
		}
	}else{
		return '';
		//$title = get_bloginfo();
	}
	
	return $title;
}
	
//HOMEPAGE SETTINGS
function ravuss_seo_plugin_settings() {
	global $wpdb;
	if(empty($_GET['page'])){
		return '';
	}
	$current_settings_page = sanitize_text_field($_GET['page']);
	if($current_settings_page == 'ravuss_seo_plugin_posts_slug'){
		$sql_title_value = 'posts_title_value';
		$sql_description_value = 'posts_description_value';
	}elseif($current_settings_page == 'ravuss_seo_plugin_homepage_slug'){
		$sql_title_value = 'home_title_value';
		$sql_description_value = 'home_description_value';
	}elseif($current_settings_page == 'ravuss_seo_plugin_pages_slug'){
		$sql_title_value = 'pages_title_value';
		$sql_description_value = 'page_description_value';
	}elseif($current_settings_page == 'ravuss_seo_plugin_categories_slug'){
		$sql_title_value = 'categories_title_value';
		$sql_description_value = 'categories_description_value';
	}else{
		$sql_title_value = '';
		$sql_description_value = '';
	}
	$nonce = wp_create_nonce( 'my-nonce' );
    ?>
<div class="wrap">
        <h2><?php echo get_admin_page_title(); ?></h2>
        <form method="post" action="admin.php?page=<?php echo $current_settings_page ?>&_wpnonce=<?php echo $nonce?>">
            <?php wp_nonce_field('update-options') ?>
            <?php echo get_ravuss_table_with_shortcodes(); ?>
           	<p><strong>Title</strong><br />
                <input type="text" name="<?php echo $sql_title_value ?>_input" size="45" value="<?php echo esc_html(ravuss_seo_plugin_decode_text(false, $sql_title_value)) ?>" />

            </p>
            <p>
                Title: <b><u><?php echo esc_html(ravuss_seo_plugin_decode_text(true, $sql_title_value)); ?></u></b>
            </p>
           	<p><strong>Description</strong><br />
           		<textarea name="<?php echo $sql_description_value ?>_input" cols="48" rows="5"><?php echo esc_html(ravuss_seo_plugin_decode_text(false, $sql_description_value)) ?></textarea>
    
            </p>
            <p>
                Description: <b><u><?php echo esc_html(ravuss_seo_plugin_decode_text(true, $sql_description_value)); ?></u></b>
            </p>
           
            <p><input type="submit" name="update_ravuss_seo_settings" value="Update" /></p>
        </form>
        <?php 
        if(isset($_POST['update_ravuss_seo_settings'])){

        	$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'update-options' ) ) {

				     die( 'Security check' ); 

				} else {
        	if(!empty($_POST[$sql_title_value.'_input'])){
        		$title_value_input = sanitize_text_field($_POST[$sql_title_value.'_input']);

        		$title_value_row_exists_prepare = $wpdb->prepare("SELECT id FROM %i WHERE seo_key = %s", get_ravuss_seo_plugin_table_name(), $sql_title_value);
        		$title_value_row_exists = $wpdb->get_row($title_value_row_exists_prepare);
        		if(!empty($title_value_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $title_value_input),array('seo_key'=>$sql_title_value));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => $sql_title_value,
					    'seo_value' => $title_value_input,
					));
        		}
        	}

        	if(!empty($_POST[$sql_description_value.'_input'])){
        		$description_value_input = sanitize_text_field($_POST[$sql_description_value.'_input']);
        		$description_value_input_row_exists_prepare = $wpdb->prepare("SELECT id FROM %i WHERE seo_key = %s", get_ravuss_seo_plugin_table_name(), $sql_description_value);
				$description_value_input_row_exists = $wpdb->get_row($description_value_input_row_exists_prepare);
        		if(!empty($description_value_input_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $description_value_input),array('seo_key'=>$sql_description_value));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => $sql_description_value,
					    'seo_value' => $description_value_input,
					));
        		}

        	}

        	echo "<script type='text/javascript'>
        window.location=document.location.href;
        </script>";
        }
    }
        ?>
    </div>
    <?php 
}


function ravuss_seo_plugin_general_settings_views() {
	global $wpdb;

?>
<div class="wrap">
        <h2><?php echo get_admin_page_title(); ?></h2>
        <form method="post" action="admin.php?page=ravuss_seo_plugin_general_settings_slug">
            <?php wp_nonce_field('update-options') ?>
           	<p><strong>Separator</strong><br />
                <input type="text" name="separator" size="45" value="<?php echo esc_html(get_ravuss_seo_plugin_separator()) ?>" />

            </p>
            <p><input type="submit" name="update_general_settings" value="Update" /></p>
        </form>
        <?php 
        if(isset($_POST['update_general_settings'])){
        	if(!empty($_POST['separator'])){
        		$separator = sanitize_text_field($_POST['separator']);
        	
        		$separator_value_row_exists_prepare = $wpdb->prepare("SELECT id FROM %i WHERE seo_key = %s", get_ravuss_seo_plugin_table_name(), 'separator');
				$separator_value_row_exists = $wpdb->get_row($separator_value_row_exists_prepare);
        		if(!empty($separator_value_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $separator),array('seo_key'=>'separator'));
        		}else{
	        		$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'separator',
					    'seo_value' => $separator,
					));
	        	}


			}


        	echo "<script type='text/javascript'>
        window.location=document.location.href;
        </script>";
        }
        ?>
    </div>

<?php 
}

// Custom function should return an array
function ravuss_seo_plugin_get_title( $title ) {
	global $wpdb;
	$title = get_bloginfo();
	if(is_home()){
		$title = ravuss_seo_plugin_decode_text(true, 'home_title_value');
	}elseif(is_single()){
		$title = ravuss_seo_plugin_decode_text(true, 'posts_title_value');
	}elseif(is_page()){
		$title = ravuss_seo_plugin_decode_text(true, 'pages_title_value');
		
	}elseif(is_category()){
		$title = ravuss_seo_plugin_decode_text(true, 'categories_title_value');
	}else{
		$title = '';
	}
    return array(
     'title' => $title,
    );	
}

add_filter( 'document_title_parts', 'ravuss_seo_plugin_get_title', 10 );

function ravuss_seo_plugin_description(){
	global $wpdb;
 $description = '';
 	if(is_home()){
		$description = ravuss_seo_plugin_decode_text(true, 'home_description_value');
	}elseif(is_single()){
		$description = ravuss_seo_plugin_decode_text(true, 'posts_description_value');
	}elseif(is_page()){
		$description = ravuss_seo_plugin_decode_text(true, 'pages_description_value');
	}elseif(is_category()){
		$description = ravuss_seo_plugin_decode_text(true, 'categories_description_value');
	}else{
		$description = '';
	}

	if(!empty($description)){
		echo '<meta name="description" content="'.esc_html($description).'"/>'."\n";
	}

}


add_action( 'wp_head', 'ravuss_seo_plugin_description' );
