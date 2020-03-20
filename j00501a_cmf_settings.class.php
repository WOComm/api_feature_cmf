<?php
/**
 * Core file.
 *
 * @author Vince Wooll <sales@jomres.net>
 *
 * @version Jomres 9.21.4
 *
 * @copyright	2005-2020 Vince Wooll
 * Jomres (tm) PHP, CSS & Javascript files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project, and use it accordingly
 **/

// ################################################################
defined('_JOMRES_INITCHECK') or die('');
// ################################################################
	
	/**
	 * @package Jomres\Core\Minicomponents
	 *
	 * Property Configuration page tabs. Offers booking form related configuration options.
	 * 
	 */

class j00501a_cmf_settings
{	
	/**
	 *
	 * Constructor
	 * 
	 * Main functionality of the Minicomponent 
	 *
	 * 
	 * 
	 */
	 
	public function __construct($componentArgs)
	{
		// Must be in all minicomponents. Minicomponents with templates that can contain editable text should run $this->template_touch() else just return
		$MiniComponents = jomres_singleton_abstract::getInstance('mcHandler');
		if ($MiniComponents->template_touch) {
			$this->template_touchable = false;

			return;
		}
		$configurationPanel = $componentArgs[ 'configurationPanel' ];

		$mrConfig = getPropertySpecificSettings();
		if ($mrConfig[ 'is_real_estate_listing' ] == 1) {
			return;
		}

		if (!isset($mrConfig['cmf_exposure_allowed'] )) {
			$mrConfig['cmf_exposure_allowed']  = 0;
			$query = "INSERT INTO #__jomres_settings (property_uid,akey,value) VALUES ('".(int) get_showtime("property_uid")."','cmf_exposure_allowed','".$mrConfig['cmf_exposure_allowed']."')";
			doInsertSql($query, jr_gettext('_JOMRES_MR_AUDIT_EDIT_PROPERTY_SETTINGS', '_JOMRES_MR_AUDIT_EDIT_PROPERTY_SETTINGS', false));

		}

		$yesno = array();
		$yesno[] = jomresHTML::makeOption( '0', jr_gettext('_JOMRES_COM_MR_NO','_JOMRES_COM_MR_NO') );
		$yesno[] = jomresHTML::makeOption( '1', jr_gettext('_JOMRES_COM_MR_YES','_JOMRES_COM_MR_YES') );

		$lists = array();
		$lists['cmf_exposure_allowed'] = jomresHTML::selectList( $yesno, 'cfg_cmf_exposure_allowed', 'class="inputbox" size="1"', 'value', 'text', $mrConfig['cmf_exposure_allowed'] );

		$configurationPanel->startPanel(jr_gettext('_OAUTH_SCOPE_CHANNEL_MANAGEMENT','_OAUTH_SCOPE_CHANNEL_MANAGEMENT',false,false));

		$configurationPanel->setleft(jr_gettext('_CMF_EXPOSURE_ALLOWED','_CMF_EXPOSURE_ALLOWED',false,false));
		$configurationPanel->setmiddle($lists['cmf_exposure_allowed']);
		$configurationPanel->setright(jr_gettext('_CMF_EXPOSURE_ALLOWED_DESC','_CMF_EXPOSURE_ALLOWED_DESC',false,false));
		$configurationPanel->insertSetting();

		$configurationPanel->endPanel();
	}


	public function getRetVals()
	{
		return null;
	}
}
