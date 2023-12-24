<?php
/**
 * @package Akismet
 */
/*
Plugin Name: SEO
Plugin URI: https://ravuss.com
Description: MALLKO MALKO MALKO
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
if($wpdb->get_var( "show tables like '".get_ravuss_seo_plugin_table_name()."'" ) != get_ravuss_seo_plugin_table_name() ) 
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

  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Homepage Settings", "Homepage Settings", 0, "ravuss_seo_plugin_homepage_slug", "ravuss_seo_plugin_homepage_settings");
  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Posts Page Settings", "Posts Page Settings", 0, "ravuss_seo_plugin_single_page_slug", "ravuss_seo_plugin_single_page_settings");
  add_submenu_page("ravuss_seo_plugin_general_settings_slug", "Pages Settings", "Pages Settings", 0, "ravuss_seo_plugin_pages_slug", "ravuss_seo_plugin_pages_settings");
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
    </table>';
}

// SEPARATOR
function get_ravuss_seo_plugin_separator(){
	global $wpdb;

	$separator = $wpdb->get_row($wpdb->prepare("SELECT seo_value FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'separator'"));
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

	$home_title_value = $wpdb->get_row($wpdb->prepare("SELECT seo_value FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = '$seo_key'"));
	if(!empty($home_title_value)){
		if($decode){
			$home_title_value = $home_title_value->seo_value;
			$title = str_replace("%"."site_title%", get_bloginfo(), $home_title_value);
			$title = str_replace("%"."separator%", get_ravuss_seo_plugin_separator(), $title);
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

//PAGES SETTINGS 
function ravuss_seo_plugin_pages_settings() {
	global $wpdb;


?>
<div class="wrap">
        <h2><?php echo get_admin_page_title(); ?></h2>
        <form method="post" action="admin.php?page=ravuss_seo_plugin_pages_slug">
            <?php wp_nonce_field('update-options') ?>
            <?php echo get_ravuss_table_with_shortcodes(); ?>
           	<p><strong>Title</strong><br />
                <input type="text" name="page_title_value_input" size="45" value="<?php echo esc_html(ravuss_seo_plugin_decode_text(false, 'page_title_value')) ?>" />

            </p>
            <p>
                Title: <b><u><?php echo  esc_html(ravuss_seo_plugin_decode_text(true, 'page_title_value')); ?></u></b>
            </p>
             <p><strong>Description</strong><br />
           		<textarea name="page_description_value_input" cols="48" rows="5"><?php echo esc_html(ravuss_seo_plugin_decode_text(false, 'page_description_value')) ?></textarea>
            </p>
            <p>
                Description: <b><u><?php echo esc_html(ravuss_seo_plugin_decode_text(true, 'page_description_value')); ?></u></b>
            </p>
            <p><input type="submit" name="update_single_page_settings" value="Update" /></p>
        </form>
        <?php 
        if(isset($_POST['update_single_page_settings'])){
        	if(!empty($_POST['page_title_value_input'])){
        		$page_title_value_input = sanitize_text_field($_POST['page_title_value_input']);
        		$page_title_value_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'page_title_value'"));
        		if(!empty($page_title_value_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $page_title_value_input),array('seo_key'=>'page_title_value'));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'page_title_value',
					    'seo_value' => $page_title_value_input,
					));
        		}
			}

			if(!empty($_POST['page_description_value_input'])){
        		$page_description_value_input = sanitize_text_field($_POST['page_description_value_input']);

				$page_description_value_input_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'page_description_value'"));
        		if(!empty($page_description_value_input_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $page_description_value_input),array('seo_key'=>'page_description_value'));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'page_description_value',
					    'seo_value' => $page_description_value_input,
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

//SINGLE PAGE SETTINGS
function ravuss_seo_plugin_single_page_settings() {
	global $wpdb;

?>
<div class="wrap">
        <h2><?php echo get_admin_page_title(); ?></h2>
        <form method="post" action="admin.php?page=ravuss_seo_plugin_single_page_slug">
            <?php wp_nonce_field('update-options') ?>
            <?php echo get_ravuss_table_with_shortcodes(); ?>
           	<p><strong>Title</strong><br />
                <input type="text" name="single_title_value_input" size="45" value="<?php echo esc_html(ravuss_seo_plugin_decode_text(false, 'single_title_value')); ?>" />

            </p>
            <p>
                Title: <b><u><?php echo ravuss_seo_plugin_decode_text(true, 'single_title_value'); ?></u></b>
            </p>
            <p><strong>Description</strong><br />
           		<textarea name="single_description_value_input" cols="48" rows="5"><?php echo esc_html(ravuss_seo_plugin_decode_text(false, 'single_description_value')) ?></textarea>
            </p>
            <p>
                Description: <b><u><?php echo esc_html(ravuss_seo_plugin_decode_text(true, 'single_description_value')); ?></u></b>
            </p>
            <p><input type="submit" name="update_single_page_settings" value="Update" /></p>
        </form>
        <?php 
        if(isset($_POST['update_single_page_settings'])){
        	if(!empty($_POST['single_title_value_input'])){
        		$single_title_value_input = sanitize_text_field($_POST['single_title_value_input']);
				
				$single_page_title_value_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'single_title_value'"));
        		if(!empty($single_page_title_value_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $single_title_value_input),array('seo_key'=>'single_title_value'));
        		}else{
	        		$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'single_title_value',
					    'seo_value' => $single_title_value_input,
					));
	        	}
			}

        	if(!empty($_POST['single_description_value_input'])){
        		$single_description_value_input = sanitize_text_field($_POST['single_description_value_input']);

				$home_description_value_input_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'single_description_value'"));
        		if(!empty($home_description_value_input_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $single_description_value_input),array('seo_key'=>'single_description_value'));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'single_description_value',
					    'seo_value' => $single_description_value_input,
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

//HOMEPAGE SETTINGS
function ravuss_seo_plugin_homepage_settings() {
	global $wpdb;
    ?>
<div class="wrap">
        <h2><?php echo get_admin_page_title(); ?></h2>
        <form method="post" action="admin.php?page=ravuss_seo_plugin_homepage_slug">
            <?php wp_nonce_field('update-options') ?>
            <?php echo get_ravuss_table_with_shortcodes(); ?>
           	<p><strong>Title</strong><br />
                <input type="text" name="home_title_value_input" size="45" value="<?php echo esc_html(ravuss_seo_plugin_decode_text(false, 'home_title_value')) ?>" />

            </p>
            <p>
                Title: <b><u><?php echo esc_html(ravuss_seo_plugin_decode_text(true, 'home_title_value')); ?></u></b>
            </p>
           	<p><strong>Description</strong><br />
           		<textarea name="home_description_value_input" cols="48" rows="5"><?php echo esc_html(ravuss_seo_plugin_decode_text(false, 'home_description_value')) ?></textarea>
    
            </p>
            <p>
                Description: <b><u><?php echo esc_html(ravuss_seo_plugin_decode_text(true, 'home_description_value')); ?></u></b>
            </p>
           
            <p><input type="submit" name="update_homepage_settings" value="Update" /></p>
        </form>
        <?php 
        if(isset($_POST['update_homepage_settings'])){

        	if(!empty($_POST['home_title_value_input'])){
        		$home_title_value_input = sanitize_text_field($_POST['home_title_value_input']);

        		$home_title_value_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'home_title_value'"));
        		if(!empty($home_title_value_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $home_title_value_input),array('seo_key'=>'home_title_value'));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'home_title_value',
					    'seo_value' => $home_title_value_input,
					));
        		}
        	}

        	if(!empty($_POST['home_description_value_input'])){
        		$home_description_value_input = sanitize_text_field($_POST['home_description_value_input']);

				$home_description_value_input_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'home_description_value'"));
        		if(!empty($home_description_value_input_row_exists)){
        			$wpdb->update( get_ravuss_seo_plugin_table_name(), array( 'seo_value' => $home_description_value_input),array('seo_key'=>'home_description_value'));
        		}else{
        			$wpdb->insert(get_ravuss_seo_plugin_table_name(), array(
					    'seo_key' => 'home_description_value',
					    'seo_value' => $home_description_value_input,
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
        	
				$separator_value_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'separator'"));
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
		$is_home_title_custom_key = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'home_title_custom_key' AND seo_value = 1" ) );
		if(!empty($is_home_title_custom_key)){
			$home_title_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'home_title_value'" ) );
			if(!empty($home_title_value)){
				//$title = str_replace("%"."site_title%", get_bloginfo(), $home_title_value->seo_value);
				$title = $home_title_value->seo_value;
			}
		}

	}elseif(is_single()){
		$single_title_value = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'single_title_value'" ) );
		$use_custom_title_key = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'use_custom_single_title_key'"));
		if(!empty($single_title_value)){
			$title = ravuss_seo_plugin_decode_text(true, 'single_title_value');
		}

	}elseif(is_page()){
		$title = ravuss_seo_plugin_decode_text(true, 'page_title_value');
		
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
 		$home_description_value_input_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'home_description_value'"));
		if(!empty($home_description_value_input_row_exists)){
			$description = ravuss_seo_plugin_decode_text(true, 'home_description_value');
		}
	}elseif(is_single()){
 		$single_description_value_input_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'single_description_value'"));
		if(!empty($single_description_value_input_row_exists)){
			$description = ravuss_seo_plugin_decode_text(true, 'single_description_value');
		}

	}elseif(is_page()){
 		$page_description_value_input_row_exists = $wpdb->get_row($wpdb->prepare("SELECT id FROM `".get_ravuss_seo_plugin_table_name()."` WHERE seo_key = 'page_description_value'"));
		if(!empty($page_description_value_input_row_exists)){
			$description = ravuss_seo_plugin_decode_text(true, 'page_description_value');
		}		
		
	}

	if(!empty($description)){
		echo '<meta name="description" content="'.esc_html($description).'"/>'."\n";
	}

}


add_action( 'wp_head', 'ravuss_seo_plugin_description' );
