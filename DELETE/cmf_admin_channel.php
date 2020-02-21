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

Return all countries 

*/

Flight::route('DELETE /cmf/admin/channel/@channel_id', function( $channel_id )
	{
    require_once("../framework.php");

	cmf_utilities::validate_admin_for_user();
	
	// 
	$query = "SELECT id FROM #__jomres_channelmanagement_framework_property_uid_xref WHERE channel_id = ".$channel_id;
	$channels = doSelectSql($query);
	
	if (!empty($channels)) {
		Flight::halt(204, "Channel has properties, re-assign those properties to another channel before attempting to delete this one.");
	} 
	
	
	$query = "DELETE FROM #__jomres_channelmanagement_framework_channels WHERE channel_id = ".$channel_id;
	doInsertSql($query);
	

	
	Flight::json( $response_name = "response" , $channel_properties ); 
	});
	