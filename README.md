# ILIAS MinDef xApi
This plug-in will enable ILIAS to use xApi.

Copyright (c) 2024 Roberto Pasini <bonjour@kalamun.net>
GPLv3, see LICENSE

Author: Roberto Pasini <bonjour@kalamun.net>

## Install

```
mkdir -p Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
cd Customizing/global/plugins/Services/UIComponent/UserInterfaceHook
git clone https://github.com/kalamun/ILIAS-MinDefxApi.git MinDefxApi
```

## Activation

After having copied the plugin files to the plugins directory, log-in to ILIAS and go to `Administration` > `Extending ILIAS` > `Plugins`.
There, in the corresponding line, you have to click on `Actions` button, then select `Install`.
Do it again, but this time selecting `Activate`.
Then you can go to `Configure`.

## Usage

Concretely, this plugin is going to apply some modifications to the ILIAS code.
First of all it will perform some compatibility checks.
It could be the case that you don't have the writing permissions: in that case you can't apply the modifications and you have to contact the system administration to give to the Apache user (usually `www-data`) the right permissions.
It could also be the case that your ILIAS version is not compatible with the plugin: in that case a warning will be displayed.

To apply the patch, click the button "Enable". This action will rename some files of the CmiXapi ilias module with extension ".bkup".
To remove the patch, click the button "Disable". This action will delete files installed by the plugin and rename original files of the CmiXapi ilias module.

<b>IMPORTANT : You mustn't delete renamed files with extension ".bkup" of the server. if you do it, you won't be able to disable the plugin.  </b>


## Requirements
This plugin is compatible with ILIAS v7.x
