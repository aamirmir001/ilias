<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2005 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

include_once("./Services/Table/classes/class.ilTableGUI.php");
include_once("classes/class.ilTabsGUI.php");

/**
* Class ilAdministratioGUI
*
* @author Alex Killing <alex.killing@gmx.de>
* @version $Id$
*
* @ilCtrl_Calls ilAdministrationGUI: ilObjGroupGUI, ilObjFolderGUI, ilObjFileGUI, ilObjCourseGUI, ilCourseObjectivesGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjSAHSLearningModuleGUI, ilObjChatGUI, ilObjForumGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjLearningModuleGUI, ilObjDlBookGUI, ilObjGlossaryGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjQuestionPoolGUI, ilObjSurveyQuestionPoolGUI, ilObjTestGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjSurveyGUI, ilObjExerciseGUI, ilObjMediaPoolGUI, ilObjFileBasedLMGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjCategoryGUI, ilObjUserGUI, ilObjRoleGUI, ilObjUserFolderGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjiLincCourseGUI, ilObjiLincClassroomGUI, ilObjLinkResourceGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjRoleTemplateGUI, ilObjStyleSheetGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjRootFolderGUI, ilObjSessionGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjSystemFolderGUI, ilObjRoleFolderGUI, ilObjAuthSettingsGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjChatServerGUI, ilObjLanguageFolderGUI, ilObjMailGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjObjectFolderGUI, ilObjPaymentSettingsGUI, ilObjRecoveryFolderGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjSearchSettingsGUI, ilObjStyleSettingsGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjAssessmentFolderGUI, ilObjExternalToolsSettingsGUI, ilObjUserTrackingGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjAdvancedEditingGUI, ilObjPrivacySecurityGUI, ilObjNewsSettingsGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjPersonalDesktopSettingsGUI, ilObjMediaCastGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjLanguageExtGUI, ilObjMDSettingsGUI, ilObjComponentSettingsGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjCalendarSettingsGUI, ilObjSurveyAdministrationGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjCategoryReferenceGUI, ilObjCourseReferenceGUI, ilObjRemoteCourseGUI
* @ilCtrl_Calls ilAdministrationGUI: ilObjForumAdministrationGUI
* // BEGIN WebDAV
* @ilCtrl_Calls ilAdministrationGUI: ilObjFileAccessSettingsGUI, ilPermissionGUI
* // END WebDAV
*/
class ilAdministrationGUI
{
	var $lng;
	var $ilias;
	var $tpl;
	var $tree;
	var $rbacsystem;
	var $cur_ref_id;
	var $cmd;
	var $mode;
	var $ctrl;

	/**
	* Constructor
	* @access	public
	*/
	function ilAdministrationGUI()
	{
		global $lng, $ilias, $tpl, $tree, $rbacsystem, $objDefinition,
			$_GET, $ilCtrl, $ilLog, $ilMainMenu;

		$this->lng =& $lng;
		$this->lng->loadLanguageModule('administration');
		$this->ilias =& $ilias;
		$this->tpl =& $tpl;
		$this->tree =& $tree;
		$this->rbacsystem =& $rbacsystem;
		$this->objDefinition =& $objDefinition;

		$this->ctrl =& $ilCtrl;
		$ilMainMenu->setActive("administration");
		
		$this->creation_mode = false;

		$this->ctrl->saveParameter($this, array("ref_id", "admin_mode"));
		
		if ($_GET["admin_mode"] != "repository")
		{
			$_GET["admin_mode"] = "settings";
		}
		
		if (!ilUtil::isAPICall())
			$this->ctrl->setReturn($this,"");

		// determine current ref id and mode
		if (!empty($_GET["ref_id"]) && $tree->isInTree($_GET["ref_id"]))
		{
			$this->cur_ref_id = $_GET["ref_id"];
		}
		else
		{
			//$this->cur_ref_id = $this->tree->getRootId();
			$_POST = array();
			if ($_GET["cmd"] != "getDropDown")
			{
				$_GET["cmd"] = "";
			}
		}
	}

	
	/**
	* execute command
	*/
	function &executeCommand()
	{
		global $tree, $rbacsystem, $ilias, $lng, $objDefinition;
		
		// permission checks
		include_once './Services/MainMenu/classes/class.ilMainMenuGUI.php';
		if(!ilMainMenuGUI::_checkAdministrationPermission())
		{
			$ilias->raiseError("You are not entitled to access this page!",$ilias->error_obj->WARNING);
		}
		// check creation mode
		// determined by "new_type" parameter
		$new_type = $_POST["new_type"]
			? $_POST["new_type"]
			: $_GET["new_type"];
		if ($new_type != "" && $this->ctrl->getCmd() == "create")
		{
			$this->creation_mode = true;
		}
	
		// determine next class
		if ($this->creation_mode)
		{
			$obj_type = $new_type;
			$class_name = $this->objDefinition->getClassName($obj_type);
			$next_class = strtolower("ilObj".$class_name."GUI");
			$this->ctrl->setCmdClass($next_class);
		}
		// set next_class directly for page translations
		// (no cmdNode is given in translation link)
		elseif ($this->ctrl->getCmdClass() == "ilobjlanguageextgui")
		{
			$next_class = "ilobjlanguageextgui";
		}
		else
		{
			$next_class = $this->ctrl->getNextClass($this);
			
		}

		if (($next_class == "iladministrationgui" || $next_class == ""
			) && ($this->ctrl->getCmd() == "return"))
		{
			// get GUI of current object
			$obj_type = ilObject::_lookupType($this->cur_ref_id,true);
			$class_name = $this->objDefinition->getClassName($obj_type);
			$next_class = strtolower("ilObj".$class_name."GUI");
			$this->ctrl->setCmdClass($next_class);
			$this->ctrl->setCmd("view");
		}

		$cmd = $this->ctrl->getCmd("frameset");

//echo "<br>cmd:$cmd:nextclass:$next_class:-".$_GET["cmdClass"]."-".$_GET["cmd"]."-";
		switch ($next_class)
		{
			/*
			case "ilobjusergui":
				include_once('./Services/User/classes/class.ilObjUserGUI.php');

				if(!$_GET['obj_id'])
				{
					$this->gui_obj = new ilObjUserGUI("",$_GET['ref_id'],true, false);
					$this->gui_obj->setCreationMode($this->creation_mode);

					$this->prepareOutput(false);
					$ret =& $this->ctrl->forwardCommand($this->gui_obj);
				}
				else
				{
					$this->gui_obj = new ilObjUserGUI("", $_GET['obj_id'],false, false);
					$this->gui_obj->setCreationMode($this->creation_mode);

					$this->prepareOutput(false);
					$ret =& $this->ctrl->forwardCommand($this->gui_obj);
				}
				$this->tpl->show();
				break;
			*/
				
			/*
			case "ilobjuserfoldergui":
				include_once('./Services/User/classes/class.ilObjUserFolderGUI.php');

				$this->gui_obj = new ilObjUserFolderGUI("", $_GET['ref_id'],true, false);
				$this->gui_obj->setCreationMode($this->creation_mode);

				$this->prepareOutput(false);
				$ret =& $this->ctrl->forwardCommand($this->gui_obj);
				$this->tpl->show();
				break;*/

			default:
			
				// forward all other classes to gui commands
				if ($next_class != "" && $next_class != "iladministrationgui")
				{
					$class_path = $this->ctrl->lookupClassPath($next_class);
					// get gui class instance
					include_once($class_path);
					$class_name = $this->ctrl->getClassForClasspath($class_path);
					if (($next_class == "ilobjrolegui" || $next_class == "ilobjusergui"
						|| $next_class == "ilobjroletemplategui"
						|| $next_class == "ilobjstylesheetgui"))
					{
						if ($_GET["obj_id"] != "")
						{
							$this->gui_obj = new $class_name("", $_GET["obj_id"], false, false);
							$this->gui_obj->setCreationMode(false);
						}
						else
						{
							$this->gui_obj = new $class_name("", $this->cur_ref_id, true, false);
							$this->gui_obj->setCreationMode(true);
						}
					}
					else
					{
						if ($objDefinition->isPlugin(ilObject::_lookupType($this->cur_ref_id,true)))
						{
							$this->gui_obj = new $class_name($this->cur_ref_id);
						}
						else
						{
							$this->gui_obj = new $class_name("", $this->cur_ref_id, true, false);
						}
						$this->gui_obj->setCreationMode($this->creation_mode);
					}
					$tabs_out = ($new_type == "")
						? true
						: false;

					$this->ctrl->setReturn($this, "return");					
					$ret =& $this->ctrl->forwardCommand($this->gui_obj);
					$html = $this->gui_obj->getHTML();

					if ($html != "")
					{
						$this->tpl->setVariable("OBJECTS", $html);
					}
					$this->tpl->show();
				}
				else	// 
				{
					$cmd = $this->ctrl->getCmd("frameset");
					$this->$cmd();
				}
				break;
		}
	}

	/**
	* output tree frameset
	*/
	function frameset()
	{
		global $tree;
		
		include_once("Services/Frameset/classes/class.ilFramesetGUI.php");
		$fs_gui = new ilFramesetGUI();

		$fs_gui->setMainFrameName("content");
		$fs_gui->setSideFrameName("tree");
		$fs_gui->setFrameSetTitle($this->lng->txt("administration"));

		if ($_GET["admin_mode"] != "repository")	// settings
		{
			if ($_GET["ref_id"] == USER_FOLDER_ID)
			{
				$this->ctrl->setParameter($this, "ref_id", USER_FOLDER_ID);
				$this->ctrl->setParameterByClass("iladministrationgui", "admin_mode", "settings");
				$fs_gui->setMainFrameSource(
					$this->ctrl->getLinkTargetByClass("ilobjuserfoldergui", "view"));
		$this->ctrl->redirectByClass("ilobjuserfoldergui", "view");
			}
			else
			{
				$this->ctrl->setParameter($this, "ref_id", SYSTEM_FOLDER_ID);
				$this->ctrl->setParameterByClass("iladministrationgui", "admin_mode", "settings");

                if($_GET['fr'])
                {
                    $fs_gui->setMainFrameSource(
                        base64_decode(rawurldecode($_GET['fr'])));
		ilUtil::redirect(base64_decode(rawurldecode($_GET['fr'])));
                }
                else
                {
                    $fs_gui->setMainFrameSource(
                        $this->ctrl->getLinkTargetByClass("ilobjsystemfoldergui", "view"));
		$this->ctrl->redirectByClass("ilobjsystemfoldergui", "view");
                }
			}
			$this->ctrl->setParameter($this, "expand", "1");
			$fs_gui->setSideFrameSource(
				$this->ctrl->getLinkTarget($this, "showTree"));
		}
		else
		{
			$this->ctrl->setParameter($this, "ref_id", ROOT_FOLDER_ID);
			$this->ctrl->setParameterByClass("iladministrationgui", "admin_mode", "repository");
			$fs_gui->setMainFrameSource(
				$this->ctrl->getLinkTargetByClass("ilobjrootfoldergui", "view"));
			$this->ctrl->setParameter($this, "expand", "1");
			$fs_gui->setSideFrameSource(
				$this->ctrl->getLinkTarget($this, "showTree"));
		}
		
		$fs_gui->show();
		exit;
	}

	/**
	* display tree view
	*/
	function showTree()
	{
		global $tpl, $tree, $lng;

		require_once "./Services/Administration/classes/class.ilAdministrationExplorer.php";

		$explorer = new ilAdministrationExplorer("ilias.php?baseClass=ilAdministrationGUI&cmd=view");		
		$explorer->setExpand($_GET["expand"]);
		$explorer->setExpandTarget($this->ctrl->getLinkTarget($this, "showTree"));
		$explorer->setUseStandardFrame(true);
		
		// hide RecoveryFolder if empty
		if (!$tree->getChilds(RECOVERY_FOLDER_ID))
		{
			$explorer->addFilter("recf");
		}
		//$explorer->addFilter("rolf");

		if ($_GET["admin_mode"] == "settings")
		{
			$explorer->addFilter("cat");
			$explorer->addFilter("catr");
		}
		else
		{
			$explorer->addFilter("adm");
		}
		/*
		$explorer->addFilter("root");
		$explorer->addFilter("cat");
		$explorer->addFilter("grp");
		$explorer->addFilter("crs");
		$explorer->addFilter("le");
		$explorer->addFilter("frm");
		$explorer->addFilter("lo");
		$explorer->addFilter("rolf");
		$explorer->addFilter("adm");
		$explorer->addFilter("lngf");
		$explorer->addFilter("usrf");
		$explorer->addFilter("objf");
		*/
		//$explorer->setFiltered(false);
		$explorer->setOutput(0);		
		$output = $explorer->getOutput();		
		$this->ctrl->setParameter($this, "expand", $_GET["expand"]);
		echo $output;
	}
	
	/**
	 * Special jump to plugin slot after ilCtrl has been reloaded
	 */
	function jumpToPluginSlot()
	{
		global $ilCtrl;
		
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "ctype", $_GET["ctype"]);
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "cname", $_GET["cname"]);
		$ilCtrl->setParameterByClass("ilobjcomponentsettingsgui", "slot_id", $_GET["slot_id"]);
		$ilCtrl->redirectByClass("ilobjcomponentsettingsgui", "showPluginSlot");

	}

	/**
	 * Get drop down
	 */
	function getDropDown()
	{
		global $tree, $rbacsystem, $lng, $ilSetting, $objDefinition;

		$tpl = new ilTemplate("tpl.admin_drop_down.html", true, true, "Services/Administration");

		$objects = $tree->getChilds(SYSTEM_FOLDER_ID);
		foreach($objects as $object)
		{
			$new_objects[$object["title"].":".$object["child"]]
				= $object;
		}

		// add entry for switching to repository admin
		// note: please see showChilds methods which prevents infinite look
		$new_objects[$lng->txt("repository_admin").":".ROOT_FOLDER_ID] =
			array(
			"tree" => 1,
			"child" => ROOT_FOLDER_ID,
			"ref_id" => ROOT_FOLDER_ID,
			"depth" => 3,
			"type" => "root",
			"title" => $lng->txt("repository_admin"),
			"description" => $lng->txt("repository_admin_desc"),
			"desc" => $lng->txt("repository_admin_desc"),
			);

//$nd = $tree->getNodeData(SYSTEM_FOLDER_ID);
//var_dump($nd);
		$new_objects[$lng->txt("general_settings").":".SYSTEM_FOLDER_ID] =
			array(
			"tree" => 1,
			"child" => SYSTEM_FOLDER_ID,
			"ref_id" => SYSTEM_FOLDER_ID,
			"depth" => 2,
			"type" => "adm",
			"title" => $lng->txt("general_settings"),
			);
		ksort($new_objects);

		// determine items to show
		$items = array();
		foreach ($new_objects as $c)
		{
			// check visibility
			if ($tree->getParentId($c["ref_id"]) == ROOT_FOLDER_ID && $c["type"] != "adm" &&
				$_GET["admin_mode"] != "repository")
			{
				continue;
			}
			// these objects may exist due to test cases that didnt clear
			// data properly
			if ($c["type"] == "" || $c["type"] == "objf" ||
				$c["type"] == "xxx" || $c["type"] == 'frma')
			{
				continue;
			}
			$visible = $rbacsystem->checkAccess('visible', $c["ref_id"]);
			if ($c["type"] == "rolf" && $c["ref_id"] != ROLE_FOLDER_ID)
			{
				continue;
			}
			$items[] = $c;
		}

		$two_columns = false;
		if (count($items) > 10)
		{
			$two_columns = true;

			// resort for two column layout
			$n = array();
			$half = ceil(count($items) / 2);
			for ($i = 0; $i < $half; $i++)
			{
				$n[] = $items[$i];
				if (isset($items[$i + $half]))
				{
					$n[] = $items[$i + $half];
				}
			}
			$items = $n;
		}

		$cnt = 0;
		foreach ($items as $c)
		{
			$cnt++;
			// get icon
			$path = "";
			if ($ilSetting->get("custom_icons") &&
				in_array($c["type"], array("cat","grp","crs")))
			{
				require_once("./Services/Container/classes/class.ilContainer.php");
				$path = ilContainer::_lookupIconPath($c["obj_id"], "small");
			}
			if ($path == "")
			{
				$path = ilUtil::getImagePath("icon_".$c["type"]."_s.gif");
			}
			$alt = $lng->txt("icon")." ".$lng->txt("obj_".$c["type"]);

			// output item
			if ($rbacsystem->checkAccess('read', $c["ref_id"]))
			{
				// build link
				$link = "";
				if ($_GET["admin_mode"] == "settings" && $c["ref_id"] == ROOT_FOLDER_ID)
				{
					//$this->ctrl->setParameterByClass("iladministrationgui", "ref_id", ROOT_FOLDER_ID);
					//$this->ctrl->setParameterByClass("iladministrationgui", "admin_mode", "repository");
					//$link = $this->ctrl->getLinkTargetByClass("iladministrationgui", "frameset");
					//$this->ctrl->setParameterByClass("iladministrationgui", "admin_mode", "settings");
					$link = "ilias.php?baseClass=ilAdministrationGUI&amp;ref_id=".
						$c["ref_id"]."&amp;admin_mode=repository";
				}
				else
				{
					$link = "ilias.php?baseClass=ilAdministrationGUI&amp;ref_id=".
						$c["ref_id"]."&amp;cmd=jump";
				}

				$tpl->setCurrentBlock("linked");
				$tpl->setVariable("ITEM_TITLE", $c["title"]);
				$tpl->setVariable("ITEM_HREF", $link);
				$tpl->setVariable("IMG", ilUtil::img($path, $alt), 16, 16);
				$tpl->parseCurrentBlock();
			}
			else
			{
				$tpl->setCurrentBlock("linked");
				$tpl->setVariable("NL_ITEM_TITLE", $c["title"]);
				$tpl->setVariable("NL_IMG", ilUtil::img($path, $alt), 16, 16);
				$tpl->parseCurrentBlock();
			}

			$tpl->setCurrentBlock("td");
			$tpl->parseCurrentBlock();

			if (!$two_columns || $cnt % 2 == 0)
			{
				$tpl->setCurrentBlock("row");
				$tpl->parseCurrentBlock();
			}
		}

		echo $tpl->get();
		exit;
	}

	/**
	 * Jump to node
	 */
	function jump()
	{
		global $ilCtrl, $objDefinition;

		$ref_id = (int) $_GET["ref_id"];
		$obj_id = ilObject::_lookupObjId($ref_id);
		$obj_type = ilObject::_lookupType($obj_id);
		$class_name = $objDefinition->getClassName($obj_type);
		$class = strtolower("ilObj".$class_name."GUI");
		$ilCtrl->setParameterByClass($class, "ref_id", $ref_id);
		$ilCtrl->redirectByClass($class, "view");
	}
}

?>
