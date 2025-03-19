<?php
/**
 * Class ilMinDefxApiConfigGUI
 * @author            Roberto Pasini <bonjour@kalamun.net>
 * @ilCtrl_IsCalledBy ilMinDefxApiConfigGUI: ilObjComponentSettingsGUI
 */

 class ilMinDefxApiConfigGUI extends ilPluginConfigGUI {

  const PLUGIN_CLASS_NAME = ilMinDefxApiPlugin::class;
  const CMD_CONFIGURE = "configure";
  const CMD_ENABLE = "enablexApi";
  const CMD_DISABLE = "disablexApi";

  protected $dic;
  protected $plugin;
  protected $lng;
  protected $request;
  protected $user;
  protected $ctrl;
  protected $object;

  protected $compatible_version;
  public $is_active;
  protected $replace_list;
  protected $is_writable;
  
  public function __construct()
  {
    global $DIC;
    $this->dic = $DIC;
    $this->plugin = ilMinDefxApiPlugin::getInstance();
    $this->lng = $this->dic->language();
    $this->request = $this->dic->http()->request();
    $this->user = $this->dic->user();
    $this->ctrl = $this->dic->ctrl();
    $this->object = $this->dic->object();
    
    $this->replace_list = [
      "./Modules/CmiXapi/classes/class.ilCmiXapiDateTime.php",
      "./Modules/CmiXapi/classes/class.ilCmiXapiLaunchGUI.php",
      "./Modules/CmiXapi/classes/class.ilCmiXapiLrsType.php",
      "./Modules/CmiXapi/classes/class.ilCmiXapiScoringGUI.php",
      "./Modules/CmiXapi/classes/class.ilCmiXapiSettingsGUI.php",
      "./Modules/CmiXapi/classes/class.ilCmiXapiStatementsGUI.php",
      "./Modules/CmiXapi/classes/class.ilCmiXapiUser.php",
      "./Modules/CmiXapi/classes/class.ilObjCmiXapi.php",
      "./Modules/CmiXapi/classes/class.ilObjCmiXapiGUI.php",     
      "./Modules/CmiXapi/classes/XapiReport/class.ilCmiXapiAbstractReportLinkBuilder.php",
      "./Modules/CmiXapi/classes/XapiReport/class.ilCmiXapiHighscoreReport.php",
      "./Modules/CmiXapi/classes/XapiReport/class.ilCmiXapiHighscoreReportLinkBuilder.php",
      "./Modules/CmiXapi/classes/XapiReport/class.ilCmiXapiStatementsReport.php",
      "./Modules/CmiXapi/classes/XapiReport/class.ilCmiXapiStatementsReportLinkBuilder.php",
      "./Modules/CmiXapi/classes/XapiReport/class.ilXapiCompliantStatementsReportLinkBuilder.php",
    ];

    $this->detect_version();
  }
  
  private function get_local_path($file_path) {
    return str_replace("./Modules/CmiXapi/classes/", "", $file_path);
  }

  public function detect_version() {

    $this->is_writable = true;
    $this->is_active = false;
    
    if(ILIAS_VERSION[0]==7){
    	$this->compatible_version = ILIAS_VERSION;}
    else{
    	$this->compatible_version = false;}

    foreach ($this->replace_list as $file_path) {
      if (!is_writable($file_path)) {
        $this->is_writable = false;
      }
      
      $content = file_get_contents($file_path);
     
      if (strpos($content, '* edited by MinDefxAPI v') !== false) {
        $this->is_active = true;
        preg_match('#^((\d+\.)+\d+)#', substr($content, strpos($content, '* edited by MinDefxAPI v') + 24, 8), $matched_version);
        $this->compatible_version = $matched_version[0];       
      } else {
      	$this->is_active = false;
      }
    }
  }
  
  public function performCommand($cmd)
  {
    $this->plugin = $this->getPluginObject();

    switch ($cmd)
		{
		case self::CMD_CONFIGURE:
      		case self::CMD_DISABLE:
      		case self::CMD_ENABLE:
        	$this->{$cmd}();
        	break;

      		default:
        	break;
		}
  }

  protected function enablexApi(): void
  {
    foreach ($this->replace_list as $file_path) {
      if ($this->compatible_version) {
      	$file_name = $this->get_local_path($file_path);
      	$content = file_get_contents($file_path);
      	
      	/* backup des fichiers d'origine */
        if (strpos($content, '/* edited by MinDefConnect') === false) {
               rename($file_path, $file_path. '.bkup');
        	}
        else{
        	ilUtil::sendSuccess($this->plugin->txt("configuration_saved"), true);
        }
        
        /* Remplacement des fichiers d'origine par les fichiers du plugin */ 
        $copy_from = './Customizing/global/plugins/Services/UIComponent/UserInterfaceHook/MinDefxApi/vendor/src_files/7.30/' . $file_name;
        $copy_to = $file_path;
        copy($copy_from, $copy_to);
        ilUtil::sendSuccess($this->plugin->txt("configuration_saved"), true);
        }
    }
   $this->detect_version();
   $this->configure();
  }
  
  protected function disablexApi(): void
  {
    foreach ($this->replace_list as $file_path) {
      if ($this->compatible_version) {
        $file_name = $this->get_local_path($file_path);
        $content = file_get_contents($file_path);
        
        /* Suppression du fichier modifié par le plugin et rename du fichier de backup */
        	unlink($file_path);
        	rename($file_path.'.bkup', $file_path);
      }
    }
    ilUtil::sendSuccess($this->plugin->txt("configuration_saved"), true);
    $this->detect_version();
    $this->configure();
  }

  protected function configure(): void
  {
	global $tpl, $ilCtrl;
	require_once("./Services/Form/classes/class.ilPropertyFormGUI.php");
	$form = new ilPropertyFormGUI();
	$form->setFormAction($ilCtrl->getFormAction($this));
    	$form->setTitle($this->plugin->txt("settings"));
    
    	if (!$this->is_writable) {
      		$plugin_enabled_heading = new ilFormSectionHeaderGUI();
      		$plugin_enabled_heading->setTitle($this->plugin->txt('not_writable'));
      		$form->addItem($plugin_enabled_heading);
    	} elseif ($this->compatible_version) {
      		if ($this->is_active==false) {
//        		ilObjCmiXapi::log()->debug('dans configure2.1');
        		$plugin_enabled_heading = new ilFormSectionHeaderGUI();
        		$plugin_enabled_heading->setTitle($this->plugin->txt('status_supported') . ' (v.' . $this->compatible_version . ')');
        		$plugin_enabled_heading->setInfo($this->plugin->txt('status_inactive_info'));
        		$form->addItem($plugin_enabled_heading);
        		$form->addCommandButton("enablexApi", $this->plugin->txt("enable"));
      		} else {
        		$plugin_enabled_heading = new ilFormSectionHeaderGUI();
        		$plugin_enabled_heading->setTitle($this->plugin->txt('status_active') . ' (v.' . $this->compatible_version . ')');
        		$plugin_enabled_heading->setInfo($this->plugin->txt('status_active_info'));
        		$form->addItem($plugin_enabled_heading);
        		$form->addCommandButton("disablexApi", $this->plugin->txt("disable"));
      		}
    	} else {
      			$plugin_enabled_heading = new ilFormSectionHeaderGUI();
      			$plugin_enabled_heading->setTitle($this->plugin->txt('incompatible_version'));
      			$form->addItem($plugin_enabled_heading);
    	}
	$tpl->setContent($form->getHTML());
  }
}
