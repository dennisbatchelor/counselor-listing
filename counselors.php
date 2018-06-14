<?php
/**
 * @package Counselors_Custom
 * @version 0.1
 */
/*
Plugin Name: Counselors Custom
Plugin URI: http://wordpress.dennisbatchelor.org/plugins/counselor-listing/
Description: This is just a plugin to encapsulate the custom code needed to display the practitioners list on Wordpress
Author: Dennis Batchelor
Version: 0.1
Author URI: http://dennisbatchelor.com/
*/

// This displays the count of certified practitioners
function counselors_practitioner_count() {
	$wpdb = $GLOBALS['wpdb'];
	$wlm_user_levels = sprintf("%swlm_userlevels", $wpdb->prefix);
	$querystr = "SELECT COUNT(user_id) AS count FROM $wlm_user_levels WHERE level_id=%d";
	$results = $wpdb->get_results( $wpdb->prepare( $querystr, 1446497128 ), ARRAY_A );
	$count = $results[0]['count'];
	echo "<p id='counselors'>Certified Counselors Practitioners: $count</p>";
}

// Now we set that function up to execute when the admin_notices action is called
add_action( 'admin_notices', 'counselors_practitioner_count' );




// We need some CSS to position the paragraph
function counselors_css() {
	// This makes sure that the positioning is also good for right-to-left languages
	$x = is_rtl() ? 'left' : 'right';

	echo "
	<style type='text/css'>
	#counselors {
		float: $x;
		padding-$x: 15px;
		padding-top: 5px;		
		margin: 0;
		font-size: 11px;
		color: #a36684;
	}
	</style>
	";
}

add_action( 'admin_head', 'counselors_css' );


// Class to handle the case where the practitioners table will appear on only 1 page
// So, include the appropriate javascript on just that page (or wherever the shortcode = practitioners_list
//  is placed. This is all so that the table display, sorting, and pagination can be loaded only when needed
class Counselors_Shortcode {
	static $add_script;

	static function init() {
		add_shortcode('counselors_practitioner_list', array(__CLASS__, 'handle_shortcode'));

		add_action('init', array(__CLASS__, 'register_script'));
		add_action('wp_footer', array(__CLASS__, 'print_script'));
	}

	static function register_script() {
		$script1 = 'jquery_tablesorter/jquery.tablesorter.min.js';
		$script2 = 'jquery_tablesorter/addons/pager/jquery.tablesorter.pager.js';
		$script3 = 'custom_counselors.js';
		$style1 = '/jquery_tablesorter/css/style.css';
		wp_register_script('tablesorter', plugins_url($script1, __FILE__), array('jquery'), false, true);
		wp_register_script('tablesorter-pager', plugins_url($script2, __FILE__), array('tablesorter'), false, true);
		wp_register_script('custom-counselors', plugins_url($script3, __FILE__), array('tablesorter'), false, true);
		// Register and enqueue style sheet
		wp_register_style('tablesorter', plugins_url($style1, __FILE__), array(), false, 'all');
		wp_enqueue_style( 'tablesorter' );
	}

	static function print_script() {
		if ( ! self::$add_script )
			return;

		wp_print_scripts('tablesorter');
		wp_print_scripts('tablesorter-pager');
		wp_print_scripts('custom-counselors');
	}


	static function handle_shortcode($atts) {
		self::$add_script = true;

		// actual shortcode handling here
		return counselors_practitioner_list_func($atts);
	}


}

Counselors_Shortcode::init();

	function counselors_certified_members_func( $state, $country ) {
          $wpdb = $GLOBALS['wpdb'];
          $wlm_user_options = sprintf("%swlm_user_options", $wpdb->prefix);
          $wlm_user_levels = sprintf("%swlm_userlevels", $wpdb->prefix);
          $querystr =
               "SELECT u.ID,u.user_login,u.user_email AS email,u.user_url as website,u.display_name,m.meta_value AS first_name,
                 ml.meta_value AS last_name,wp.option_value AS phone, wfi.option_value AS fee_info,
                 wsp.option_value AS public,
                 wua.option_value AS address
                FROM $wpdb->users u
                RIGHT JOIN $wlm_user_options wpu ON u.ID=wpu.user_id AND wpu.option_value LIKE 'Yes' AND wpu.option_name='custom_public'
                JOIN $wpdb->usermeta m ON u.ID = m.user_id AND m.meta_key='first_name'
                JOIN $wpdb->usermeta ml ON u.ID = ml.user_id AND ml.meta_key='last_name'
                JOIN $wlm_user_options wp ON u.ID=wp.user_id AND wp.option_name='custom_phone'
                JOIN $wlm_user_options wua ON u.ID=wua.user_id AND wua.option_name='wpm_useraddress'
                JOIN $wlm_user_options wfi ON u.ID=wfi.user_id AND wfi.option_name='custom_fee_info'
                JOIN $wlm_user_options wsp ON u.ID=wsp.user_id AND wsp.option_name='custom_public'
                WHERE u.ID IN (SELECT user_id FROM $wlm_user_levels WHERE level_id=%d) ORDER BY ml.meta_value";
          $results = $wpdb->get_results( $wpdb->prepare( $querystr, 1446497128 ), ARRAY_A );
          return $results;
     }

	// shortcode [splanna_practitioner_list state="CO"]
	function counselors_practitioner_list_func( $atts ) {
          $a = shortcode_atts( array(
               'state' => 'all',
               'country' => 'all',
               'details_url' => '/practitioner-portal/practitioner-details/'
          ), $atts );

          //$members = wlmapi_the_level_members(1446497128); // certified level = 1446497128
          $members = counselors_certified_members_func( 'CO', 'all' );

          $output = '';
          if ( $members ) {
            foreach( $members as $member_info ) {  //First element in the returned array
		$address = maybe_unserialize( $member_info['address'] );
		$output .= '<tr>';
		$output .= '<td><a href="' . $a[details_url] . "?uid=" . $member_info['ID'] . '">';
		$output .= $member_info['display_name'] . '</td>';
		$output .= '<td>' . $address['city']  . '</td>';
		$output .= '<td>' . $address['state']  . '</td>';
		if( preg_match( '/^United States/',$address['country'] ) ) {
			$output .= '<td>US</td>';
		} else {
			$output .= '<td>' . $address['country']  . '</td>';
		}
		$output .= '<td>' . $address['zip']  . '</td>';
		$output .= '<td>' . $member_info['phone']  . '</td>';
		$output .= '</tr>';
            }
          } else {
               $output = "Error<br>";
               print_r($members);
          }
          return $output;
	}
	//add_shortcode( 'counselors_practitioner_list', 'counselors_practitioner_list_func' );

     function get_counselors_practitioner_detail ( $id ) {
          $wpdb = $GLOBALS['wpdb'];
          $wlm_user_options = sprintf("%swlm_user_options", $wpdb->prefix);
          $querystr =
               "SELECT u.user_login,u.user_email AS email,u.user_url as website,u.display_name,m.meta_value AS first_name,
                 ml.meta_value AS last_name,wua.option_value AS address,wp.option_value AS phone, wfi.option_value AS fee_info,
                 wi.option_value AS insurance,wsp.option_value AS public,wpd.option_value AS description,
                 wad.option_value AS advanced_degree, wpu.option_value AS public
                FROM $wpdb->users u JOIN $wpdb->usermeta m ON u.ID = m.user_id AND m.meta_key='first_name'
                JOIN $wpdb->usermeta ml ON u.ID = ml.user_id AND ml.meta_key='last_name'
                JOIN $wlm_user_options wp ON u.ID=wp.user_id AND wp.option_name='custom_phone'
                JOIN $wlm_user_options wua ON u.ID=wua.user_id AND wua.option_name='wpm_useraddress'
                JOIN $wlm_user_options wpu ON u.ID=wpu.user_id AND wpu.option_name='custom_public'
                JOIN $wlm_user_options wfi ON u.ID=wfi.user_id AND wfi.option_name='custom_fee_info'
                JOIN $wlm_user_options wi ON u.ID=wi.user_id AND wi.option_name='custom_insurance'
                JOIN $wlm_user_options wsp ON u.ID=wsp.user_id AND wsp.option_name='custom_public'
                JOIN $wlm_user_options wpd ON u.ID=wpd.user_id AND wpd.option_name='custom_practitioner_description'
                JOIN $wlm_user_options wad ON u.ID=wad.user_id AND wad.option_name='custom_advanced_degree'
                WHERE u.ID=%d";
          $results = $wpdb->get_results( $wpdb->prepare( $querystr, $id ), ARRAY_A );

          $member_info = $results[0]; //First element in the returned array
          if ( preg_match( '/^Y/',$member_info['public'] ) ) {
		$address = maybe_unserialize( $member_info['address'] );
		$output = '<table class="practitioners-table"><tbody>';
		$output .= '<tr><td><strong>Name</strong></td><td>' . $member_info['display_name'] . '</td></tr>';
		$output .= '<tr><td><strong>Advanced Degrees</strong></td><td>' . $member_info['advanced_degree'] . '</td></tr>';
		$output .= '<tr><td><strong>Company</strong></td><td>' . $address['company'] . '</<tr>';
		$output .= '<tr><td><strong>Website or<br>Social Media</strong></td><td><a href="';
		$output .= $member_info['website'] . '">' . $member_info['website'] . '</a></<tr>';
		$output .= '<tr><td><strong>Description</strong></td><td>' . $member_info['description'] . '</<tr>';
		$output .= '<tr><td><strong>Address</strong></td><td>' . $address['address1'] . '</<tr>';
		$output .= '<tr><td><strong>City</strong></td><td>' . $address['city'] . '</<tr>';
		$output .= '<tr><td><strong>State</strong></td><td>' . $address['state'] . '</<tr>';
		$output .= '<tr><td><strong>Zip Code</strong></td><td>' . $address['zip'] . '</<tr>';
		$output .= '<tr><td><strong>Country</strong></td><td>' . $address['country'] . '</<tr>';
		$output .= '<tr><td><strong>Phone</strong></td><td>' . $member_info['phone'] . '</<tr>';
		$output .= '<tr><td><strong>Email</strong></td><td>' . $member_info['email'] . '</<tr>';
		$output .= '<tr><td><strong>Fee Info</strong></td><td>' . $member_info['fee_info'] . '</<tr>';
		$output .= '<tr><td><strong>Insurance</strong></td><td>' . $member_info['insurance'] . '</<tr>';
		$output .= '</tbody></table>';
		} else {
			$output = "Error<br>";
			print_r($member_info);
		}
		return $output;
     }

     function counselors_practitioner_detail_func ( $id=6454 ) {
          $uid = isset( $_REQUEST['uid'] ) ? $_REQUEST['uid'] : $id[0];
          return get_counselors_practitioner_detail( $uid );
     }

     add_shortcode( 'counselors_practitioner_detail', 'counselors_practitioner_detail_func' );

     function edit_counselors_practitioner_detail ( $upd ) {
/*
          $wpdb = $GLOBALS['wpdb'];
          $wlm_user_options = sprintf("%swlm_user_options", $wpdb->prefix);
          $querystr =
               "SELECT u.user_login,u.user_email AS email,u.user_url as website,u.display_name,m.meta_value AS first_name,
                 ml.meta_value AS last_name,wua.option_value AS address,wp.option_value AS phone, wfi.option_value AS fee_info,
                 wi.option_value AS insurance,wsp.option_value AS public,wpd.option_value AS description,
                 wad.option_value AS advanced_degree, wpu.option_value AS public
                FROM $wpdb->users u JOIN $wpdb->usermeta m ON u.ID = m.user_id AND m.meta_key='first_name'
                JOIN $wpdb->usermeta ml ON u.ID = ml.user_id AND ml.meta_key='last_name'
                JOIN $wlm_user_options wp ON u.ID=wp.user_id AND wp.option_name='custom_phone'
                JOIN $wlm_user_options wua ON u.ID=wua.user_id AND wua.option_name='wpm_useraddress'
                JOIN $wlm_user_options wpu ON u.ID=wpu.user_id AND wpu.option_name='custom_public'
                JOIN $wlm_user_options wfi ON u.ID=wfi.user_id AND wfi.option_name='custom_fee_info'
                JOIN $wlm_user_options wi ON u.ID=wi.user_id AND wi.option_name='custom_insurance'
                JOIN $wlm_user_options wsp ON u.ID=wsp.user_id AND wsp.option_name='custom_public'
                JOIN $wlm_user_options wpd ON u.ID=wpd.user_id AND wpd.option_name='custom_practitioner_description'
                JOIN $wlm_user_options wad ON u.ID=wad.user_id AND wad.option_name='custom_advanced_degree'
                WHERE u.ID=%d";
          $results = $wpdb->get_results( $wpdb->prepare( $querystr, $id ), ARRAY_A );

          $member_info = $results[0]; //First element in the returned array
          if ( preg_match( '/^Y/',$member_info['public'] ) ) {
*/
		$user_info = wp_get_current_user();
		$member_info = wlmapi_get_member($user_info->ID);
		$info = $member_info['member'][0]['UserInfo'];

		$s = '<table border="0" width="60%"> <tbody>';
		$s .= '<form action="' . $upd . '" method="post" class="wpcf7-form" novalidate="novalidate"';
		$s .= '<tr> <td width="150"><strong>Practitioner Level:</strong></td> <td>';
		$count = 0;
		foreach ( $member_info['member'][0]['Levels'] as $level ) {
			if ( $count++ ) { $s .= ', '; }
			$s .= $level->Pending ? '<span style="text-decoration: line-through;">' : '';
			$s .= $level->Name;
			$s .= $level->Pending ? '</span">' : '';
		}
		$s .= '</td> </tr>';
		$s .= '<tr> <td><strong>Username:</strong></td> <td>' . $info['user_login'] . '</td> </tr>';
		$s .= '<tr> <td><strong>Display Name:</strong></td> <td><input type="text" name="displayname" ';
		$s .= 'value="' . $info['display_name'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Advanced Degrees:</strong></td> <td><input type="text" name="advanced_degrees" ';
		$s .= 'value="' . $info['custom_advanced_degrees'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Company:</strong></td> <td><input type="text" name="company" ';
		$s .= 'value="' . $info['wpm_useraddress']['company'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Website or Social Media:</strong></td> <td><input type="text" name="website" value="';
		$s .= $info['user_url'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Description:</strong></td> <td><textarea name="practitioner_description" cols="65" rows="10" ';
		$s .= 'class="wpcf7-form-control wpcf7-textarea">' . $info['custom_practitioner_description'] . '</textarea></td> </tr>';
		$s .= '<tr> <td><strong>City:</strong></td> <td><input type="text" name="city" value="';
		$s .= $info['wpm_useraddress']['city'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>State:</strong></td> <td><input type="text" name="state" value="';
		$s .= $info['wpm_useraddress']['state'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Zip Code:</strong></td> <td><input type="text" name="zip" value="';
		$s .= $info['wpm_useraddress']['zip'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Country:</strong></td> <td><input type="text" name="country" value="';
		$s .= $info['wpm_useraddress']['country'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Phone:</strong></td> <td><input type="text" name="phone" value="';
		$s .= $info['custom_phone'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Display Email:</strong></td> <td><input type="text" name="display_email" value="';
		$s .= $info['custom_display_email'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Fee Info:</strong></td> <td><input type="text" name="fee_info" value="';
		$s .= $info['custom_fee_info'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Insurance:</strong></td> <td><input type="text" name="insurance" value="';
		$s .= $info['custom_insurance'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><strong>Show Publicly:</strong></td> <td><input type="text" name="public" value="';
		$s .= $info['custom_public'] . '" size="30" class="wpcf7-form-control wpcf7-text"></td> </tr>';
		$s .= '<tr> <td><input type="submit" value="Update Profile" class="wpcf7-form-control wpcf7-submit ';
		$s .= 'fusion-button button-large button-default"></td> <td></td> </tr> </form></tbody> </table>';

		return $s;
     }

     function edit_counselors_practitioner_detail_func ( $atts ) {
          $a = shortcode_atts( array(
               'upd' => '/practitioner-portal/update-practitioner-details/'
          ), $atts );


	if ( is_user_logged_in() ) {
		$uid = wp_get_current_user()->ID;
		return edit_counselors_practitioner_detail( $a[upd] );
	} else {
		return "<h3>You must be logged in to access this feature</h3>";
	}
     }

     add_shortcode( 'edit_counselors_practitioner_detail', 'edit_counselors_practitioner_detail_func' );

     function update_counselors_practitioner_detail_func ( $atts ) {

	if ( is_user_logged_in() && $_POST && isset( $_POST['displayname'] ) ) {
		$uid = wp_get_current_user()->ID;
		$args = array(
			'display_name' => $_POST['displayname'],
			'custom_advanced_degrees' => $_POST['advanced_degrees'],
			'company' => $_POST['company'],
			'user_url' => $_POST['website'],
			'custom_practitioner_description' => $_POST['practitioner_description'],
			'city' => $_POST['city'],
			'state' => $_POST['state'],
			'zip' => $_POST['zip'],
			'country' => $_POST['country'],
			'custom_phone' => $_POST['phone'],
			'custom_display_email' => $_POST['display_email'],
			'custom_fee_info' => $_POST['fee_info'],
			'custom_insurance' => $_POST['insurance'],
			'custom_public' => $_POST['public']
		);
		$member = wlmapi_update_member(wp_get_current_user()->ID, $args);
		//return counselors_practitioner_detail_func ( wp_get_current_user()->ID );
		print "<h2>Practitioner Profile updated</h2>";
	} else {
		print "<h2>Error</h2>";
	}
	return;
     }

     add_shortcode( 'update_counselors_practitioner_detail', 'update_counselors_practitioner_detail_func' );


