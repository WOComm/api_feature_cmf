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

class plugin_info_api_feature_cmf
	{
	function __construct()
		{
		$this->data=array(
			"name"=>"api_feature_cmf",
			"category"=>"REST API",
			"marketing"=>"Functionality used by the Channel Management Framework",
			"version"=>"0.14",
			"description"=> "Functionality used by the Channel Management Framework, however intended to be used by any application that has a key/secret pair",
			"author"=>"",
			"authoremail"=>"",
			"lastupdate"=>"2021/03/26",
			"min_jomres_ver"=>"9.21.5",
			"manual_link"=>'',
			'change_log'=>' v0.2 Added scores of new endpoints v0.3 Added...Blackbookings, list booking status, get booking, get changeover day, list active bookings, list bookings, publishing, put availability, status review, property children reporting, echo, property booking link, available rooms, easy setting of base price, easy setting of min stays and prices, reservation add and cancel. Various scripts updated in Nightly to support status review where if a property is changed and it no longer passes sanity checks then if (currently secret) settings are enabled then the property is unpublished and possibly set for Re-approval too. v0.4 Standardised response names, added endpoints plugin settings (for gateways) and a variety of admin level endpoints.  v0.5 Various tweaks and updates. v0.6 Various tweaks. v0.7 Modified admin channel list properties endpoint to account for deleted managers. 0.8 Added new endpoints : PUT remote property id and PUT admin channel unassign properties. Modified how the utilities class finds custom header and proxy information because some webservers like to capitalise headers. The little tinkers. v0.8 New endpoints added plus a variety of tweaks v0.9 Proxy header changed to use hyphens. v0.10 Fixed response from admin delete channel, Fixed duplicates appearing in GET properties ids. Fixed property availability blocks to work with base64 encoded remote data introduced in last version. Added checks for empty local_rooms variable, which can happen because a room hasn\'t yet been added to the property. Proxy header changed to use hyphens. Added extra checks for manager id not being set correctly v0.11 improved put property tariffs so that updating works as well as insertion  Modified list active bookings to produce more data, fixed query in changelog endpoint. 0.12 Added invoices and fixed issue with tariff updating max guests. v0.13 PUT property tariffs adjusted so that a bug where multiple tariffs can be created is resolved. v0.14 Added code to uppercase headers in Announce feature.',
			'highlight'=>'',
			'image'=>'',
			'demo_url'=>''
			);
		}
	}
