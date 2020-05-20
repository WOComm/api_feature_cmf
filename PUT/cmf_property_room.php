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

Return the items for a given property type (e.g. property types) that currently exist in the system

*/

Flight::route('PUT /cmf/property/room', function()
	{
    require_once("../framework.php");

	validate_scope::validate('channel_management');
	
	$_PUT = $GLOBALS['PUT']; // PHP doesn't allow us to use $_PUT like a super global, however the put_method_handling.php script will parse form data and put it into PUT, which we can then use. This allows us to use PUT for updating records (as opposed to POST which is, in REST APIs used for record creation). This lets us maintain a consistent syntax throughout the REST API.

	cmf_utilities::validate_channel_for_user();  // If the user and channel name do not correspond, then this channel is incorrect and can go no further, it'll throw a 204 error

 	$property_uid				= (int)$_PUT['property_uid'];

	cmf_utilities::validate_property_uid_for_user($property_uid);
	
	jr_import('jrportal_rooms');
	
	$mrConfig = getPropertySpecificSettings($property_uid);
	
	$property = cmf_utilities::get_property_object_for_update($property_uid); 
	
	
	if ( $mrConfig[ 'singleRoomProperty' ] == '1' && isset($property->rooms["local_rooms"]) &&  count($property->rooms["local_rooms"]) == 1 ) {
		Flight::json( $response_name = "property" , array( "success" => 0 , "message" => "Cannot add more than one room to an SRP")  ); 
	}

	$jrportal_rooms = new jrportal_rooms();

	$jrportal_rooms->propertys_uid				= $property_uid;
	$jrportal_rooms->room_uid					= (int) jomresGetParam($_PUT, 'room_uid', 0);
	$jrportal_rooms->room_classes_uid			= (int) jomresGetParam($_PUT, 'room_type_id', 0);
	$jrportal_rooms->max_people					= (int) jomresGetParam($_PUT, 'max_people', 0);
	$jrportal_rooms->room_name					= getEscaped(jomresGetParam($_PUT, 'room_name', ''));
	$jrportal_rooms->room_number				= getEscaped(jomresGetParam($_PUT, 'room_number', ''));
	$jrportal_rooms->room_floor					= getEscaped(jomresGetParam($_PUT, 'room_floor', ''));
	$jrportal_rooms->singleperson_suppliment	= (float) jomresGetParam($_PUT, 'singleperson_suppliment', 0.0);
	$jrportal_rooms->room_features_uid			= '';
	$jrportal_rooms->tagline					= getEscaped(jomresGetParam($_PUT, 'tagline', ''));
	$jrportal_rooms->surcharge					= (float) jomresGetParam($_PUT, 'surcharge', 0.0);
	$jrportal_rooms->description			= jomresGetParam($_PUT, 'description', '');

	if ($jrportal_rooms->room_uid > 0) {
		$jrportal_rooms->commit_update_room();
	} else {
		$jrportal_rooms->commit_new_room();
	}
	
	Flight::json( $response_name = "response" , array( "room_uid" => $jrportal_rooms->room_uid) );
	});
	
	