<?php
/**
* Jomres CMS Agnostic Plugin
* @author  John m_majma@yahoo.com
* @version Jomres 9 
* @package Jomres
* @copyright	2005-2020 Vince Wooll
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

/*

SRPs only, set dates available/not available

*/

Flight::route('PUT /cmf/property/availability', function()
	{
    require_once("../framework.php");

	$_PUT = $GLOBALS['PUT']; // PHP doesn't allow us to use $_PUT like a super global, however the put_method_handling.php script will parse form data and put it into PUT, which we can then use. This allows us to use PUT for updating records (as opposed to POST which is, in REST APIs used for record creation). This lets us maintain a consistent syntax throughout the REST API.

	validate_scope::validate('channel_management');
	cmf_utilities::validate_channel_for_user();  // If the user and channel name do not correspond, then this channel is incorrect and can go no further, it'll throw a 204 error

	$property_uid			= (int)$_PUT['property_uid'];
	$date_sets				= json_decode(stripslashes($_PUT['availability']));

	cmf_utilities::validate_property_uid_for_user($property_uid);
	
	// First, we need to find out if this property is an SRP. If not, we cannot continue
	$mrConfig = getPropertySpecificSettings($property_uid);
	if ($mrConfig['singleRoomProperty'] == "0") {
		Flight::halt(204, "Property is not Villa/Apartment type property, cannot set availability using this endpoint.");
	}
	
	if (empty($date_sets)) {
		Flight::halt(204, "No dates sent to set availability.");
	}
	
	$call_self = new call_self( );

	$elements = array(
		"method"=>"GET",
		"request"=>"cmf/property/list/blackbookings/".$property_uid,
		"data"=>array(),
		"headers" => array ( Flight::get('channel_header' ).": ".Flight::get('channel_name') , "X-JOMRES-proxy-id: ".Flight::get('user_id') )
		);
			
	$response = json_decode(stripslashes($call_self->call($elements)));

	$black_bookings_contract_uids = array();
	if (isset($response->data->response) && !empty($response->data->response->blackbooking_ids)) {
		 $black_bookings_contract_uids = $response->data->response->blackbooking_ids;
	}
	
	// Find all bookings that haven't been cancelled, rejected, completed and sort them into two arrays, normal bookings and black bookings. We'll do this as a query here instead of using the api because it's quicker
 	$today = date("Y-m-d");
	$query = "SELECT `contract_uid` ,  `date_range_string`  FROM #__jomres_contracts WHERE `property_uid` = ".(int)$property_uid." AND DATE_FORMAT(`arrival`, '%Y-%m-%d') >= ".$today." AND `cancelled` != 1 AND rejected  != 1 AND bookedout != 1 ORDER BY arrival ASC    ";
	$all_bookings = doSelectSql($query);
	
	$normal_bookings = array();
	$black_bookings = array();
	foreach ($all_bookings as $existing_booking) {
		
		$date_range_string = str_replace ("/", "-" , $existing_booking->date_range_string);
		$date_range_array = explode ( "," , $date_range_string);
		
		$is_black_booking = false;
		
		if (in_array( $existing_booking->contract_uid , $black_bookings_contract_uids )) {
			$is_black_booking = true;
		}
		
		foreach ( $date_range_array as $date ) {
			$reformatted_date = str_replace ( "/" , "-" , $date);
			if ( $is_black_booking ) {
				$black_bookings[$reformatted_date]	= array ( "contract_uid" => $existing_booking->contract_uid , "is_black_booking" => $is_black_booking , "date_range_array"  => $date_range_array , "date" => $reformatted_date ) ;
			} else {
				$normal_bookings[$reformatted_date]	= array ( "contract_uid" => $existing_booking->contract_uid , "is_black_booking" => $is_black_booking , "date_range_array"  => $date_range_array , "date" => $reformatted_date) ;
			}
		}
	}

	// We can cancel black bookings, but not normal bookings so we'll go through each of the sets of dates, and find if any normal bookings exist for each time period
	
	foreach ( $date_sets as $date_set ) {
		
		if ( !cmf_utilities::validate_date($date_set->date_from) ) {
			Flight::halt(204, "Date from incorrect, must be in Y-m-d format");
			}
			
		if ( !cmf_utilities::validate_date($date_set->date_to) ) {
			Flight::halt(204, "Date to incorrect, must be in Y-m-d format");
			}
			
		$set_dates_as_available = (bool) $date_set->available;
		
		$dates_in_set = array_keys(cmf_utilities::get_date_ranges ( $date_set->date_from , $date_set->date_to ));

		// If "set_dates_as_available" is true, we will search for any black bookings in the date set and cancel them. We will not interfere with "normal" bookings
		if ($set_dates_as_available == true ) {
			
			// We need to find dates inside, and outside the range. Any dates outside the range need a new black booking to be created so that existing "not available" dates remain unavailable
			$black_booking_ids_in_date_set			= array();

			if (!empty($black_bookings)) {
				foreach ($black_bookings as $black_booking_date => $black_booking) {
					$black_booking_dates_outside_range = array_diff($black_booking['date_range_array'] , $dates_in_set);

 					$first_date_in_set = $dates_in_set[0];
					$last_date_in_set = end($dates_in_set);

					if ( 
						strtotime($black_booking_date) <= strtotime ($last_date_in_set) &&
						strtotime($black_booking_date) >= strtotime ($first_date_in_set)
						) { // We need to find any black bookings where one or more dates fall inside the date_set
							$black_booking_ids_in_date_set[] = array ( "contract_uid_to_cancel" => $black_booking["contract_uid"] , "dates_not_in_set_and_need_to_be_rebooked" => $black_booking_dates_outside_range );
					}
				
				
				}
			}

			$bookings_to_refactor = array_map('unserialize', array_unique(array_map('serialize', $black_booking_ids_in_date_set)));

			if (!empty($bookings_to_refactor)) {
				$black_booking_responses = array();
				foreach ($bookings_to_refactor as $refactor ) {
					
					$elements = array(
						"method"=>"DELETE",
						"request"=>"cmf/property/blackbooking/".$property_uid."/".$refactor['contract_uid_to_cancel'] ,
						"data"=>array(),
						"headers" => array ( Flight::get('channel_header' ).": ".Flight::get('channel_name') , "X-JOMRES-proxy-id: ".Flight::get('user_id') )
						);
			
					$deleted_blackbooking_response = json_decode(stripslashes($call_self->call($elements)));
					
					$grouped_dates = cmf_utilities::build_date_sets($refactor['dates_not_in_set_and_need_to_be_rebooked']);
					
					if ( $deleted_blackbooking_response->data->response == true && !empty($refactor['dates_not_in_set_and_need_to_be_rebooked']) ) {
						foreach ($grouped_dates as $group ) {
							$new_date_obj = new stdClass();
						
							$new_date_obj->date_from =$group[0];
							$new_date_obj->date_to = $group[1];
							$availability = json_encode($new_date_obj);
								
							$elements = array(
								"method"=>"PUT",
								"request"=>"cmf/property/blackbooking/",
								"data"=>array( "property_uid" => $property_uid , "availability" => $availability , "room_ids" => '[]' , "remote_booking_id" => '' ),
								"headers" => array ( Flight::get('channel_header' ).": ".Flight::get('channel_name') , "X-JOMRES-proxy-id: ".Flight::get('user_id') )
								);
				
							$black_booking_responses[] = json_decode(stripslashes($call_self->call($elements)));
						}
					}
				}
			}
			$response = array ( "success" => true );
			
		} else { // If "set_dates_as_available" is false then we'll need to search for any "normal" bookings and if they exist we can't set this date range as unavailable, we will return a message that there are already bookings for this date, the booking id and leave it to them to cancel the booking using another method. If there aren't any normal bookings we can go ahead and create a black booking.
			$response = array();
			
			$conflicting_booking_found = false;
			
			foreach ($dates_in_set as $date_in_set) {
				if ( array_key_exists ( $date_in_set , $normal_bookings) ) {
					$conflicting_booking_found = true;
				}
			}
			
			if ( !$conflicting_booking_found ) {
				// Find all existing black bookings and their dates, figure out the missing dates, and add them.
				
				$dates_to_blackbook = array();
				foreach ($dates_in_set as $date_in_set) {
					$date_found = false;
					foreach ( $black_bookings as $black_booking ) {
						if (in_array($date_in_set , $black_booking['date_range_array'])) {
							$date_found = true;
						}
					}
					if (!$date_found) {
						$dates_to_blackbook[] = $date_in_set;
					}
					
				}
				
				$grouped_dates = cmf_utilities::build_date_sets($dates_to_blackbook);
				if (!empty($grouped_dates)) {
					foreach ($grouped_dates as $group ) {
						$new_date_obj = new stdClass();
						
						$new_date_obj->date_from =$group[0];
						$new_date_obj->date_to = $group[1];
						$availability = json_encode($new_date_obj);
						
						$elements = array(
							"method"=>"PUT",
							"request"=>"cmf/property/blackbooking/",
							"data"=>array( "property_uid" => $property_uid , "availability" => $availability , "room_ids" => '[]' , "remote_booking_id" => '' ),
							"headers" => array ( Flight::get('channel_header' ).": ".Flight::get('channel_name') , "X-JOMRES-proxy-id: ".Flight::get('user_id') )
							);
							
						$black_booking_responses[] = json_decode(stripslashes($call_self->call($elements)));
					}
				}
				$response = array ( "success" => true );
			} else {
				$response = array ( "success" => false , "message" => "There are already bookings for these dates, can't mark them as unavailable again.");
			}
		}
	}
	
	
	Flight::json( $response_name = "response" , $response ); 
	});