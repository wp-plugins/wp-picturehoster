<?php
/*
Plugin Name: WP Picturehoster
Plugin URI: http://www.horttcore.de/wordpress/wp-picturehoster
Description: Host images on your blog just like ImageShack.us
Version: 1.0
Author: Ralf Hortt
Author URI: http://www.horttcore.de/
*/

define('WP_PICTUREHOSTING_DIR', WP_CONTENT_DIR.'/wp-picturehosting');
define('WP_PICTUREHOSTING_URL', WP_CONTENT_URL.'/wp-picturehosting');

//======================================
// @Description: Shortcode for upload formular
// @Return: str $content Upload Form
function wpp_callback(){
global $user_ID;
	if ($user_ID)
	{
		if ($_FILES)
		{
			$content .= wpp_upload_picture();
		}
		$content .= '<form method="post" enctype="multipart/form-data">';
		$content .= '<p><label for="upload">File: </label>';
		$content .= '<input type="file" name="upload" id="upload" />';
		$content .= '<button type="submit">'.__('Submit','wp-picturehosting').'</button></p>';
		$content .= '</form>';
	}
	else
	{
		$content .= '<p class="error_message">'.__('You have to be logged in to use this service', 'wp-picturehoster').'</p>';
	}
	return $content;
}

//======================================
// @Description: 
// @Require: 
// @Optional: 
// @Return: 
function wpp_display_links($attach_id){
	$post = get_post($attach_id);
	$metadata = get_post_meta($attach_id, '_wp_attachment_metadata', 'true');
	$url = get_permalink($attach_id);
	$thumb_url = WP_PICTUREHOSTING_URL.'/'.$metadata['sizes']['thumbnail']['file'];
	$medium_url = WP_PICTUREHOSTING_URL.'/'.$metadata['sizes']['medium']['file'];
	$large_url = WP_PICTUREHOSTING_URL.'/'.$metadata['sizes']['large']['file'];
	
	$link = htmlentities('<a href="'.$url.'" title="QuickPost"><img src="'.$medium_url.'" alt="'.$small_url.'" /></a>');
	$thumb = htmlentities('[URL='.$url.'][IMG]'.$thumb_url.'[/IMG][/URL]');
	$medium = htmlentities('[URL='.$url.'][IMG]'.$medium_url.'[/IMG][/URL]');
	$large = htmlentities('[URL='.$url.'][IMG]'.$large_url.'[/IMG][/URL]');
	$thumb2 = htmlentities('[URL='.$url.'][IMG]'.$thumb_url.'[/IMG][/URL]');
	$medium2 = htmlentities('[url='.$url.'][img='.$medium_url.'][/url]');
	$large2 = htmlentities('[url='.$url.'][img='.$large_url.'][/url]');
	
	$content .= '<h3>'.__('Link to your image', 'wp-picturehoster').'</h3>';
	$content .= '<blockquote id="wpp_links">';
	$content .= '<p><img width="100" src="'.$thumb_url.'" alt="'.$post->post_title.'" title="'.$post->post_title.'" /></p>';
	$content .= '<p><label for="link"><strong>'.__('Link:', 'wp-picturehosting').'</strong></label> <input id="link" type="text" value="'.$link.'" /></p>';
	
	$content .= '<p>[URL=<em>'.__('path/to/large/file', 'wp-picturehoster').'</em>][IMG]'.__('path/to/thumbnail', 'wp-picturehoster').'[/IMG][/URL]</p>';
	$content .= '<p><label for="thumb"><strong>'.__('Forum thumbnail:', 'wp-picturehosting').'</strong></label> <input type="text" id="thumb" value="'.$thumb.'" /> (1)</p>';
	$content .= '<p><label for="medium"><strong>'.__('Forum medium:', 'wp-picturehosting').'</strong></label> <input id="medium" type="text" value="'.$medium.'" /> (1)</p>';
	$content .= '<p><label for="large"><strong>'.__('Forum large:', 'wp-picturehosting').'</strong></label> <input id="large" type="text" value="'.$large.'" /> (1)</p>';

	$content .= '<p>[url=<em>'.__('path/to/large/file', 'wp-picturehoster').'</em>][img='.__('path/to/thumbnail', 'wp-picturehoster').'][/url]</p>';
	$content .= '<p><label for="thumb2"><strong>'.__('Forum thumbnail:', 'wp-picturehosting').'</strong></label></label> <input id="thumb2" type="text" value="'.$thumb2.'" /> (2)</p>';
	$content .= '<p><label for="medium2"><strong>'.__('Forum medium:', 'wp-picturehosting').'</strong></label> <input type="text" id="medium2" value="'.$medium2.'" /> (2)</p>';
	$content .= '<p><label for="large2"><strong>'.__('Forum large:', 'wp-picturehosting').'</strong></label> <input type="text" id="large2" value="'.$large2.'" /> (2)</p>';
	$content .= '</blockquote>';
	$content .= '<h3>'.__('Upload another image', 'wp-picturehoster').'</h3>';
	return $content;
}


/**
 * Deletes the original file if removed from the media pool
 *
 * @return void
 * @author Ralf Hortt
 **/
function wpp_fix_delete($ID)
{
	$metadata = get_post_meta($ID, '_wp_attachment_metadata', true);
	if ($metadata['file'] && preg_match('&wp-picturehosting&', $metadata['file']) && file_exists($metadata['file']))
	{
		if (unlink($metadata['file']))
			return true;
		else
			return false;
	}
}


/**
 * Includes the plugin stylesheet
 *
 * @return void
 * @author Ralf Hortt
 **/
function wpp_header()
{
	?>
	<link rel="stylesheet" href="<?php echo PLUGINDIR ?>/wp-picturehoster/wp-picturehoster.css" type="text/css" media="screen">
	<?php
}

//======================================
// @Description: Runs on Plugininstallation
function wpp_install(){
	if (!is_dir(WP_PICTUREHOSTING_DIR)) mkdir(WP_PICTUREHOSTING_DIR);
}


/**
 * Template tag
 *
 * @return void
 * @author Ralf Hortt
 **/
function wpp_picturehoster()
{
	echo wpp_callback();
}


//======================================
// @Description: handle file upload
function wpp_upload_picture(){
global $user_ID;
	if ($user_ID) {
		$image = strtolower(sanitize_file_name($_FILES['upload']['name']));
		if (!file_exists(WP_PICTUREHOSTING_DIR.'/'.$image))
		{
			if (!function_exists('wp_generate_attachment_metadata'))
				require_once(ABSPATH . 'wp-admin/includes/image.php');
			if ( preg_match('&.jpg|.jpeg|.gif|.png&i', $image) ) {
				if ( move_uploaded_file( $_FILES['upload']['tmp_name'], WP_PICTUREHOSTING_DIR.'/'.$image ) ) {		
					// Build DB entry
					$attachment['post_title'] = $image;
					$attachment['guid'] = WP_PICTUREHOSTING_URL.'/'.$attachment['post_title'];
					$attachment['post_status'] = 'inherit';
					$attachment['post_mime_type'] = 'image/';
					$attachment['post_author'] = $user_ID;
					// Send to DB
					$attach_id = wp_insert_attachment( $attachment );
					$attach_data = wp_generate_attachment_metadata( $attach_id, WP_PICTUREHOSTING_DIR.'/'.$image );
					wp_update_attachment_metadata( $attach_id,  $attach_data );
					// Adding some post meta data
					update_post_meta( $attach_id, 'wpp_picturehoster', '1' ); // its a WPP image
					// Output
					$content = wpp_display_links($attach_id);
				}
				else {
					$content = '<p class="error_message">'.__('Upload failed', 'wp-picturehoster').'</p>';
				}
			}
			else {
				$content = '<p class="error_message">'.__('Wrong data type', 'wp-picturehoster').'</p>';
			}
		}
		else
		{
			$content = '<p class="error_message">'.__('File with that name already exists', 'wp-picturehoster').'</p>';
		}
	}
	else {
		$content = '<p class="error_message">'.__('You have to be registered in this Blog to use this service', 'wp-picturehoster').'</p>';
	}
	return $content;
}

//======================================
// @WP-HOOKS: 
load_plugin_textdomain('wp-picturehoster');
add_shortcode('PICTUREHOSTER', 'wpp_callback');
add_action('delete_attachment', 'wpp_fix_delete');
add_action('wp_head', 'wpp_header');
register_activation_hook(__FILE__, 'wpp_install');
?>
