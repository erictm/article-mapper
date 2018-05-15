<?php
/**
 * Pulls articles from WP and converts their contents
 * into the correct JS to implement with google Maps API
 */

require($_SERVER['DOCUMENT_ROOT'].'/wp-blog-header.php');
$args = array(
	'orderby' => 'title',
	'order'   => 'DESC',
	'nopaging' => true,
	'meta_query' => array(
		array(
			'key'     => 'latitude',
			'key'     => 'longitude',
		),
	)
);

if(!empty($_GET['location'])) {
	$args['category_name'] = $_GET['location'];
}

$query = new WP_Query( $args );
$post_data = array( 'posts' => array() );
$i = 0;
while($query->have_posts()) {
	$query->the_post();
	$latitude = get_post_meta(get_the_id(), 'latitude', true);
	$longitude = get_post_meta(get_the_id(), 'longitude', true);
	if(empty($latitude) || empty($longitude)) continue;
	$image_url = '';
	$image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_id() ), 'single-post-thumbnail' );
	if(!empty($image)) {
		$image_url = $image[0];
	}
	$post_data['posts'][$i]['post_id'] = get_the_id();
	$post_data['posts'][$i]['latitude'] = $latitude;
	$post_data['posts'][$i]['longitude'] = $longitude;
	$post_data['posts'][$i]['post_title'] = get_the_title();
	$post_data['posts'][$i]['permalink'] = get_the_permalink();
	$post_data['posts'][$i]['image_url'] = $image_url;
	$post_data['posts'][$i]['excerpt'] = get_the_excerpt();
	$i++;
}

// for debugging only
$post_data['count'] = $i;
if(!empty($_GET['print'])) print_r($post_data);

echo 'var articleLocationData = ' . json_encode($post_data);