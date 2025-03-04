<?php
/**
 * Class ilMinDefxApiPlugin
 * @author  Kalamun <rp@kalamun.net>
 * @version $Id$
 */

class ilMinDefxApiPlugin extends ilUserInterfaceHookPlugin
{
    const CTYPE = "Services";
    const CNAME = "UIComponent";
    const SLOT_ID = "uihk";
    const PLUGIN_NAME = "MinDefxApi";
    protected static $instance = null;

    public function __construct()
    {
        parent::__construct();
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
    }

    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }
    
    public function beforeUninstall()
    {
    	/* Remise en état d'origine du module CmiXapi  */
    	require_once('class.ilMinDefxApiConfigGUI.php');
        $plugin = new ilMinDefxApiConfigGUI();
        if ($plugin->is_active==true){
        	ilUtil::sendFailure($this->txt("uninstall_not_possible"), true);
        	return false;
        }
        else{
        	return true;
        }
    }

}
