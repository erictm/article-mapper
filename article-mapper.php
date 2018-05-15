<?php
defined( 'ABSPATH' ) or die( 'No access outside of WordPress.' );

/*
Plugin Name: Article Mapper
Plugin URI:  http://iambicdesign.net/
Description: Gives publishers the ability to associate an article with a location, and tags that location on a map.
Version:     1.0
Author:      Eric Guerin
Author URI:  http://iambicdesign.net
License:     Apache
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: article-mapper
*/

/*
 Some pieces of this plugin respectfully re-used code from the Geolocation Plus Plugin 
 http://wordpress.org/extend/plugins/geolocation-plus/
 Specifically, the javascript geocode, as well as the clean_coordinate() and reverse_geocode() functions
*/

/**
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

add_action('save_post', 'article_location_save_post', 10, 2);
add_action('the_content', 'article_location_show_map');
wp_enqueue_script("jquery");
admin_init();

function admin_init() {
	add_action('admin_head-post-new.php', 'admin_head');
	add_action('admin_head-post.php', 'admin_head');
	add_action("add_meta_boxes", "add_article_location_meta_box");
}

function admin_head() {
	global $post;
	$post_id = $post->ID;
	$post_type = $post->post_type;
	?>
		<script type="text/javascript" src="http://www.google.com/jsapi"></script>
		<script type="text/javascript" src="http://maps.google.com/maps/api/js"></script>
		<script type="text/javascript">
		 	var $j = jQuery.noConflict();
			$j(function() {
				$j(document).ready(function() {					
					$j("#article-location-submit-address").click(function(){
						if($j("#article-location-address").val() != '') {
							currentAddress = $j("#article-location-address").val();
							geocode(currentAddress);
						}
					});
					
					function geocode(address) {
						var geocoder = new google.maps.Geocoder();
					    if (geocoder) {
							geocoder.geocode({"address": address}, function(results, status) {
								if (status == google.maps.GeocoderStatus.OK) {
									$j("#article-location-latitude").val(results[0].geometry.location.lat);
									$j("#article-location-longitude").val(results[0].geometry.location.lng);
								} else {
									alert('There was a problem with the geocoder.');
								}
							});
						}
					}
				});
			});
		</script>
	<?php
}

function article_location_markup($post) {
	wp_nonce_field(basename(__FILE__), "article-location-nonce");
?>
	<div>
		<label for="article-location-address">Enter Address:</label>
		<input name="article-location-address" id="article-location-address" type="text" value="<?php echo get_post_meta($post->ID, "article-location-address", true); ?>" style="width:99%;">
	</div>
	<div>
		<input type="button" name="article-location-button" id="article-location-submit-address" value="Get Location" class="button right" />
	</div>
	<div id="article_location_lat_long" class="rwmb-field rwmb-input-wrapper">
		<div class="rwmb-label">
			<label for="article-location-latitude">Latitude</label>
		</div>
		<div class="rwmb-input">
			<input name="article-location-latitude" id="article-location-latitude" type="text" value="<?php echo get_post_meta($post->ID, "latitude", true); ?>" />
		</div>
		<div class="rwmb-label">
			<label for="article-location-longitude">Longitude</label>
		</div>
		<div class="rwmb-input">
			<input name="article-location-longitude" id="article-location-longitude" type="text" value="<?php echo get_post_meta($post->ID, "longitude", true); ?>" />
		</div>
	</div>
	<?php
}

function add_article_location_meta_box() {
    add_meta_box("article-location", "Article Location", "article_location_markup", "post", "side", "high", null);
}

function article_location_save_post($post_id, $post) {
    if (!isset($_POST["article-location-nonce"]) || !wp_verify_nonce($_POST["article-location-nonce"], basename(__FILE__)))
        return $post_id;

    if(!current_user_can("edit_post", $post_id))
        return $post_id;

    if(defined("DOING_AUTOSAVE") && DOING_AUTOSAVE)
        return $post_id;

    $slug = "post";
    if($slug != $post->post_type)
        return $post_id;

  	$latitude = '';
  	$longitude = '';

    if(isset($_POST["article-location-latitude"])) {
		$latitude = clean_coordinate($_POST['article-location-latitude']);
    }

    if(isset($_POST['article-location-longitude'])) {
    	$longitude = clean_coordinate($_POST['article-location-longitude']);
    }

    if((clean_coordinate($latitude) != '') && (clean_coordinate($longitude)) != '') {
    	$address = reverse_geocode($latitude, $longitude);
    	if ( ! add_post_meta( $post_id, 'latitude', $latitude, true ) )
		   update_post_meta ( $post_id, 'latitude', $latitude );
    	if ( ! add_post_meta( $post_id, 'longitude', $longitude, true ) )
		   update_post_meta ( $post_id, 'longitude', $longitude );
    	if ( ! add_post_meta( $post_id, 'address', $address, true ) )
		   update_post_meta ( $post_id, 'address', $address );
    }
}

function clean_coordinate($coordinate) {
	$pattern = '/^(\-)?(\d{1,3})\.(\d{1,15})/';
	preg_match($pattern, $coordinate, $matches);
	return $matches[0];
}

function reverse_geocode($latitude, $longitude) {
	$url = "http://maps.google.com/maps/api/geocode/json?latlng=".$latitude.",".$longitude;
	$result = wp_remote_get($url);
	$json = json_decode($result['body']);
	foreach ($json->results as $result)	{
		foreach($result->address_components as $addressPart) {
			if((in_array('locality', $addressPart->types)) && (in_array('political', $addressPart->types)))
	    		$city = $addressPart->long_name;
	    	else if((in_array('administrative_area_level_1', $addressPart->types)) && (in_array('political', $addressPart->types)))
	    		$state = $addressPart->long_name;
	    	else if((in_array('country', $addressPart->types)) && (in_array('political', $addressPart->types)))
	    		$country = $addressPart->long_name;
		}
	}
	
	if(($city != '') && ($state != '') && ($country != ''))
		$address = $city.', '.$state.', '.$country;
	else if(($city != '') && ($state != ''))
		$address = $city.', '.$state;
	else if(($state != '') && ($country != ''))
		$address = $state.', '.$country;
	else if($country != '')
		$address = $country;
		
	return $address;
}

function article_location_show_map($content) {
	$page_content = $content;
	if(stristr($content, '{{map}}')) {
		$map_data_file = 'map-data.php';
		if(!empty($_GET['location'])) {
			$map_data_file .= '?location=' . $_GET['location'];
			add_filter('the_title', 'add_location_to_title', 10, 2);
		}

		wp_enqueue_script( 'google-api', 'https://maps.googleapis.com/maps/api/js');
	    wp_enqueue_script( 'article-location-data', plugin_dir_url(__FILE__) . 'js/' . $map_data_file, array(), '1.0.0' );
		wp_enqueue_script( 'map-script', plugin_dir_url(__FILE__) . 'js/map.js', array(), '1.0.0' );
		wp_enqueue_style( 'map-styles', plugin_dir_url(__FILE__) . 'map.css', array(), '1.0.0' );
		$map_content = '<div id="map-container"><div id="map-canvas" style="width:100%; height:600px;"></div></div>';
    	$page_content = str_replace( '{{map}}', $map_content, str_replace('{{cat-list}}', show_article_location_by_category(), $content) );
	}

	return $page_content;
}

function show_article_location_by_category() {
	$category_list = '';

	$categories = get_categories();
	if(!empty($categories)) {
		$category_list .= '<ul class="article-location-categories">';
		foreach($categories as $category) {
			if(!empty($_GET['location']) && $_GET['location'] == $category->slug) $current = ' class="current-category-slug"'; else $current = '';
			$category_list .= '<li class="article-location-category"><a' . $current . ' href="' . get_permalink() . '?location=' . $category->slug . '">' . 
			$category->name . '</a></li>';
		}

		$category_list .= '</ul>';
		if(!empty($_GET['location'])) $category_list .= '<h5 style="clear:both;"><a href="' . get_permalink() . '">(Remove Location Filter)</a></h5>';
	}

	return $category_list;
}

function add_location_to_title($title, $id) {
	if($id == get_the_ID()) {
		$title = ucwords($_GET['location']) . ' ' . $title;
	}
	return $title;
}