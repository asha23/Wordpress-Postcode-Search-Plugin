<?php

/*
Plugin Name: Fuzzy Postcode Search Shortcode
Plugin URI: 
Description: Uses the shortcode ['user_search'] - This was designed to be used only with the current APCI theme. It could of course be modified for use with any website.
Author: Ash Whiting
Version: 1.0
Author URI: http://ashwhiting.com
*/

/* 
	API Classes 
	We are currently using the postcode.io and ipstack.com API's to calculate distances from the user location

*/

require_once(dirname(__FILE__) . '/classes/postcode.io.class.php');
require_once(dirname(__FILE__) . '/classes/ipstack.class.php');

// Admin menu
require_once(dirname(__FILE__) . '/inc/admin.menu.php');

add_shortcode('user_search','custom_postcode_search');

// Do the search
// =====================================

function custom_postcode_search($atts = null){

	$out = user_search_form();

	if (isset($_GET['user_search']) && $_GET['user_search'] == "search_users"):

		global $wpdb;

		$metakey = $_GET['search_by'];
		$language_key = $_GET['lang_value'];
		$city_key = $_GET['city_value'];
		$county_key = $_GET['county_value'];
		$postcode_key = $_GET['postcode_value'];

		/* 
			Get Geolocation from ip
		*/ 

		// Get user ip
		$ip = $_SERVER['HTTP_CLIENT_IP']? $_SERVER['HTTP_CLIENT_IP'] : ($_SERVER['HTTP_X_FORWARDE‌​D_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);

		$ips = new ipsWrapper(); 

		$ips->setEndPoint('api'); 

		$ips->ipnum = $ip; 

		$ips->getResponse(); 

		$ip_num = $ips->ipnum;
		$ip_phone = $ips->response->country_name;
		$ip_region = $ips->response->region_name;
		$ip_city = $ips->response->city;
		$ip_zip = $ips->response->zip;

		$ips->resetParams(); 
		$ips->setEndPoint('check'); 
		$ips->setParam('fields','main'); 
		$ips->getResponse(); 

		$originating_ip = $ips->response->ip;

		$pc = $ips->response->zip;

		// Do the search
		// =====================================

		$args = array(
			'meta_query' => array(
				'relation' => 'AND',
			
				array (
					'key' => 'main_post_code',
					'value' => $postcode_key,
					'compare' => 'LIKE'
				),
				array (
					'key' => 'languages_spoken',
					'value' => $language_key,
					'compare' => 'LIKE'
				)
			)
		);

		$user_query = new WP_User_Query($args ); 

		$users = $user_query->get_results(); 

		$out .= '<div class="search-results">';
		$out .= '<hr/>';

		if($users && $language_key != ""):
			foreach($users as $user):

				/*  
					Use Google to work out the distance between 2 postcodes (by road)
				*/

				$first_name = $user->first_name;
				$last_name = $user->last_name;
				$city = $user->main_city;
				$post_code = $user->main_post_code;
				$miles_to_travel = $user->miles_prepared_to_travel;

				$result = array();

				// Calculate the postcode distances
				// This uses postcode.io.class.php 

				$postcode = new Postcode();
				$distance = $postcode->distance($pc, $post_code, "M");

				$distance = round($distance);

				// Calculate if this user will travel that far.

				if($miles_to_travel > $distance):
					$addition = $distance . ' miles.';
				else:
					$addition = '<span style="color:red"><i class="fas fa-exclamation-circle"></i> ' . $distance . ' miles.</span><br><span class="tiny">This member has specified that they will only travel ' . $miles_to_travel . ' miles from their location.</span>';
				endif;

				$url = get_author_posts_url($user->ID);

				$lang_string = $user->languages_spoken;

				$lang_string = str_replace('["', '',  $lang_string);
				$lang_string = str_replace('"', '',  $lang_string);
				$lang_string = str_replace(']', '',  $lang_string);
				$lang_string = str_replace(',', ', ',  $lang_string);

				$lang_string = str_replace($language_key, "<strong>" . $language_key . "</strong>", $lang_string);

				$out .= '<div class="result">';
				$out .= '<h3><a href="' . $url . '">' . $first_name . ' ' .  $last_name . '</a></h3>';
				$out .= '<p><strong>Town/City: </strong>' . $city . '</p>';
				$out .= '<p><strong>Languages spoken: </strong>' . $lang_string  . '</p>';
				$out .= '<p><strong>Distance from you: </strong>' . $addition . '</p>';
				$out .=	'</div>';
				$out .= '<div class="address">';
				$out .= '<a href="' . $url . '" class="btn btn-default">View more details</a>';
				$out .= '</div>';
				$out .= '<hr/>';

			endforeach;
		else:
			$out .= '<div class="result">No results, please search again.</div>';
		endif;

	else: 
		$out .= '<div class="result">No results, please search again.</div>';
	endif;
	return $out;
}

// Display user search form
// =====================================

function user_search_form() {

	/* 
		Get the language fields (prepare a dropdown for this)
		Although it's probably better to cross reference languages
		with users.

		We do that as well. So this call is depricated at the moment
	*/

	global $wpdb;

	$lang_src = GFAPI::get_form(1);
	$language_array = $lang_src['fields'][9]['choices'];


	// Get the county fields (This is from all activated users)

	$all_user_query = new WP_User_Query( array('meta_key' => 'main_county', 
												'meta_value' => '' , 
												'fields' => 'all' ) ); 
	$all_users = $all_user_query->get_results(); 

	/*
		Set up a data object for each user 
		Just return the required fields we need for the search

		We can then parse this to set up our search dropdowns with
		language presets that already exist, rather than having too many 
		not found results.
	*/

	foreach($all_users as $user):
		
		$user_id = $user->ID;
		$first_name = $user->first_name;
		$last_name = $user->last_name;
		$county = $user->main_county;
		$city = $user->main_city;
		$postcode = $user->main_post_code;
		$language_list[] = $user->languages_spoken;

		// Get the language list based on all current users.

		foreach($language_list as $lang):
			
			$lang_string = $lang;
	
			// Do some string manipulation - WP adds the countries in an odd string format.

			$lang_string = str_replace('["', '',  $lang_string);
			$lang_string = str_replace('"', '',  $lang_string);
			$lang_string = str_replace(']', '',  $lang_string);
			$lang_explode[] = explode(',', $lang_string);
			
			foreach($lang_explode as $langs):
				$lang_output = $langs;
			endforeach;

			$language_list = array_unique($lang_output);
			
		endforeach;
	
		// Create an array with the user id as key

		$user_output[] = array(
			'user_id' => $user_id,
			'url' => get_author_posts_url($user_id),
			'name' => $first_name . " " . $last_name,
			'city' => $city,
			'county' => $county,
			'postcode' => $postcode,
			'languages' => $language_list
		);
		
	endforeach;

	// Do the form
	// Create the languages dropdown data

	foreach($user_output as $lang_output) :
		$languages[] = $lang_output['languages'];
	endforeach;

	$languages = array_merge(...$languages);
	$languages = array_unique($languages);

	$metavalue = $metakey = '';

	// Not currently used

	// foreach($user_output as $city_output):
	// 	$cities[] = $city_output['city'];
	// endforeach;

	// foreach($user_output as $county_output):
	// 	$counties[] = $county_output['county'];
	// endforeach;

	// ------------------

	if (isset($_GET['search_by'])) :
		$metakey = $_GET['search_by'];
	endif;

	if (isset($_GET['postcode_value'])) :
		$metavalue_postcode = $_GET['postcode_value'];
	endif;

	if (isset($_GET['lang_value'])) :
		$metavalue_lang = $_GET['lang_value'];
	endif;

	// if (isset($_GET['city_value'])) :
	// 	$metavalue_city = $_GET['city_value'];
	// endif;

	// if (isset($_GET['county_value'])) :
	// 	$metavalue_county= $_GET['county_value'];
	// endif;

	$re = '
		<div class="fs_user_search">
			<h2><i class="fa fa-search"></i> Find an interpreter</h2>
			<form action="" name="user_s" method="get">
				<div class="form-group">

					<label for="lang_value">By Language:</label>
						<select id="lang_value" name="lang_value" class="form-control">';
							$re .= '<option value="">Please select a language</option>';
								foreach($languages as $langs):
									$re .= ($metavalue_lang == $langs) ? '<option value="' . $langs . '" selected="selected">' . $langs . '</option>': '<option value="' . $langs . '">' . $langs . '</option>';
								endforeach;
							$re .= '</select>

					<label for="s_value">By Postcode:</label>

						<input id="postcode_value" name="postcode_value" type="text" value="' . $metavalue_postcode . '" class="form-control"/>
						<input name="user_search" id="user_search" type="hidden" value="search_users"/>
						<input id="submit" type="submit" value="Search" class="search-btn btn btn-default "/>
				</div>
			</form>
		</div>';
	return $re;

}