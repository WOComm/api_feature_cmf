<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright	2005-2019 Woollyinwales IT
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die();
// ################################################################

jr_define('_OAUTH_SCOPE_CHANNEL_MANAGEMENT',"Channel Management");
jr_define('_OAUTH_SCOPE_CHANNEL_MANAGEMENT_DESC',"Client can perform Channel Management activities. Note, this gives the client considerable power in the system to modify your accounts and properties.");

jr_define('_OAUTH_SCOPE_CHANNEL_MANAGEMENT_CLEANING_PRICE',"Cleaning");

jr_define('_CMF_CANCELLED_BOOKING',"Channel manager cancelled booking");

jr_define('_CMF_CLEANING_STRING',"Cleaning");  // Do not change this if you have already imported properties. Properties with cleaning fees have an Extra with this name
jr_define('_CMF_SECURITY_STRING',"Security deposit");  // Do not change this if you have already imported properties. Properties with security deposits have an Extra with this name