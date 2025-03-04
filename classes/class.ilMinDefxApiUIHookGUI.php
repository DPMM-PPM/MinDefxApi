<?php

/**
 * Class ilMinDefxApiUIHookGUI
 * @author            Kalamun <rp@kalamun.net>
 * @version $Id$
 * @ingroup ServicesUIComponent
 * @ilCtrl_isCalledBy ilMinDefxApiUIHookGUI: ilUIPluginRouterGUI, ilAdministrationGUI, ilMinDefxApiGUI
 */

class ilMinDefxApiUIHookGUI extends ilUIHookPluginGUI {

  public function __construct()
  {

  }
  
  function getHTML($a_comp = false, $a_part = false, $a_par = array()): array {
    return ["mode" => ilUIHookPluginGUI::KEEP, "html" => ""];
  }

  function modifyGUI($a_comp,$a_part,$a_par = [])
	{

	}
  
}
