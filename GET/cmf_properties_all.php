<?php
/**
* Jomres CMS Agnostic Plugin
* @author  John m_majma@yahoo.com
* @version Jomres 9 
* @package Jomres
* @copyright 2017
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

/*

Return all properties in the system for this user, including those not created by this channel

*/

Flight::route('GET /cmf/properties/all', function()
	{
    require_once("../framework.php");

	validate_scope::validate('channel_management');
	
	cmf_utilities::validate_channel_for_user();  // If the user and channel name do not correspond, then this channel is incorrect and can go no further, it'll throw a 204 error

	$thisJRUser = jomres_singleton_abstract::getInstance('jr_user');
	$thisJRUser->init_user(Flight::get('user_id'));

	$response = array();
	if (!empty($thisJRUser->authorisedProperties)) {
		foreach ( $thisJRUser->authorisedProperties as $r ) {
			$response[] = $r ;
		}
	}

	Flight::json( $response_name = "response" , $response ); 
	});
	
	