<?php
/*
	FusionPBX
	Version: MPL 1.1

	The contents of this file are subject to the Mozilla Public License Version
	1.1 (the "License"); you may not use this file except in compliance with
	the License. You may obtain a copy of the License at
	http://www.mozilla.org/MPL/

	Software distributed under the License is distributed on an "AS IS" basis,
	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
	for the specific language governing rights and limitations under the
	License.

	The Original Code is FusionPBX

	The Initial Developer of the Original Code is
	Mark J Crane <markjcrane@fusionpbx.com>
	Portions created by the Initial Developer are Copyright (C) 2008-2012
	the Initial Developer. All Rights Reserved.

	Contributor(s):
	Mark J Crane <markjcrane@fusionpbx.com>
*/
require_once "root.php";
require_once "includes/require.php";

//preferences
	$v_label_show = false;
	$v_menu_tab_show = false;
	$v_fax_show = true;
	$v_path_show = true;

//get user defined variables
	if (strlen($_SESSION['user_defined_variables']) == 0) {
		$sql = "select * from v_vars ";
		$sql .= "where var_cat = 'Defaults' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			switch ($row["var_name"]) {
				case "username":
					//not allowed to override this value
					break;
				case "groups":
					//not allowed to override this value
					break;
				case "menu":
					//not allowed to override this value
					break;
				case "template_name":
					//not allowed to override this value
					break;
				case "template_content":
					//not allowed to override this value
					break;
				case "extension_array":
					//not allowed to override this value
					break;
				case "user_extension_array":
					//not allowed to override this value
					break;
				case "user_array":
					//not allowed to override this value
					break;
				default:
					$_SESSION[$row["var_name"]] = $row["var_value"];
			}
		}
		//when this value is cleared it will re-read the user defined variables
		$_SESSION["user_defined_variables"] = "set";
	}

/*
function v_settings() {
	global $db, $domain_uuid;

	//get the program directory
		$program_dir = '';
		$doc_root = $_SERVER["DOCUMENT_ROOT"];
		$doc_root = str_replace ("\\", "/", $doc_root);
		$doc_root_array = explode("/", $doc_root);
		$doc_root_array_count = count($doc_root_array);
		$x = 0;
		foreach ($doc_root_array as $value) {
			$program_dir = $program_dir.$value."/";
			if (($doc_root_array_count-3) == $x) {
				break;
			}
			$x++;
		}
		$program_dir = rtrim($program_dir, "/");

	//get the domains variables
		$sql = "select * from v_domain_settings ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$name = $row['domain_setting_name'];
				$settings_array[$name] = $row['domain_setting_value'];
			}
		}

	//get the server variables
		$sql = "select * from v_server_settings ";
		$sql .= "where domain_uuid = '".$domain_uuid."' ";
		$sql .= "and domain_setting_enabled = 'true' ";
		$prep_statement = $db->prepare($sql);
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_NAMED);
			foreach($result as $row) {
				$name = $row['server_setting_name'];
				$settings_array[$name] = $row['server_setting_value'];
			}
		}

	//return the results
		return $settings_array;
}
//update the settings
//$settings_array = v_settings();
foreach($settings_array as $name => $value) {
	$$name = $value;
}
*/

//create the recordings/archive/year/month/day directory structure
	$v_recording_archive_dir = $_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M")."/".date("d");
	if(!is_dir($v_recording_archive_dir)) {
		mkdir($v_recording_archive_dir, 0764, true);
		chmod($_SESSION['switch']['recordings']['dir']."/archive/".date("Y"), 0764);
		chmod($_SESSION['switch']['recordings']['dir']."/archive/".date("Y")."/".date("M"), 0764);
		chmod($v_recording_archive_dir, 0764);
	}

//get the event socket information
	if (strlen($_SESSION['event_socket_ip_address']) == 0) {
		$sql = "select * from v_settings ";
		$prep_statement = $db->prepare(check_sql($sql));
		if ($prep_statement) {
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			foreach ($result as &$row) {
				$_SESSION['event_socket_ip_address'] = $row["event_socket_ip_address"];
				$_SESSION['event_socket_port'] = $row["event_socket_port"];
				$_SESSION['event_socket_password'] = $row["event_socket_password"];
				break; //limit to 1 row
			}
		}
	}

//get the extensions that are assigned to this user
	if (strlen($_SESSION["user_uuid"]) > 0 && count($_SESSION['user']['extension']) == 0) {
		//get the user extension list
			unset($_SESSION['user']['extension']);
			$sql = "select e.extension, e.user_context, e.extension_uuid, e.outbound_caller_id_name, e.outbound_caller_id_number from v_extensions as e, v_extension_users as u ";
			$sql .= "where e.domain_uuid = '".$_SESSION['domain_uuid']."' ";
			$sql .= "and e.extension_uuid = u.extension_uuid ";
			$sql .= "and u.user_uuid = '".$_SESSION['user_uuid']."' ";
			$sql .= "and e.enabled = 'true' ";
			$sql .= "order by e.extension asc ";
			$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			if (count($result) > 0) {
				$x = 0;
				foreach($result as $row) {
					$_SESSION['user']['extension'][$x]['user'] = $row['extension'];
					$_SESSION['user']['extension'][$x]['extension_uuid'] = $row['extension_uuid'];
					$_SESSION['user']['extension'][$x]['outbound_caller_id_name'] = $row['outbound_caller_id_name'];
					$_SESSION['user']['extension'][$x]['outbound_caller_id_number'] = $row['outbound_caller_id_number'];
					$_SESSION['user_context'] = $row["user_context"];
					$x++;
				}
			}
		//if no extension has been assigned then setting user_context will still need to be set
			if (strlen($_SESSION['user_context']) == 0) {
				if (count($_SESSION['domains']) == 1) {
					$_SESSION['user_context'] = "default";
				}
				else {
					$_SESSION['user_context'] = $_SESSION['domain_name'];
				}
			}
	}

if ($db_type == "sqlite") {
	//sqlite: check if call detail record (CDR) db file exists if not create it
	if (!file_exists($db_file_path.'/'.$server_name.'.cdr.db')) {
		//echo "file does not exist: ".$_SESSION['switch']['db']['dir'].'/cdr.db';
		if (copy($db_file_path.'/cdr.clean.db', $db_file_path.'/'.$server_name.'.cdr.db')) {
			//echo "copy succeeded.\n";
		}
	}
}

function build_menu() {
	global $v_menu_tab_show;

	if ($v_menu_tab_show) {
		global $config;
		if (is_dir($_SERVER["DOCUMENT_ROOT"].'/fusionpbx')){ $relative_url = $_SERVER["DOCUMENT_ROOT"].'/fusionpbx'; } else { $relative_url = '/'; }

		$tab_array = array();
		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_settings.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Settings"), $menu_selected, $relative_url."/v_settings.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplans.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplans.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplan_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/dialplan_details_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Dialplan"), $menu_selected, $relative_url."/dialplans.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_extensions.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_extensions_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Extensions"), $menu_selected, $relative_url."/v_extensions.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_features.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/fax.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/fax_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_hunt_group.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_hunt_group_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_hunt_group_destinations.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_hunt_group_destinations_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_auto_attendant.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_auto_attendant_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_auto_attendant_options_edit.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_modules.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_recordings.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_recordings_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Features"), $menu_selected, $relative_url."/v_features.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_gateways.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_gateways_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Gateways"), $menu_selected, $relative_url."/v_gateways.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_profiles.php") { $menu_selected = true; }
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_profile_edit.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Profiles"), $menu_selected, $relative_url."/v_profiles.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_status.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Status"), $menu_selected, $relative_url."/v_status.php");
		unset($menu_selected);

		$menu_selected = false;
		if ($_SERVER["SCRIPT_NAME"] == $relative_url."/v_vars.php") { $menu_selected = true; }
		$tab_array[] = array(gettext("Vars"), $menu_selected, $relative_url."/v_vars.php");
		unset($menu_selected);
	}
}


function event_socket_create($host, $port, $password){
	$fp = fsockopen($host, $port, $errno, $errdesc, 3);
	socket_set_blocking($fp,false);

	if (!$fp) {
		//error "invalid handle<br />\n";
		//echo "error number: ".$errno."<br />\n";
		//echo "error description: ".$errdesc."<br />\n";
	}
	else {
		//connected to the socket return the handle
		while (!feof($fp)) {
			$buffer = fgets($fp, 1024);
			usleep(100); //allow time for reponse
			if (trim($buffer) == "Content-Type: auth/request") {
				 fputs($fp, "auth $password\n\n");
				 break;
			}
		}
		return $fp;
	}
} //end function


function event_socket_request($fp, $cmd) {
	if ($fp) {
		fputs($fp, $cmd."\n\n");
		usleep(100); //allow time for reponse

		$response = "";
		$i = 0;
		$contentlength = 0;
		while (!feof($fp)) {
			$buffer = fgets($fp, 4096);
			if ($contentlength > 0) {
				$response .= $buffer;
			}

			if ($contentlength == 0) { //if the content has length don't process again
				if (strlen(trim($buffer)) > 0) { //run only if buffer has content
					$temparray = explode(":", trim($buffer));
					if ($temparray[0] == "Content-Length") {
						$contentlength = trim($temparray[1]);
					}
				}
			}

			usleep(20); //allow time for reponse

			//prevent an endless loop //optional because of script timeout
			if ($i > 1000000) { break; }

			if ($contentlength > 0) { //is contentlength set
				//stop reading if all content has been read.
				if (strlen($response) >= $contentlength) {
					break;
				}
			}
			$i++;
		}

		return $response;
	}
	else {
		echo "no handle";
	}
}


function event_socket_request_cmd($cmd) {
	global $db, $domain_uuid, $host;

	$sql = "select * from v_settings ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		$event_socket_ip_address = $row["event_socket_ip_address"];
		$event_socket_port = $row["event_socket_port"];
		$event_socket_password = $row["event_socket_password"];
		break; //limit to 1 row
	}
	unset ($prep_statement);

	$fp = event_socket_create($event_socket_ip_address, $event_socket_port, $event_socket_password);
	$response = event_socket_request($fp, $cmd);
	fclose($fp);
}

function byte_convert($bytes, $decimals = 2) {
	if ($bytes <= 0) { return '0 Bytes'; }
	$convention = 1024;
	$formattedbytes = array_reduce( array(' B', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', 'ZB'), create_function( '$a,$b', 'return is_numeric($a)?($a>='.$convention.'?$a/'.$convention.':number_format($a,'.$decimals.').$b):$a;' ), $bytes );
	return $formattedbytes;
}

function lan_sip_profile() {
	global $config;
	clearstatcache();

	//if the lan directory does not exist then create it
	if (!is_readable($_SESSION['switch']['conf']['dir'].'/sip_profiles/lan/')) {
		exec("mkdir ".$_SESSION['switch']['conf']['dir']."/sip_profiles/lan/");
	}

	//create the LAN profile if it doesn't exist
	if (!file_exists($_SESSION['switch']['conf']['dir'].'/sip_profiles/lan.xml')) {
		$lan_ip = $config['interfaces']['lan']['ipaddr'];
		if (strlen($lan_ip) > 0) {
			exec("cp ".$_SESSION['switch']['conf']['dir']."/sip_profiles/internal.xml ".$_SESSION['switch']['conf']['dir']."/sip_profiles/lan.xml");

			$filename = $_SESSION['switch']['conf']['dir']."/sip_profiles/lan.xml";
			$handle = fopen($filename,"rb");
			$contents = fread($handle, filesize($filename));
			fclose($handle);

			$handle = fopen($filename,"w");
			$contents = str_replace("<profile name=\"internal\">", "<profile name=\"lan\">", $contents);
			$contents = str_replace("<alias name=\"default\"/>", "", $contents);
			$contents = str_replace("<X-PRE-PROCESS cmd=\"include\" data=\"internal/*.xml\"/>", "<X-PRE-PROCESS cmd=\"include\" data=\"lan/*.xml\"/>", $contents);
			$contents = str_replace("<param name=\"rtp-ip\" value=\"\$\${local_ip_v4}\"/>", "<param name=\"rtp-ip\" value=\"".$lan_ip."\"/>", $contents);
			$contents = str_replace("<param name=\"sip-ip\" value=\"\$\${local_ip_v4}\"/>", "<param name=\"sip-ip\" value=\"".$lan_ip."\"/>", $contents);
			fwrite($handle, $contents);
			unset($contents);
			fclose($handle);
			unset($filename);
		}
	}
}

function ListFiles($dir) {
	if($dh = opendir($dir)) {
		$files = Array();
		$inner_files = Array();

		while($file = readdir($dh)) {
			if($file != "." && $file != ".." && $file[0] != '.') {
				if(is_dir($dir . "/" . $file)) {
					//$inner_files = ListFiles($dir . "/" . $file); //recursive
					if(is_array($inner_files)) $files = array_merge($files, $inner_files);
			} else {
					array_push($files, $file);
					//array_push($files, $dir . "/" . $file);
				}
			}
		}
		closedir($dh);
		return $files;
	}
}

function switch_select_destination($select_type, $select_label, $select_name, $select_value, $select_style, $action='') {
	//select_type can be ivr, dialplan, or call_center_contact
	global $config, $db, $domain_uuid;

	if (if_group("superadmin")) {
		echo "<script>\n";
		echo "var Objs;\n";
		echo "\n";
		echo "function changeToInput".$select_name."(obj){\n";
		echo "	tb=document.createElement('INPUT');\n";
		echo "	tb.type='text';\n";
		echo "	tb.name=obj.name;\n";
		echo "	tb.className='formfld';\n";
		echo "	tb.setAttribute('id', '".$select_name."');\n";
		echo "	tb.setAttribute('style', '".$select_style."');\n";
		echo "	tb.value=obj.options[obj.selectedIndex].value;\n";
		echo "	document.getElementById('btn_select_to_input_".$select_name."').style.visibility = 'hidden';\n";
		echo "	tbb=document.createElement('INPUT');\n";
		echo "	tbb.setAttribute('class', 'btn');\n";
		echo "	tbb.type='button';\n";
		echo "	tbb.value='<';\n";
		echo "	tbb.objs=[obj,tb,tbb];\n";
		echo "	tbb.onclick=function(){ Replace".$select_name."(this.objs); }\n";
		echo "	obj.parentNode.insertBefore(tb,obj);\n";
		echo "	obj.parentNode.insertBefore(tbb,obj);\n";
		echo "	obj.parentNode.removeChild(obj);\n";
		echo "	Replace".$select_name."(this.objs);\n";
		echo "}\n";
		echo "\n";
		echo "function Replace".$select_name."(obj){\n";
		echo "	obj[2].parentNode.insertBefore(obj[0],obj[2]);\n";
		echo "	obj[0].parentNode.removeChild(obj[1]);\n";
		echo "	obj[0].parentNode.removeChild(obj[2]);\n";
		echo "	document.getElementById('btn_select_to_input_".$select_name."').style.visibility = 'visible';\n";
		echo "}\n";
		echo "</script>\n";
		echo "\n";
	}

	//default selection found to false
		$selection_found = false;

	if (if_group("superadmin")) {
		echo "		<select name='".$select_name."' id='".$select_name."' class='formfld' style='".$select_style."' onchange='changeToInput".$select_name."(this);'>\n";
		if (strlen($select_value) > 0) {
			if ($select_type == "ivr") {
				echo "		<option value='".$select_value."' selected='selected'>".$select_label."</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "		<option value='".$action.":".$select_value."' selected='selected'>".$select_label."</option>\n";
			}
		}
	}
	else {
		echo "		<select name='".$select_name."' id='".$select_name."' class='formfld' style='".$select_style."'>\n";
	}

	echo "		<option></option>\n";

	//list call center queues
		$sql = "select * from v_call_center_queues ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by queue_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Call Center'>\n";
		}
		$previous_call_center_name = "";
		foreach ($result as &$row) {
			$queue_name = $row["queue_name"];
			$queue_name = str_replace('_${domain_name}@default', '', $queue_name);
			$queue_extension = $row["queue_extension"];
			if ($previous_call_center_name != $queue_name) {
				if ("menu-exec-app:transfer ".$queue_extension." XML ".$_SESSION["context"] == $select_value || "transfer:".$queue_extension." XML ".$_SESSION["context"] == $select_value) {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:transfer ".$queue_extension." XML ".$_SESSION["context"]."' selected='selected'>".$queue_extension." ".$queue_name."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='transfer:".$queue_extension." XML ".$_SESSION["context"]."' selected='selected'>".$queue_extension." ".$queue_name."</option>\n";
					}
					$selection_found = true;
				}
				else {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:transfer ".$queue_extension." XML ".$_SESSION["context"]."'>".$queue_extension." ".$queue_name."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='transfer:".$queue_extension." XML ".$_SESSION["context"]."'>".$queue_extension." ".$queue_name."</option>\n";
					}
				}
				$previous_call_center_name = $queue_name;
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement);

	//list call flows
		if ($select_type == "dialplan" || $select_type == "ivr") {
			$sql = "select * from v_call_flows ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "order by call_flow_extension asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			echo "<optgroup label='Call Flows'>\n";
			foreach ($result as &$row) {
				$call_flow_name = $row["call_flow_name"];
				$call_flow_extension = $row["call_flow_extension"];
				$call_flow_context = $row["call_flow_context"];
				if ("transfer $call_flow_extension XML ".$call_flow_context == $select_value || "transfer:".$call_flow_extension." XML ".$call_flow_context == $select_value) {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:transfer $call_flow_extension XML ".$call_flow_context."' selected='selected'>".$call_flow_extension." ".$call_flow_name."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='transfer:$call_flow_extension XML ".$call_flow_context."' selected='selected'>".$call_flow_extension." ".$call_flow_name."</option>\n";
					}
					$selection_found = true;
				}
				else {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:transfer $call_flow_extension XML ".$call_flow_context."'>".$call_flow_extension." ".$call_flow_name."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='transfer:$call_flow_extension XML ".$call_flow_context."'>".$call_flow_extension." ".$call_flow_name."</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
			unset ($prep_statement, $call_flow_extension);
		}

	//list call groups
		$sql = "select distinct(call_group) from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by call_group asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Call Group'>\n";
		}
		$previous_call_group_name = "";
		foreach ($result as &$row) {
			$call_groups = $row["call_group"];
			$call_group_array = explode(",", $call_groups);
			foreach ($call_group_array as $call_group) {
				$call_group = trim($call_group);
				if ($previous_call_group_name != $call_group) {
					if ("menu-exec-app:bridge group/".$call_group."@".$_SESSION['domain_name'] == $select_value || "bridge:group/".$call_group."@".$_SESSION['domain_name'] == $select_value) {
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:bridge group/".$call_group."@".$_SESSION['domain_name']."' selected='selected'>".$call_group."</option>\n";
						}
						if ($select_type == "dialplan") {
							echo "		<option value='bridge:group/".$call_group."@".$_SESSION['domain_name']."' selected='selected'>".$call_group."</option>\n";
						}
						$selection_found = true;
					}
					else {
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:bridge group/".$call_group."@".$_SESSION['domain_name']."'>".$call_group."</option>\n";
						}
						if ($select_type == "dialplan") {
							echo "		<option value='bridge:group/".$call_group."@".$_SESSION['domain_name']."'>".$call_group."</option>\n";
						}
					}
					$previous_call_group_name = $call_group;
				}
			}
			$x++;
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement);

	//list conference centers
		$sql = "select * from v_conference_centers ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by conference_center_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				echo "<optgroup label='Conference Centers'>\n";
			}
			foreach ($result as &$row) {
				$name = $row["conference_center_name"];
				$extension = $row["conference_center_extension"];
				$description = $row["conference_center_description"];
				if ("execute_extension ".$extension." XML ".$_SESSION['context'] == $select_value || "execute_extension:".$extension." XML ".$_SESSION['context'] == $select_value) {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:execute_extension $extension XML ".$_SESSION['context']."' selected='selected'>".$name." ".$description."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='execute_extension:$extension XML ".$_SESSION['context']."' selected='selected'>".$name." ".$description."</option>\n";
					}
					$selection_found = true;
				}
				else {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:execute_extension $extension XML ".$_SESSION['context']."'>".$name." ".$description."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='execute_extension:".$extension." XML ".$_SESSION['context']."'>".$name." ".$description."</option>\n";
					}
				}
				$x++;
			}
			if ($select_type == "dialplan" || $select_type == "ivr") {
				echo "</optgroup>\n";
			}
			unset ($prep_statement);
		}

	//list conferences
		$sql = "select * from v_conferences ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by conference_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if (count($result) > 0) {
			if ($select_type == "dialplan" || $select_type == "ivr") {
				echo "<optgroup label='Conferences'>\n";
			}
			foreach ($result as &$row) {
				$name = $row["conference_name"];
				$extension = $row["conference_extension"];
				$description = $row["conference_description"];
				if ("execute_extension ".$extension." XML ".$_SESSION['context'] == $select_value || "execute_extension:".$extension." XML ".$_SESSION['context'] == $select_value) {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:execute_extension $extension XML ".$_SESSION['context']."' selected='selected'>".$name." ".$description."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='execute_extension:$extension XML ".$_SESSION['context']."' selected='selected'>".$name." ".$description."</option>\n";
					}
					$selection_found = true;
				}
				else {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:execute_extension $extension XML ".$_SESSION['context']."'>".$name." ".$description."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='execute_extension:".$extension." XML ".$_SESSION['context']."'>".$name." ".$description."</option>\n";
					}
				}
				$x++;
			}
			if ($select_type == "dialplan" || $select_type == "ivr") {
				echo "</optgroup>\n";
			}
			unset ($prep_statement);
		}

	//list destinations
		/*
		$sql = "select * from v_destinations ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and destination_enabled = 'true' ";
		$sql .= "order by destination_name asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Destinations'>\n";
		}
		foreach ($result as &$row) {
			$name = $row["destination_name"];
			$context = $row["destination_context"];
			$extension = $row["destination_extension"];
			$description = $row["destination_description"];
			if ("execute_extension ".$extension." XML ".$context == $select_value || "execute_extension:".$extension." XML ".$context == $select_value) {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:execute_extension $extension XML ".$context."' selected='selected'>".$name." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='execute_extension:$extension XML ".$context."' selected='selected'>".$name." ".$description."</option>\n";
				}
				$selection_found = true;
			}
			else {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:execute_extension $extension XML ".$context."'>".$name." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='execute_extension:".$extension." XML ".$context."'>".$name." ".$description."</option>\n";
				}
			}
			$x++;
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement);
		*/
	//list extensions
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and enabled = 'true' ";
		$sql .= "order by extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
			echo "<optgroup label='Extensions'>\n";
		}
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$context = $row["user_context"];
			$description = $row["description"];
			if ("transfer ".$extension." XML ".$context == $select_value || "transfer:".$extension." XML ".$context == $select_value || "user/$extension@".$_SESSION['domains'][$domain_uuid]['domain_name'] == $select_value) {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer $extension XML ".$context."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:$extension XML ".$context."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "call_center_contact") {
					echo "		<option value='user/$extension@".$_SESSION['domains'][$domain_uuid]['domain_name']."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				$selection_found = true;
			}
			else {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer $extension XML ".$context."'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:$extension XML ".$context."'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "call_center_contact") {
					echo "		<option value='user/$extension@".$_SESSION['domains'][$domain_uuid]['domain_name']."'>".$extension." ".$description."</option>\n";
				}
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement, $extension);

	//list fax extensions
		if ($select_type == "dialplan" || $select_type == "ivr") {
			$sql = "select * from v_fax ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "order by fax_extension asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			echo "<optgroup label='FAX'>\n";
			foreach ($result as &$row) {
				$fax_name = $row["fax_name"];
				$extension = $row["fax_extension"];
				if ("transfer $extension XML ".$_SESSION["context"] == $select_value || "transfer:".$extension." XML ".$_SESSION["context"] == $select_value) {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:transfer $extension XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$fax_name."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='transfer:$extension XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$fax_name."</option>\n";
					}
					$selection_found = true;
				}
				else {
					if ($select_type == "ivr") {
						echo "		<option value='menu-exec-app:transfer $extension XML ".$_SESSION["context"]."'>".$extension." ".$fax_name."</option>\n";
					}
					if ($select_type == "dialplan") {
						echo "		<option value='transfer:$extension XML ".$_SESSION["context"]."'>".$extension." ".$fax_name."</option>\n";
					}
				}
			}
			echo "</optgroup>\n";
			unset ($prep_statement, $extension);
		}

	//list fifo queues
		$sql = "select * from v_dialplan_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "order by dialplan_detail_data asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='FIFO'>\n";
		}
		foreach ($result as &$row) {
			//$dialplan_detail_tag = $row["dialplan_detail_tag"];
			if ($row["dialplan_detail_type"] == "fifo") {
				if (strpos($row["dialplan_detail_data"], '@${domain_name} in') !== false) {
					$dialplan_uuid = $row["dialplan_uuid"];
					//get the extension number using the dialplan_uuid
						$sql = "select dialplan_detail_data as extension_number ";
						$sql .= "from v_dialplan_details ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
						$sql .= "and dialplan_detail_type = 'destination_number' ";
						$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
						$extension_number = $tmp['extension_number'];
						$extension_number = ltrim($extension_number, "^");
						$extension_number = ltrim($extension_number, "\\");
						$extension_number = rtrim($extension_number, "$");
						unset($tmp);

					//get the extension number using the dialplan_uuid
						$sql = "select * ";
						$sql .= "from v_dialplans ";
						$sql .= "where domain_uuid = '$domain_uuid' ";
						$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
						$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
						$dialplan_name = $tmp['dialplan_name'];
						$dialplan_name = str_replace("_", " ", $dialplan_name);
						unset($tmp);

					$fifo_name = $row["dialplan_detail_data"];
					$fifo_name = str_replace('@${domain_name} in', '', $fifo_name);
					$option_label = $extension_number.' '.$dialplan_name;
					if ($select_type == "ivr") {
						if ("menu-exec-app:transfer ".$row["dialplan_detail_data"] == $select_value) {
							echo "		<option value='menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"]."' selected='selected'>".$option_label."</option>\n";
							$selection_found = true;
						}
						else {
							echo "		<option value='menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"]."'>".$option_label."</option>\n";
						}
					}
					if ($select_type == "dialplan") {
						if ("transfer:".$row["dialplan_detail_data"] == $select_value) {
							echo "		<option value='transfer:".$extension_number." XML ".$_SESSION["context"]."' selected='selected'>".$option_label."</option>\n";
							$selection_found = true;
						}
						else {
							echo "		<option value='transfer:".$extension_number." XML ".$_SESSION["context"]."'>".$option_label."</option>\n";
						}
					}
				}
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement);

	//gateways
		if (if_group("superadmin")) {
			if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
				echo "<optgroup label='Gateways'>\n";
			}
			$sql = "select * from v_gateways ";
			$sql .= "where domain_uuid = '$domain_uuid' ";
			$sql .= "and enabled = 'true' ";
			$sql .= "order by gateway asc ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
			$result_count = count($result);
			unset ($prep_statement, $sql);
			$tmp_selected = '';
			foreach($result as $row) {
				if ($row['gateway'] == $select_value) {
					$tmp_selected = "selected='selected'";
				}
					if ($select_type == "dialplan") {
						if (count($_SESSION['domains']) == 1) {
							echo "		<option value='bridge:sofia/gateway/".$row['gateway']."/xxxxx' $tmp_selected>".$row['gateway']."</option>\n";
						}
						else {
							echo "		<option value='bridge:sofia/gateway/".$_SESSION['domain_name']."-".$row['gateway']."/xxxxx' $tmp_selected>".$row['gateway']."</option>\n";
						}
					}
					if ($select_type == "ivr") {
						if (count($_SESSION['domains']) == 1) {
							echo "		<option value='menu-exec-app:bridge sofia/gateway/".$row['gateway']."/xxxxx' $tmp_selected>".$row['gateway']."</option>\n";
						}
						else {
							echo "		<option value='menu-exec-app:bridge sofia/gateway/".$_SESSION['domain_name']."-".$row['gateway']."/xxxxx' $tmp_selected>".$row['gateway']."</option>\n";
						}
					}
					if ($select_type == "call_center_contact") {
						if (count($_SESSION['domains']) == 1) {
							echo "		<option value='sofia/gateway/".$row['gateway']."/xxxxx' $tmp_selected>".$row['gateway']."</option>\n";
						}
						else {
							echo "		<option value='sofia/gateway/".$_SESSION['domain_name']."-".$row['gateway']."/xxxxx' $tmp_selected>".$row['gateway']."</option>\n";
						}
					}
					$tmp_selected = '';
			}
			unset($sql, $result);
			if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
				echo "</optgroup>\n";
			}
		}

	//list hunt groups
		$sql = "select * from v_hunt_groups ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and hunt_group_enabled = 'true' ";
		$sql .= "and ( ";
		$sql .= "hunt_group_type = 'simultaneous' ";
		$sql .= "or hunt_group_type = 'sequence' ";
		$sql .= "or hunt_group_type = 'sequentially' ";
		$sql .= ") ";
		$sql .= "order by hunt_group_extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Hunt Groups'>\n";
		}
		foreach ($result as &$row) {
			$extension = $row["hunt_group_extension"];
			$hunt_group_name = $row["hunt_group_name"];
			if ("transfer $extension XML ".$_SESSION["context"] == $select_value || "transfer:".$extension." XML ".$_SESSION["context"] == $select_value) {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer $extension XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$hunt_group_name."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:$extension XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$hunt_group_name."</option>\n";
				}
				$selection_found = true;
			}
			else {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer $extension XML ".$_SESSION["context"]."'>".$extension." ".$hunt_group_name."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:$extension XML ".$_SESSION["context"]."'>".$extension." ".$hunt_group_name."</option>\n";
				}
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement, $extension);

	//list ivr menus
		$sql = "select * from v_ivr_menus ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and ivr_menu_enabled = 'true' ";
		$sql .= "order by ivr_menu_extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='IVR Menu'>\n";
		}
		foreach ($result as &$row) {
			$extension = $row["ivr_menu_extension"];
			$extension_name = $row["ivr_menu_name"];
			$extension_label = $row["ivr_menu_name"];
			$extension_name = str_replace(" ", "_", $extension_name);
			if (count($_SESSION["domains"]) > 1) {
				$extension_name =  $_SESSION['domains'][$row['domain_uuid']]['domain_name'].'-'.$extension_name;
			}
			if ("ivr:".$extension_name."" == $select_value || "ivr ".$extension_name == $select_value || "transfer:".$extension." XML ".$_SESSION["context"] == $select_value) {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$extension_label."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:".$extension." XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$extension_label."</option>\n";
				}
				$selection_found = true;
			}
			else {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer ".$extension." XML ".$_SESSION["context"]."'>".$extension." ".$extension_label."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:".$extension." XML ".$_SESSION["context"]."'>".$extension." ".$extension_label."</option>\n";
				}
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement, $extension);

	//list ivr menus
		if ($select_type == "ivr") {
			//list sub ivr menu
				$sql = "select * from v_ivr_menus ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and ivr_menu_enabled = 'true' ";
				$sql .= "order by ivr_menu_name asc ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				if ($select_type == "dialplan" || $select_type == "ivr") {
					echo "<optgroup label='IVR Sub'>\n";
				}
				foreach ($result as &$row) {
					$extension_name = $row["ivr_menu_name"];
					$extension_label = $row["ivr_menu_name"];
					$extension_name = str_replace(" ", "_", $extension_name);
					if (count($_SESSION["domains"]) > 1) {
						$extension_name = $_SESSION['domains'][$row['domain_uuid']]['domain_name'].'-'.$extension_name;
					}
					if ($extension_name == $select_value) {
						echo "		<option value='menu-sub:$extension_name' selected='selected'>".$extension_label."</option>\n";
						$selection_found = true;
					}
					else {
						echo "		<option value='menu-sub:$extension_name'>".$extension_label."</option>\n";
					}
				}
				if ($select_type == "dialplan" || $select_type == "ivr") {
					echo "</optgroup>\n";
				}
				unset ($prep_statement, $extension_name);

			//list ivr misc
				if ($select_type == "dialplan" || $select_type == "ivr") {
					echo "<optgroup label='IVR Misc'>\n";
				}
				if ($ivr_menu_option_action == "menu-top") {
					echo "		<option value='menu-top:' selected='selected'>Top</option>\n";
					$selection_found = true;
				}
				else {
					echo "		<option value='menu-top:'>Top</option>\n";
				}
				if ($ivr_menu_option_action == "menu-exit") {
					echo "		<option value='menu-exit:' selected='selected'>Exit</option>\n";
					$selection_found = true;
				}
				else {
					echo "		<option value='menu-exit:'>Exit</option>\n";
				}
				if (strlen($select_value) > 0) {
					if (!$selection_found) {
						echo "		<option value='$select_value' selected='selected'>".$select_value."</option>\n";
					}
				}
				if ($select_type == "dialplan" || $select_type == "ivr") {
					echo "</optgroup>\n";
				}
		}

	//list the languages
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Language'>\n";
		}
		//dutch
		if ("menu-exec-app:set default_language=nl" == $select_value || "set:default_language=nl" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=nl' selected='selected'>Dutch</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=nl' selected='selected'>Dutch</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=nl'>Dutch</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=nl'>Dutch</option>\n";
			}
		}
		//english
		if ("menu-exec-app:set default_language=en" == $select_value || "set:default_language=en" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=en' selected='selected'>English</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=en' selected='selected'>English</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=en'>English</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=en'>English</option>\n";
			}
		}
		//french
		if ("menu-exec-app:set default_language=fr" == $select_value || "set:default_language=fr" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=fr' selected='selected'>French</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=fr' selected='selected'>French</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=fr'>French</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=fr'>French</option>\n";
			}
		}
		//italian
		if ("menu-exec-app:set default_language=it" == $select_value || "set:default_language=it" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=it' selected='selected'>Italian</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=it' selected='selected'>Italian</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=it'>Italian</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=it'>Italian</option>\n";
			}
		}
		//german
		if ("menu-exec-app:set default_language=de" == $select_value || "set:default_language=de" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=de' selected='selected'>German</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=de' selected='selected'>German</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=de'>German</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=de'>German</option>\n";
			}
		}
		//portuguese - portugal
		if ("menu-exec-app:set default_language=de" == $select_value || "set:default_language=de" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=pt-pt' selected='selected'>Portuguese - Portugal</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=pt-pt' selected='selected'>Portuguese - Portugal</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=pt-pt'>Portuguese - Portuguese - Portugal</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=pt-pt'>Portuguese - Portugal</option>\n";
			}
		}
		//portuguese - brazil
		if ("menu-exec-app:set default_language=pt-br" == $select_value || "set:default_language=de" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=pt-br' selected='selected'>Portuguese - Brazil</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=pt-br' selected='selected'>Portuguese - Brazil</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=pt-br'>Portuguese - Brazil</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=pt-br'>Portuguese - Brazil</option>\n";
			}
		}
		//spanish
		if ("menu-exec-app:set default_language=es" == $select_value || "set:default_language=es" == $select_value) {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=es' selected='selected'>Spanish</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=es' selected='selected'>Spanish</option>\n";
			}
		}
		else {
			if ($select_type == "ivr") {
				echo "	<option value='menu-exec-app:set default_language=es'>Spanish</option>\n";
			}
			if ($select_type == "dialplan") {
				echo "	<option value='set:default_language=es'>Spanish</option>\n";
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}

	//recordings
		if ($select_type == "dialplan" || $select_type == "ivr") {
			if($dh = opendir($_SESSION['switch']['recordings']['dir']."/")) {
				$tmp_selected = false;
				$files = Array();
				echo "<optgroup label='Recordings'>\n";
				while($file = readdir($dh)) {
					if($file != "." && $file != ".." && $file[0] != '.') {
						if(is_dir($_SESSION['switch']['recordings']['dir'] . "/" . $file)) {
							//this is a directory
						}
						else {
							if ($ivr_menu_greet_long == $_SESSION['switch']['recordings']['dir']."/".$file) {
								$tmp_selected = true;
								if ($select_type == "dialplan") {
									echo "		<option value='playback:".$_SESSION['switch']['recordings']['dir']."/".$file."' selected='selected'>".$file."</option>\n";
								}
								if ($select_type == "ivr") {
									echo "		<option value='menu-exec-app:playback ".$_SESSION['switch']['recordings']['dir']."/".$file."' selected='selected'>".$file."</option>\n";
								}
							}
							else {
								if ($select_type == "dialplan") {
									echo "		<option value='playback:".$_SESSION['switch']['recordings']['dir']."/".$file."'>".$file."</option>\n";
								}
								if ($select_type == "ivr") {
									echo "		<option value='menu-exec-app:playback ".$_SESSION['switch']['recordings']['dir']."/".$file."'>".$file."</option>\n";
								}
							}
						}
					}
				}
				closedir($dh);
				echo "</optgroup>\n";
			}
		}

	//ring groups
		$sql = "select * from v_ring_groups ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and ring_group_enabled = 'true' ";
		$sql .= "order by ring_group_extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Ring Groups'>\n";
		}
		foreach ($result as &$row) {
			$extension = $row["ring_group_extension"];
			$context = $row["ring_group_context"];
			$description = $row["ring_group_description"];
			if ("transfer ".$extension." XML ".$context == $select_value || "transfer:".$extension." XML ".$context == $select_value) {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer $extension XML ".$context."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:$extension XML ".$context."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				$selection_found = true;
			}
			else {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer $extension XML ".$context."'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:$extension XML ".$context."'>".$extension." ".$description."</option>\n";
				}
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}

	//list time conditions
		$sql = "select * from v_dialplan_details ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$x = 0;
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			//$dialplan_detail_tag = $row["dialplan_detail_tag"];
			switch ($row['dialplan_detail_type']) {
			case "hour":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "minute":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "minute-of-day":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "mday":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "mweek":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "mon":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "yday":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "year":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "wday":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			case "week":
				$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			default:
				//$time_array[$row['dialplan_uuid']] = $row['dialplan_detail_type'];
				break;
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Time Conditions'>\n";
		}
		foreach($time_array as $key=>$val) {
			$dialplan_uuid = $key;
			//get the extension number using the dialplan_uuid
				$sql = "select dialplan_detail_data as extension_number ";
				$sql .= "from v_dialplan_details ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
				$sql .= "and dialplan_detail_type = 'destination_number' ";
				$sql .= "order by extension_number asc ";
				$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
				$extension_number = $tmp['extension_number'];
				$extension_number = ltrim($extension_number, "^");
				$extension_number = ltrim($extension_number, "\\");
				$extension_number = rtrim($extension_number, "$");
				unset($tmp);

			//get the extension number using the dialplan_uuid
				$sql = "select * ";
				$sql .= "from v_dialplans ";
				$sql .= "where domain_uuid = '$domain_uuid' ";
				$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
				$tmp = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
				$dialplan_name = $tmp['dialplan_name'];
				$dialplan_name = str_replace("_", " ", $dialplan_name);
				unset($tmp);

				$option_label = $extension_number.' '.$dialplan_name;
				if ($select_type == "ivr") {
					if ("menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"] == $select_value) {
						echo "		<option value='menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"]."' selected='selected'>".$option_label."</option>\n";
						$selection_found = true;
					}
					else {
						echo "		<option value='menu-exec-app:transfer ".$extension_number." XML ".$_SESSION["context"]."'>".$option_label."</option>\n";
					}
				}
				if ($select_type == "dialplan") {
					if ("transfer:".$extension_number == $select_value) {
						echo "		<option value='transfer:".$extension_number." XML ".$_SESSION["context"]."' selected='selected'>".$option_label."</option>\n";
						$selection_found = true;
					}
					else {
						echo "		<option value='transfer:".$extension_number." XML ".$_SESSION["context"]."'>".$option_label."</option>\n";
					}
				}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}
		unset ($prep_statement);

	//list voicemail
		$sql = "select * from v_extensions ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$sql .= "and enabled = 'true' ";
		$sql .= "order by extension asc ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "<optgroup label='Voicemail'>\n";
		}
		foreach ($result as &$row) {
			$extension = $row["extension"];
			$context = $row["user_context"];
			$description = $row["description"];
			if ("voicemail default \${domain_name} ".$extension == $select_value || "transfer:*99".$extension." XML ".$_SESSION["context"] == $select_value || "voicemail:default \${domain_name} ".$extension == $select_value) {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer *99".$extension." XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:*99".$extension." XML ".$_SESSION["context"]."' selected='selected'>".$extension." ".$description."</option>\n";
				}
				$selection_found = true;
			}
			else {
				if ($select_type == "ivr") {
					echo "		<option value='menu-exec-app:transfer *99".$extension." XML ".$_SESSION["context"]."'>".$extension." ".$description."</option>\n";
				}
				if ($select_type == "dialplan") {
					echo "		<option value='transfer:*99".$extension." XML ".$_SESSION["context"]."'>".$extension." ".$description."</option>\n";
				}
			}
		}
		if ($select_type == "dialplan" || $select_type == "ivr") {
			echo "</optgroup>\n";
		}

	//other
		if (if_group("superadmin")) {
			if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
				echo "<optgroup label='Other'>\n";
			}
				if ($select_type == "dialplan" || $select_type == "ivr") {
					//set the default value
						$selected = '';
					//answer
						if ($select_value == "answer") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='answer' $selected>answer</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:answer' $selected>answer</option>\n";
						}
					//bridge
						if ($select_value == "bridge") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='bridge:' $selected>bridge</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:bridge ' $selected>bridge</option>\n";
						}
					//db
						if ($select_value == "db") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='db:' $selected>db</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:db ' $selected>db</option>\n";
						}
					//export
						if ($select_value == "export") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='export:' $selected>export</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:export ' $selected>export</option>\n";
						}
					//global_set
						if ($select_value == "global_set") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='global_set:' $selected>global_set</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:global_set ' $selected>global_set</option>\n";
						}
					//group
						if ($select_value == "group") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='group:' $selected>group</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:group ' $selected>group</option>\n";
						}
					//hangup
						if ($select_value == "hangup") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='hangup' $selected>hangup</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:hangup' $selected>hangup</option>\n";
						}
					//info
						if ($select_value == "info") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='info' $selected>info</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:info' $selected>info</option>\n";
						}
					//javascript
						if ($select_value == "javascript") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='javascript:' $selected>javascript</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:javascript ' $selected>javascript</option>\n";
						}
					//lua
						if ($select_value == "lua") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='lua:' $selected>lua</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:lua ' $selected>lua</option>\n";
						}
					//perl
						if ($select_value == "perl") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='perl:' $selected>perl</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:perl ' $selected>perl</option>\n";
						}
					//reject
						if ($select_value == "reject") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='reject' $selected>reject</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:reject' $selected>reject</option>\n";
						}
					//set
						if ($select_value == "set") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='set:' $selected>set</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:set ' $selected>set</option>\n";
						}
					//sleep
						if ($select_value == "sleep") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='sleep:' $selected>sleep</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:sleep ' $selected>sleep</option>\n";
						}
					//transfer
						if ($select_value == "transfer") { $selected = "selected='selected'"; }
						if ($select_type == "dialplan") {
							echo "		<option value='transfer:' $selected>transfer</option>\n";
						}
						if ($select_type == "ivr") {
							echo "		<option value='menu-exec-app:transfer ' $selected>transfer</option>\n";
						}
					//other
						if ($select_value == "other") {
							echo "		<option value='' selected='selected'>other</option>\n";
						} else {
							echo "		<option value=''>other</option>\n";
						}
				}
				if (!$selection_found) {
					if (strlen($select_label) > 0) {
						echo "		<option value='".$select_value."' selected='selected'>".$select_label."</option>\n";
					}
					else {
						echo "		<option value='".$select_value."' selected='selected'>".$select_value."</option>\n";
					}
				}
			if ($select_type == "dialplan" || $select_type == "ivr" || $select_type == "call_center_contact") {
				echo "</optgroup>\n";
			}
		}

		/*
		//echo "    <option value='answer'>answer</option>\n";
		//echo "    <option value='bridge'>bridge</option>\n";
		echo "    <option value='cond'>cond</option>\n";
		//echo "    <option value='db'>db</option>\n";
		//echo "    <option value='global_set'>global_set</option>\n";
		//echo "    <option value='group'>group</option>\n";
		echo "    <option value='expr'>expr</option>\n";
		//echo "    <option value='export'>export</option>\n";
		//echo "    <option value='hangup'>hangup</option>\n";
		//echo "    <option value='info'>info</option>\n";
		//echo "    <option value='javascript'>javascript</option>\n";
		//echo "    <option value='lua'>lua</option>\n";
		echo "    <option value='playback'>playback</option>\n";
		echo "    <option value='read'>read</option>\n";
		//echo "    <option value='reject'>reject</option>\n";
		echo "    <option value='respond'>respond</option>\n";
		echo "    <option value='ring_ready'>ring_ready</option>\n";
		//echo "    <option value='set'>set</option>\n";
		echo "    <option value='set_user'>set_user</option>\n";
		//echo "    <option value='sleep'>sleep</option>\n";
		echo "    <option value='sofia_contact'>sofia_contact</option>\n";
		//echo "    <option value='transfer'>transfer</option>\n";
		echo "    <option value='conference'>conference</option>\n";
		echo "    <option value='conference_set_auto_outcall'>conference_set_auto_outcall</option>\n";
		*/
		unset ($prep_statement, $extension);

	echo "		</select>\n";
	if (if_group("superadmin")) {
		echo "<input type='button' id='btn_select_to_input_".$select_name."' class='btn' name='' alt='back' onclick='changeToInput".$select_name."(document.getElementById(\"".$select_name."\"));this.style.visibility = \"hidden\";' value='<'>";
	}
}

function save_setting_xml() {
	global $db, $domain_uuid, $host, $config;

	$sql = "select * from v_settings ";
	$prep_statement = $db->prepare(check_sql($sql));
	if ($prep_statement) {
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			$fout = fopen($_SESSION['switch']['conf']['dir']."/directory/default/default.xml","w");
			$xml = "<include>\n";
			$xml .= "  <user id=\"default\"> <!--if id is numeric mailbox param is not necessary-->\n";
			$xml .= "    <variables>\n";
			$xml .= "      <!--all variables here will be set on all inbound calls that originate from this user -->\n";
			$xml .= "      <!-- set these to take advantage of a dialplan localized to this user -->\n";
			$xml .= "      <variable name=\"numbering_plan\" value=\"" . $row['numbering_plan'] . "\"/>\n";
			$xml .= "      <variable name=\"default_gateway\" value=\"" . $row['default_gateway'] . "\"/>\n";
			$xml .= "      <variable name=\"default_area_code\" value=\"" . $row['default_area_code'] . "\"/>\n";
			$xml .= "    </variables>\n";
			$xml .= "  </user>\n";
			$xml .= "</include>\n";
			fwrite($fout, $xml);
			unset($xml);
			fclose($fout);

			$event_socket_ip_address = $row['event_socket_ip_address'];
			if (strlen($event_socket_ip_address) == 0) { $event_socket_ip_address = '127.0.0.1'; }

			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/event_socket.conf.xml","w");
			$xml = "<configuration name=\"event_socket.conf\" description=\"Socket Client\">\n";
			$xml .= "  <settings>\n";
			$xml .= "    <param name=\"listen-ip\" value=\"" . $event_socket_ip_address . "\"/>\n";
			$xml .= "    <param name=\"listen-port\" value=\"" . $row['event_socket_port'] . "\"/>\n";
			$xml .= "    <param name=\"password\" value=\"" . $row['event_socket_password'] . "\"/>\n";
			$xml .= "    <!--<param name=\"apply-inbound-acl\" value=\"lan\"/>-->\n";
			$xml .= "  </settings>\n";
			$xml .= "</configuration>";
			fwrite($fout, $xml);
			unset($xml, $event_socket_password);
			fclose($fout);

			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_rpc.conf.xml","w");
			$xml = "<configuration name=\"xml_rpc.conf\" description=\"XML RPC\">\n";
			$xml .= "  <settings>\n";
			$xml .= "    <!-- The port where you want to run the http service (default 8080) -->\n";
			$xml .= "    <param name=\"http-port\" value=\"" . $row['xml_rpc_http_port'] . "\"/>\n";
			$xml .= "    <!-- if all 3 of the following params exist all http traffic will require auth -->\n";
			$xml .= "    <param name=\"auth-realm\" value=\"" . $row['xml_rpc_auth_realm'] . "\"/>\n";
			$xml .= "    <param name=\"auth-user\" value=\"" . $row['xml_rpc_auth_user'] . "\"/>\n";
			$xml .= "    <param name=\"auth-pass\" value=\"" . $row['xml_rpc_auth_pass'] . "\"/>\n";
			$xml .= "  </settings>\n";
			$xml .= "</configuration>\n";
			fwrite($fout, $xml);
			unset($xml);
			fclose($fout);

			//shout.conf.xml
				$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/shout.conf.xml","w");
				$xml = "<configuration name=\"shout.conf\" description=\"mod shout config\">\n";
				$xml .= "  <settings>\n";
				$xml .= "    <!-- Don't change these unless you are insane -->\n";
				$xml .= "    <param name=\"decoder\" value=\"" . $row['mod_shout_decoder'] . "\"/>\n";
				$xml .= "    <param name=\"volume\" value=\"" . $row['mod_shout_volume'] . "\"/>\n";
				$xml .= "    <!--<param name=\"outscale\" value=\"8192\"/>-->\n";
				$xml .= "  </settings>\n";
				$xml .= "</configuration>";
				fwrite($fout, $xml);
				unset($xml);
				fclose($fout);

			break; //limit to 1 row
		}
		unset ($prep_statement);
	}

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function filename_safe($filename) {
	// lower case
		$filename = strtolower($filename);

	// replace spaces with a '_'
		$filename = str_replace(" ", "_", $filename);

	// loop through string
		$result = '';
		for ($i=0; $i<strlen($filename); $i++) {
			if (preg_match('([0-9]|[a-z]|_)', $filename[$i])) {
				$result .= $filename[$i];
			}
		}

	// return filename
		return $result;
}

function save_gateway_xml() {

	//declare the global variables
		global $db, $domain_uuid, $config;

	//delete all old gateways to prepare for new ones
		if (count($_SESSION["domains"]) > 1) {
			$v_needle = 'v_'.$_SESSION['domain_name'].'-';
		}
		else {
			$v_needle = 'v_';
		}
		$gateway_list = glob($_SESSION['switch']['gateways']['dir'] . "/*/".$v_needle."*.xml");
		foreach ($gateway_list as $gateway_file) {
			unlink($gateway_file);
		}

	//get the list of gateways and write the xml
		$sql = "select * from v_gateways ";
		$sql .= "where domain_uuid = '$domain_uuid' ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
			if ($row['enabled'] != "false") {
					//remove invalid characters from the file names
						$gateway = $row['gateway'];
						$gateway = str_replace(" ", "_", $gateway);
						$gateway = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $gateway);
					//set the default profile as external
						$profile = $row['profile'];
						if (strlen($profile) == 0) {
							$profile = "external";
						}
					if (count($_SESSION["domains"]) > 1) {
						$fout = fopen($_SESSION['switch']['gateways']['dir']."/".$profile."/v_".$_SESSION['domain_name'].'-'.$gateway.".xml","w");
						$xml .= "<include>\n";
						$xml .= "    <gateway name=\"". $_SESSION['domain_name'] .'-'. $gateway . "\">\n";
					}
					else {
						$fout = fopen($_SESSION['switch']['gateways']['dir']."/".$profile."/v_".$gateway.".xml","w");
						$xml .= "<include>\n";
						$xml .= "    <gateway name=\"" . $gateway . "\">\n";
					}
					if (strlen($row['username']) > 0) {
						$xml .= "      <param name=\"username\" value=\"" . $row['username'] . "\"/>\n";
					}
					else {
						$xml .= "      <param name=\"username\" value=\"register:false\"/>\n";
					}
					if (strlen($row['distinct_to']) > 0) {
						$xml .= "      <param name=\"distinct-to\" value=\"" . $row['distinct_to'] . "\"/>\n";
					}
					if (strlen($row['auth_username']) > 0) {
						$xml .= "      <param name=\"auth-username\" value=\"" . $row['auth_username'] . "\"/>\n";
					}
					if (strlen($row['password']) > 0) {
						$xml .= "      <param name=\"password\" value=\"" . $row['password'] . "\"/>\n";
					}
					else {
						$xml .= "      <param name=\"password\" value=\"register:false\"/>\n";
					}
					if (strlen($row['realm']) > 0) {
						$xml .= "      <param name=\"realm\" value=\"" . $row['realm'] . "\"/>\n";
					}
					if (strlen($row['from_user']) > 0) {
						$xml .= "      <param name=\"from-user\" value=\"" . $row['from_user'] . "\"/>\n";
					}
					if (strlen($row['from_domain']) > 0) {
						$xml .= "      <param name=\"from-domain\" value=\"" . $row['from_domain'] . "\"/>\n";
					}
					if (strlen($row['proxy']) > 0) {
						$xml .= "      <param name=\"proxy\" value=\"" . $row['proxy'] . "\"/>\n";
					}
					if (strlen($row['register_proxy']) > 0) {
										$xml .= "      <param name=\"register-proxy\" value=\"" . $row['register_proxy'] . "\"/>\n";
					}
					if (strlen($row['outbound_proxy']) > 0) {
							$xml .= "      <param name=\"outbound-proxy\" value=\"" . $row['outbound_proxy'] . "\"/>\n";
					}
					if (strlen($row['expire_seconds']) > 0) {
						$xml .= "      <param name=\"expire-seconds\" value=\"" . $row['expire_seconds'] . "\"/>\n";
					}
					if (strlen($row['register']) > 0) {
						$xml .= "      <param name=\"register\" value=\"" . $row['register'] . "\"/>\n";
					}

					if (strlen($row['register_transport']) > 0) {
						switch ($row['register_transport']) {
						case "udp":
							$xml .= "      <param name=\"register-transport\" value=\"udp\"/>\n";
							break;
						case "tcp":
							$xml .= "      <param name=\"register-transport\" value=\"tcp\"/>\n";
							break;
						case "tls":
							$xml .= "      <param name=\"register-transport\" value=\"tls\"/>\n";
							$xml .= "      <param name=\"contact-params\" value=\"transport=tls\"/>\n";
							break;
						default:
							$xml .= "      <param name=\"register-transport\" value=\"" . $row['register_transport'] . "\"/>\n";
						}
					  }

					if (strlen($row['retry_seconds']) > 0) {
						$xml .= "      <param name=\"retry-seconds\" value=\"" . $row['retry_seconds'] . "\"/>\n";
					}
					if (strlen($row['extension']) > 0) {
						$xml .= "      <param name=\"extension\" value=\"" . $row['extension'] . "\"/>\n";
					}
					if (strlen($row['ping']) > 0) {
						$xml .= "      <param name=\"ping\" value=\"" . $row['ping'] . "\"/>\n";
					}
					if (strlen($row['context']) > 0) {
						$xml .= "      <param name=\"context\" value=\"" . $row['context'] . "\"/>\n";
					}
					if (strlen($row['caller_id_in_from']) > 0) {
						$xml .= "      <param name=\"caller-id-in-from\" value=\"" . $row['caller_id_in_from'] . "\"/>\n";
					}
					if (strlen($row['supress_cng']) > 0) {
						$xml .= "      <param name=\"supress-cng\" value=\"" . $row['supress_cng'] . "\"/>\n";
					}
					if (strlen($row['sip_cid_type']) > 0) {
						$xml .= "      <param name=\"sip_cid_type\" value=\"" . $row['sip_cid_type'] . "\"/>\n";
					}
					if (strlen($row['extension_in_contact']) > 0) {
						$xml .= "      <param name=\"extension-in-contact\" value=\"" . $row['extension_in_contact'] . "\"/>\n";
					}

					$xml .= "    </gateway>\n";
					$xml .= "</include>";

					fwrite($fout, $xml);
					unset($xml);
					fclose($fout);
			}

		} //end foreach
		unset($prep_statement);

	//apply settings
		$_SESSION["reload_xml"] = true;

}


function save_module_xml() {
	global $config, $db, $domain_uuid;

	$xml = "";
	$xml .= "<configuration name=\"modules.conf\" description=\"Modules\">\n";
	$xml .= "	<modules>\n";

	$sql = "select * from v_modules ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$prev_module_cat = '';
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $row) {
		if ($prev_module_cat != $row['module_cat']) {
			$xml .= "\n		<!-- ".$row['module_cat']." -->\n";
		}
		if ($row['module_enabled'] == "true"){
			$xml .= "		<load module=\"".$row['module_name']."\"/>\n";
		}
		$prev_module_cat = $row['module_cat'];
	}
	$xml .= "\n";
	$xml .= "	</modules>\n";
	$xml .= "</configuration>";

	$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/modules.conf.xml","w");
	fwrite($fout, $xml);
	unset($xml);
	fclose($fout);

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function save_var_xml() {
	global $config, $db, $domain_uuid;

	$fout = fopen($_SESSION['switch']['conf']['dir']."/vars.xml","w");
	$xml = '';

	$sql = "select * from v_vars ";
	$sql .= "where var_enabled = 'true' ";
	$sql .= "order by var_cat, var_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$prev_var_cat = '';
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		if ($row['var_cat'] != 'Provision') {
			if ($prev_var_cat != $row['var_cat']) {
				$xml .= "\n<!-- ".$row['var_cat']." -->\n";
				if (strlen($row["var_description"]) > 0) {
					$xml .= "<!-- ".base64_decode($row['var_description'])." -->\n";
				}
			}
			$xml .= "<X-PRE-PROCESS cmd=\"set\" data=\"".$row['var_name']."=".$row['var_value']."\"/>\n";
		}
		$prev_var_cat = $row['var_cat'];
	}
	$xml .= "\n";
	fwrite($fout, $xml);
	unset($xml);
	fclose($fout);

	//apply settings
		$_SESSION["reload_xml"] = true;

	//$cmd = "api reloadxml";
	//event_socket_request_cmd($cmd);
	//unset($cmd);
}

function outbound_route_to_bridge ($domain_uuid, $destination_number) {
	global $db;

	$destination_number = trim($destination_number);
	if (is_numeric($destination_number)) {
		//not found, continue to process the function
	}
	else {
		//not a number, brige_array and exit the function
		$bridge_array[0] = $destination_number;
		return $bridge_array;
	}

	$sql = "select * from v_dialplans ";
	$sql .= "where domain_uuid = '".$domain_uuid."' ";
	$sql .= "and app_uuid = '8c914ec3-9fc0-8ab5-4cda-6c9288bdc9a3' ";
	$sql .= "and dialplan_enabled = 'true' ";
	$sql .= "order by dialplan_order asc ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	$x = 0;
	foreach ($result as &$row) {
		//set as variables
			$dialplan_uuid = $row['dialplan_uuid'];
			$dialplan_detail_tag = $row["dialplan_detail_tag"];
			$dialplan_detail_type = $row['dialplan_detail_type'];
			$dialplan_continue = $row['dialplan_continue'];

		//get the extension number using the dialplan_uuid
			$sql = "select * ";
			$sql .= "from v_dialplan_details ";
			$sql .= "where domain_uuid = '".$domain_uuid."' ";
			$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
			$sql .= "order by dialplan_detail_order asc ";
			$sub_result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
			$regex_match = false;
			foreach ($sub_result as &$sub_row) {
				if ($sub_row['dialplan_detail_tag'] == "condition") {
					if ($sub_row['dialplan_detail_type'] == "destination_number") {
							$dialplan_detail_data = $sub_row['dialplan_detail_data'];
							$pattern = '/'.$dialplan_detail_data.'/';
							preg_match($pattern, $destination_number, $matches, PREG_OFFSET_CAPTURE);
							if (count($matches) == 0) {
								$regex_match = false;
							}
							else {
								$regex_match = true;
								$regex_match_1 = $matches[1][0];
								$regex_match_2 = $matches[2][0];
								$regex_match_3 = $matches[3][0];
								$regex_match_4 = $matches[4][0];
								$regex_match_5 = $matches[5][0];
							}
					}
				}
			}
			if ($regex_match) {
				foreach ($sub_result as &$sub_row) {
					$dialplan_detail_data = $sub_row['dialplan_detail_data'];
					if ($sub_row['dialplan_detail_tag'] == "action" && $sub_row['dialplan_detail_type'] == "bridge" && $dialplan_detail_data != "\${enum_auto_route}") {
					$dialplan_detail_data = str_replace("\$1", $regex_match_1, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$2", $regex_match_2, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$3", $regex_match_3, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$4", $regex_match_4, $dialplan_detail_data);
						$dialplan_detail_data = str_replace("\$5", $regex_match_5, $dialplan_detail_data);
						//echo "dialplan_detail_data: $dialplan_detail_data";
						$bridge_array[$x] = $dialplan_detail_data;
						$x++;
						if ($dialplan_continue == "false") {
							break 2;
						}
					}
				}
			}
	}
	return $bridge_array;
	unset ($prep_statement);
}
//$destination_number = '1231234';
//$bridge_array = outbound_route_to_bridge ($domain_uuid, $destination_number);
//foreach ($bridge_array as &$bridge) {
//	echo "bridge: ".$bridge."<br />";
//}

function extension_exists($extension) {
	global $db, $domain_uuid;
	$sql = "select * from v_extensions ";
	$sql .= "where domain_uuid = '$domain_uuid' ";
	$sql .= "and extension = '$extension' ";
	$sql .= "and enabled = 'true' ";
	$result = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
	if (count($result) > 0) {
		return true;
	}
	else {
		return false;
	}
}

function save_hunt_group_xml() {

	//Hunt Group Lua Notes:
		//get the domain
		//loop through all Hunt Groups
			//get the Hunt Group information such as the name and description
			//add each Hunt Group to the dialplan
			//get the list of destinations then build the Hunt Group lua

	//get the domain
		global $db, $domain_uuid, $host, $config;

		$tmp = "";
		$tmp .= "\n";
		$tmp .= " domain_name = \"".$domain."\"; //by default this is the ipv4 address of FreeSWITCH used for transfer to voicemail\n";
		$tmp .= "\n";
		$tmp .= "\n";

	//prepare for hunt group .lua files to be written. delete all hunt groups that are prefixed with huntgroup_ and have a file extension of .lua
		$v_prefix = 'v_huntgroup_';
		if($dh = opendir($_SESSION['switch']['scripts']['dir'])) {
			$files = Array();
			while($file = readdir($dh)) {
				if($file != "." && $file != ".." && $file[0] != '.') {
					if(is_dir($dir . "/" . $file)) {
						//this is a directory
					} else {
						if (substr($file,0, strlen($v_prefix)) == $v_prefix && substr($file,-4) == '.lua') {
							if ($file != "huntgroup_originate.lua") {
								unlink($_SESSION['switch']['scripts']['dir'].'/'.$file);
							}
						}
					}
				}
			}
			closedir($dh);
		}

	//loop through all Hunt Groups
		$x = 0;

		$sql = "select * from v_hunt_groups ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		foreach ($result as &$row) {
				//get the Hunt Group information such as the name and description
					//$row['hunt_group_uuid']
					//$row['hunt_group_extension']
					//$row['hunt_group_name']
					//$row['hunt_group_type']
					//$row['hunt_group_timeout']
					//$row['hunt_group_context']
					//$row['hunt_group_ringback']
					//$row['hunt_group_cid_name_prefix']
					//$row['hunt_group_pin']
					//$row['hunt_group_caller_announce']
					//$row['hunt_group_enabled']
					//$row['hunt_group_description']
					$domain_uuid = $row['domain_uuid'];
					$dialplan_uuid = $row['dialplan_uuid'];

				//add each hunt group to the dialplan
					if (strlen($row['hunt_group_uuid']) > 0) {
						//set default action to add
							$action = 'add';
						//check whether the dialplan entry exists in the database
							$action = 'add'; //set default action to add
							$i = 0;
							$sql = "select count(*) as num_rows from v_dialplans ";
							$sql .= "where domain_uuid = '".$domain_uuid."' ";
							$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
							$prep_statement_2 = $db->prepare(check_sql($sql));
							if ($prep_statement_2) {
								$prep_statement_2->execute();
								$row2 = $prep_statement_2->fetch(PDO::FETCH_ASSOC);
								if ($row2['num_rows'] > 0) {
									//$num_rows = $row2['num_rows'];
									$action = 'update';
								}
							}
							unset($prep_statement, $result);

						if ($action == 'add') {
							//create dialplan entry for each huntgroup
								$app_uuid = '0610f841-2e27-4c5f-7926-08ab3aad02e0';
								if ($row['hunt_group_enabled'] == "false") {
									$dialplan_enabled = 'false';
								}
								else {
									$dialplan_enabled = 'true';
								}
								if (strlen($dialplan_uuid) == 0) {
									//create a dialplan uuid
										$dialplan_uuid = uuid();
									//update the hunt groups table with the database
										$sql = "update v_hunt_groups ";
										$sql .= "set dialplan_uuid = '".$dialplan_uuid."' ";
										$sql .= "where domain_uuid = '".$domain_uuid."' ";
										$sql .= "and hunt_group_uuid = '".$row['hunt_group_uuid']."' ";
										$db->query($sql);
										unset($sql);
								}

								require_once "includes/classes/switch_dialplan.php";
								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->app_uuid = $app_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_name = $row['hunt_group_name'];
								//$dialplan->dialplan_continue = $dialplan_continue;
								$dialplan->dialplan_order = '330';
								$dialplan->dialplan_context = $_SESSION['context'];
								$dialplan->dialplan_enabled = $dialplan_enabled;
								$dialplan->dialplan_description = $row['hunt_group_description'];
								$dialplan->dialplan_add();
								unset($dialplan);
						}

						if ($action == 'update') {
							//update the huntgroup fifo
								$dialplan_name = $row['hunt_group_name'];
								$dialplan_order = '330';
								$context = $row['hunt_group_context'];
								if ($row['hunt_group_enabled'] == "false") {
									$enabled = 'false';
								}
								else {
									$enabled = 'true';
								}
								$description = $row['hunt_group_description'];
								$hunt_group_uuid = $row['hunt_group_uuid'];

								$sql = "update v_dialplans set ";
								$sql .= "dialplan_name = '$dialplan_name', ";
								$sql .= "dialplan_order = '$dialplan_order', ";
								$sql .= "dialplan_context = '$context', ";
								$sql .= "dialplan_enabled = '$enabled', ";
								$sql .= "dialplan_description = '$description' ";
								$sql .= "where domain_uuid = '".$domain_uuid."' ";
								$sql .= "and dialplan_uuid = '".$dialplan_uuid."' ";
								$db->query($sql);
								unset($sql);

								$sql = "delete from v_dialplan_details ";
								$sql .= "where domain_uuid = '$domain_uuid' ";
								$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
								$db->query($sql);
								unset($sql);
						}

						//if action is add or update
							if ($action == 'add' || $action == 'update') {
								require_once "includes/classes/switch_dialplan.php";
								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'destination_number';
								$dialplan->dialplan_detail_data = '^'.$row['hunt_group_extension'].'$';
								//$dialplan->dialplan_detail_break = '';
								//$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '1';
								$dialplan->dialplan_detail_order = '010';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'lua';
								$dialplan->dialplan_detail_data = 'v_huntgroup_'.$_SESSION['domains'][$domain_uuid]['domain_name'].'_'.$row['hunt_group_extension'].'.lua';
								//$dialplan->dialplan_detail_break = '';
								//$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '1';
								$dialplan->dialplan_detail_order = '020';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'destination_number';
								$dialplan->dialplan_detail_data = '^\*'.$row['hunt_group_extension'].'$';
								$dialplan->dialplan_detail_break = 'on-true';
								//$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '020';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'set';
								$dialplan->dialplan_detail_data = 'fifo_music=$${hold_music}';
								//$dialplan->dialplan_detail_break = '';
								//$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '020';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$hunt_group_timeout_type = $row['hunt_group_timeout_type'];
								$hunt_group_timeout_destination = $row['hunt_group_timeout_destination'];
								if ($hunt_group_timeout_type == "voicemail") { $hunt_group_timeout_destination = '*99'.$hunt_group_timeout_destination; }
								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'set';
								$dialplan->dialplan_detail_data = 'fifo_orbit_exten='.$hunt_group_timeout_destination.':'.$row['hunt_group_timeout'];
								//$dialplan->dialplan_detail_break = '';
								//$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '030';
								$dialplan->dialplan_detail_add();
								unset($dialplan);

								$dialplan = new dialplan;
								$dialplan->domain_uuid = $domain_uuid;
								$dialplan->dialplan_uuid = $dialplan_uuid;
								$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
								$dialplan->dialplan_detail_type = 'fifo';
								$dialplan->dialplan_detail_data = $row['hunt_group_extension'].'@${domain_name} in';
								//$dialplan->dialplan_detail_break = '';
								//$dialplan->dialplan_detail_inline = '';
								$dialplan->dialplan_detail_group = '2';
								$dialplan->dialplan_detail_order = '040';
								$dialplan->dialplan_detail_add();
								unset($dialplan);
							}

					} //end if strlen hunt_group_uuid; add the Hunt Group to the dialplan

				//get the list of destinations then build the Hunt Group Lua
					$tmp = "\n";
					$tmp .= "session:preAnswer();\n";
					$tmp .= "extension = '".$row['hunt_group_extension']."';\n";
					$tmp .= "result = '';\n";
					$tmp .= "timeoutpin = 7500;\n";
					$tmp .= "sip_profile = 'internal';\n";
					$tmp .= "\n";

					$tmp .=	"function extension_registered(domain_name, sip_profile, extension)\n";
					$tmp .=	"	api = freeswitch.API();\n";
					$tmp .=	"	result = api:execute(\"sofia_contact\", sip_profile..\"/\"..extension..\"@\"..domain_name);\n";
					$tmp .=	"	if (result == \"error/user_not_registered\") then\n";
					$tmp .=	"		return false;\n";
					$tmp .=	"	else\n";
					$tmp .=	"		return true;\n";
					$tmp .=	"	end\n";
					$tmp .=	"end\n";
					$tmp .=	"\n";

					$tmp .= "\n";
					$tmp .= "sounds_dir = session:getVariable(\"sounds_dir\");\n";
					$tmp .= "uuid = session:getVariable(\"uuid\");\n";
					$tmp .= "dialed_extension = session:getVariable(\"dialed_extension\");\n";
					$tmp .= "domain_name = session:getVariable(\"domain_name\");\n";
					$tmp .= "caller_id_name = session:getVariable(\"caller_id_name\");\n";
					$tmp .= "caller_id_number = session:getVariable(\"caller_id_number\");\n";
					$tmp .= "outbound_caller_id_name = session:getVariable(\"outbound_caller_id_name\");\n";
					$tmp .= "outbound_caller_id_number = session:getVariable(\"outbound_caller_id_number\");\n";
					$tmp .= "\n";

					$tmp .= "--set the sounds path for the language, dialect and voice\n";
					$tmp .= "	default_language = session:getVariable(\"default_language\");\n";
					$tmp .= "	default_dialect = session:getVariable(\"default_dialect\");\n";
					$tmp .= "	default_voice = session:getVariable(\"default_voice\");\n";
					$tmp .= "	if (not default_language) then default_language = 'en'; end\n";
					$tmp .= "	if (not default_dialect) then default_dialect = 'us'; end\n";
					$tmp .= "	if (not default_voice) then default_voice = 'callie'; end\n";
					$tmp .= "\n";

					//pin number requested from caller if provided
						if (strlen($row['hunt_group_pin']) > 0) {
							$tmp .= "pin = '".$row['hunt_group_pin']."';\n";
							$tmp .= "digits = session:playAndGetDigits(".strlen($row['hunt_group_pin']).", ".strlen($row['hunt_group_pin']).", 3, 3000, \"#\", sounds_dir..\"/\"..default_language..\"/\"..default_dialect..\"/\"..default_voice..\"/custom/please_enter_the_pin_number.wav\", \"\", \"\\\\d+\");\n";
							$tmp .= "\n";
							$tmp .= "\n";
							$tmp .= "if (digits == pin) then\n";
							$tmp .= "	--continue\n";
							$tmp .= "\n";
						}

					//caller announce requested from caller if provided
						if ($row['hunt_group_caller_announce'] == "true" || $row['hunt_group_call_prompt'] == "true") {
							if ($row['hunt_group_caller_announce'] == "true") {
								$tmp .=	"function originate(domain_name, session, sipuri, extension, caller_id_name, caller_id_number, caller_announce) \n";
							}
							else {
								$tmp .=	"function originate(domain_name, session, sipuri, extension, caller_id_name, caller_id_number) \n";
							}
							$tmp .=	"	--caller_id_name = caller_id_name.replace(\" \", \"..\");\n";
							$tmp .=	"	caller_id_name = string.gsub(caller_id_name, \" \", \"..\");\n";
							//$tmp .=	"	--session:execute(\"luarun\", \"huntgroup_originate.lua \"..domain_name..\" \"..uuid..\" \"..sipuri..\" \"..extension..\" \"..caller_id_name..\" \"..caller_id_number..\" \"..caller_announce);\n";
							$tmp .=	"	api = freeswitch.API();\n";
							if ($row['hunt_group_caller_announce'] == "true") {
								$tmp .=	"	result = api:execute(\"luarun\", \"huntgroup_originate.lua \"..domain_name..\" \"..uuid..\" \"..sipuri..\" \"..extension..\" \"..caller_id_name..\" \"..caller_id_number..\" \"..caller_announce);\n";
							}
							else {
								$tmp .=	"	result = api:execute(\"luarun\", \"huntgroup_originate.lua \"..domain_name..\" \"..uuid..\" \"..sipuri..\" \"..extension..\" \"..caller_id_name..\" \"..caller_id_number..\"\");\n";
							}
							$tmp .=	"end";
							$tmp .=	"\n";

							if ($row['hunt_group_caller_announce'] == "true") {
								$tmp .=	"caller_announce = \"".$tmp_dir."/\"..extension..\"_\"..uuid..\".wav\";\n";
								$tmp .=	"session:streamFile(sounds_dir..\"/\"..default_language..\"/\"..default_dialect..\"/\"..default_voice..\"/custom/please_say_your_name_and_reason_for_calling.wav\");\n";
								$tmp .=	"session:execute(\"gentones\", \"%(1000, 0, 640)\");\n";
								$tmp .=	"session:execute(\"set\", \"playback_terminators=#\");\n";
								$tmp .=	"session:execute(\"record\", caller_announce..\" 180 200\");\n";
							}
							$tmp .=	"\n";
							$tmp .=	"session:setAutoHangup(false);\n";
							$tmp .=	"session:execute(\"transfer\", \"*\"..extension..\" XML ".$_SESSION["context"]."\");\n";
							$tmp .=	"\n";
						}

					//set caller id
						if (strlen($row['hunt_group_cid_name_prefix'])> 0) {
							$tmp .= "session:execute(\"set\", \"effective_caller_id_name=".$row['hunt_group_cid_name_prefix']."#\"..caller_id_name);\n";
							$tmp .= "session:execute(\"set\", \"outbound_caller_id_name=".$row['hunt_group_cid_name_prefix']."#\"..caller_id_name);\n";
						}

					//set ring back
						if (isset($row['hunt_group_ringback'])){
							if ($row['hunt_group_ringback'] == "music"){
								$tmp .= "session:execute(\"set\", \"ringback=\${hold_music}\");          --set to music\n";
								$tmp .= "session:execute(\"set\", \"transfer_ringback=\${hold_music}\"); --set to music\n";
							}
							else {
								$tmp .= "session:execute(\"set\", \"ringback=".$row['hunt_group_ringback']."\"); --set to ringtone\n";
								$tmp .= "session:execute(\"set\", \"transfer_ringback=".$row['hunt_group_ringback']."\"); --set to ringtone\n";
							}
							if ($row['hunt_group_ringback'] == "ring"){
								$tmp .= "session:execute(\"set\", \"ringback=\${us-ring}\"); --set to ringtone\n";
								$tmp .= "session:execute(\"set\", \"transfer_ringback=\${us-ring}\"); --set to ringtone\n";
							}
						}
						else {
							$tmp .= "session:execute(\"set\", \"ringback=\${hold_music}\");          --set to ringtone\n";
							$tmp .= "session:execute(\"set\", \"transfer_ringback=\${hold_music}\"); --set to ringtone\n";
						}

					if ($row['hunt_group_timeout'] > 0) {
						//$tmp .= "session:setVariable(\"call_timeout\", \"".$row['hunt_group_timeout']."\");\n";
						$tmp .= "session:setVariable(\"continue_on_fail\", \"true\");\n";
					}
					$tmp .= "session:setVariable(\"hangup_after_bridge\", \"true\");\n";
					$tmp .= "\n";
					$tmp .= "--freeswitch.consoleLog( \"info\", \"dialed extension:\"..dialed_extension..\"\\n\" );\n";
					$tmp .= "--freeswitch.consoleLog( \"info\", \"domain: \"..domain..\"\\n\" );\n";
					$tmp .= "--freeswitch.consoleLog( \"info\", \"us_ring: \"..us_ring..\"\\n\" );\n";
					$tmp .= "--freeswitch.consoleLog( \"info\", \"domain_name: \"..domain_name..\"\\n\" );\n";
					$tmp .= "\n";

					$tmp .= "--freeswitch.consoleLog( \"info\", \"action call now don't wait for dtmf\\n\" );\n";
					if ($row['hunt_group_caller_announce'] == "true" || $row['hunt_group_call_prompt'] == "true") {
						//do nothing
					}
					else {
						$tmp .= "if session:ready() then\n";
						//$tmp .= "	session.answer();\n";
					}
					$tmp .= "\n";

					$i = 0;
					$sql = "select * from v_hunt_group_destinations ";
					$sql .= "where hunt_group_uuid = '".$row['hunt_group_uuid']."' ";
					$sql .= "and domain_uuid = '$domain_uuid' ";
					//$sql .= "and destination_enabled = 'true' ";
					$sql .= "order by destination_order asc ";
					$prep_statement_2 = $db->prepare($sql);
					$prep_statement_2->execute();
					while($ent = $prep_statement_2->fetch(PDO::FETCH_ASSOC)) {
						//$ent['hunt_group_uuid']
						//$ent['destination_data']
						//$ent['destination_type']
						//$ent['destination_profile']
						//$ent['destination_order']
						//$ent['destination_enabled']
						//$ent['destination_description']

						$destination_timeout = $ent['destination_timeout'];
						$hunt_group_cid_name_prefix = $row['hunt_group_cid_name_prefix'];
						if (strlen($hunt_group_cid_name_prefix) > 0) {
							$hunt_group_cid_name_prefix .= "#";
						}
						if (strlen($destination_timeout) == 0) {
							if (strlen($row['hunt_group_timeout']) == 0) {
								$destination_timeout = '30';
							}
							else {
								$destination_timeout = $row['hunt_group_timeout'];
							}
						}

						//set the default profile
						if (strlen($ent['destination_data']) == 0) { $ent['destination_data'] = "internal"; }

						if ($ent['destination_type'] == "extension") {
							//$tmp .= "	sofia_contact_".$ent['destination_data']." = \"\${sofia_contact(".$ent['destination_profile']."/".$ent['destination_data']."@\"..domain_name..\")}\";\n";
							$tmp_sub_array["application"] = "bridge";
							$tmp_sub_array["type"] = "extension";
							$tmp_sub_array["extension"] = $ent['destination_data'];

							//$tmp_sub_array["data"] = "\"[leg_timeout=$destination_timeout]\"..sofia_contact_".$ent['destination_data'];
							$tmp_sub_array["data"] = "\"[leg_timeout=$destination_timeout,origination_caller_id_name='".$hunt_group_cid_name_prefix."\"..caller_id_name..\"',origination_caller_id_number=\"..caller_id_number..\"]user/".$ent['destination_data']."@\"..domain_name";
							$tmp_array[$i] = $tmp_sub_array;
							unset($tmp_sub_array);
						}
						if ($ent['destination_type'] == "voicemail") {
							$tmp_sub_array["application"] = "voicemail";
							$tmp_sub_array["type"] = "voicemail";
							$tmp .= "	session:answer();\n";
							$tmp .= "	session:execute(\"transfer\", \"*99".$ent['destination_data']." XML ".$_SESSION["context"]." \");\n";
							//$tmp_sub_array["application"] = "voicemail";
							//$tmp_sub_array["data"] = "default \${domain_name} ".$ent['destination_data'];
							//$tmp_array[$i] = $tmp_sub_array;
							unset($tmp_sub_array);
						}
						if ($ent['destination_type'] == "sip uri") {
							$tmp_sub_array["application"] = "bridge";
							$tmp_sub_array["type"] = "sip uri";
							//$destination_data = "{user=foo}loopback/".$ent['destination_data']."/default/XML";
							$bridge_array = outbound_route_to_bridge ($domain_uuid, $ent['destination_data']);
							$destination_data = $bridge_array[0];
							$tmp_sub_array["application"] = "bridge";
							$tmp_sub_array["data"] = "\"[leg_timeout=$destination_timeout,origination_caller_id_name='".$hunt_group_cid_name_prefix."\"..caller_id_name..\"',origination_caller_id_number=\"..caller_id_number..\"]".$destination_data."\"";
							$tmp_array[$i] = $tmp_sub_array;
							unset($tmp_sub_array);
							unset($destination_data);
						}
						$i++;
					} //end while
					unset ($sql, $prep_statement_2);
					unset($i, $ent);

					$i = 0;
					if(count($tmp_array) > 0) {
						foreach ($tmp_array as $ent) {
							$tmpdata = $ent["data"];
							if ($ent["application"] == "voicemail") { $tmpdata = "*99".$tmpdata; }
							if ($i < 1) {
								$tmp_buffer = $tmpdata;
							}
							else {
								$tmp_buffer .= "..\",\"..".$tmpdata;
							}
							$i++;
						}
					}
					unset($i);
					$tmp_application = $tmp_array[0]["application"];

					if ($row['hunt_group_type'] == "simultaneous" || $row['hunt_group_type'] == "follow_me_simultaneous" || $row['hunt_group_type'] ==  "call_forward") {
						$tmp_switch = "simultaneous";
					}
					if ($row['hunt_group_type'] == "sequence" || $row['hunt_group_type'] == "follow_me_sequence" || $row['hunt_group_type'] ==  "sequentially") {
						$tmp_switch = "sequence";
					}
					switch ($tmp_switch) {
					case "simultaneous":
						if ($row['hunt_group_caller_announce'] == "true" || $row['hunt_group_call_prompt'] == "true") {
							$i = 0;
							if (count($tmp_array) > 0) {
								foreach ($tmp_array as $tmp_row) {
									$tmpdata = $tmp_row["data"];
									if ($tmp_row["application"] == "voicemail") {
										$tmpdata = "*99".$tmpdata;
									}
									else {
										if ($tmp_row["type"] == "extension") {
											$tmp .= "if (extension_registered(domain_name, sip_profile, '".$tmp_row["extension"]."')) then\n";
											$tmp .= "	";
										}
										if ($row['hunt_group_caller_announce'] == "true") {
											$tmp .= "result = originate (domain_name, session, ".$tmpdata.", extension, caller_id_name, caller_id_number, caller_announce);\n";
										}
										else {
											$tmp .= "result = originate (domain_name, session, ".$tmpdata.", extension, caller_id_name, caller_id_number);\n";
										}
										if ($tmp_row["type"] == "extension") {
											$tmp .= "end\n";
										}
									}
								}
							}
						}
						else {
							$tmp .= "\n";
							if (strlen($tmp_buffer) > 0) {
								$tmp .= "	session:execute(\"".$tmp_application."\", $tmp_buffer);\n";
							}
						}
						break;
					case "sequence":
						$tmp .= "\n";
						$i = 0;
						if (count($tmp_array) > 0) {
							if ($row['hunt_group_caller_announce'] == "true" || $row['hunt_group_call_prompt'] == "true") {
								$i = 0;
								if (count($tmp_array) > 0) {
									foreach ($tmp_array as $tmp_row) {
										$tmpdata = $tmp_row["data"];
										if ($tmp_row["application"] == "voicemail") {
											$tmpdata = "*99".$tmpdata;
										}
										else {
											if ($tmp_row["type"] == "extension") {
												$tmp .= "if (extension_registered(domain_name, sip_profile, '".$tmp_row["extension"]."')) then\n";
												$tmp .= "	";
											}
											if ($row['hunt_group_caller_announce'] == "true") {
												$tmp .= "result = originate (domain_name, session, ".$tmpdata.", extension, caller_id_name, caller_id_number, caller_announce);\n";
											}
											else {
												$tmp .= "result = originate (domain_name, session, ".$tmpdata.", extension, caller_id_name, caller_id_number);\n";
											}
											if ($tmp_row["type"] == "extension") {
												$tmp .= "end\n";
											}
										}
									}
								}
							}
							else {
								foreach ($tmp_array as $tmp_row) {
									if (strlen($tmp_row["data"]) > 0) {
										$tmp .= "	session:execute(\"".$tmp_application."\", ".$tmp_row["data"].");\n";
									}
								}
							}
							unset($tmp_row);
						}
						break;
					}
					unset($tmp_switch, $tmp_buffer, $tmp_array);

					//set the timeout destination
						$hunt_group_timeout_destination = $row['hunt_group_timeout_destination'];
						if ($row['hunt_group_timeout_type'] == "extension") { $hunt_group_timeout_type = "transfer"; }
						if ($row['hunt_group_timeout_type'] == "voicemail") { $hunt_group_timeout_type = "transfer"; $hunt_group_timeout_destination = "*99".$hunt_group_timeout_destination." XML ".$_SESSION["context"]; }
						if ($row['hunt_group_timeout_type'] == "sip uri") { $hunt_group_timeout_type = "bridge"; }
						$tmp .= "\n";
						if ($row['hunt_group_caller_announce'] == "true" || $row['hunt_group_call_prompt'] == "true") {
							//do nothing
						}
						else {
							$tmp .= "	--timeout\n";
							if ($row['hunt_group_type'] != 'dnd') {
								$tmp .= "	originate_disposition = session:getVariable(\"originate_disposition\");\n";
								$tmp .= "	if originate_disposition ~= \"SUCCESS\" then\n";
							}
							if ($row['hunt_group_timeout_type'] == "voicemail") {
								$tmp .= "			session:answer();\n";
							}
							$tmp .= "			session:execute(\"".$hunt_group_timeout_type."\", \"".$hunt_group_timeout_destination."\");\n";
							if ($row['hunt_group_type'] != 'dnd') {
								$tmp .= "	end\n";
							}
						}

						if ($row['hunt_group_caller_announce'] == "true" || $row['hunt_group_call_prompt'] == "true") {
							//do nothing
						}
						else {
							$tmp .= "end --end if session:ready\n";
						}
						$tmp .= "\n";
						//pin number requested from caller if provided
						if (strlen($row['hunt_group_pin']) > 0) {
							$tmp .= "else \n";
							$tmp .= "	session:streamFile(sounds_dir..\"/\"..default_language..\"/\"..default_dialect..\"/\"..default_voice..\"/custom/your_pin_number_is_incorect_goodbye.wav\");\n";
							$tmp .= "	session:hangup();\n";
							$tmp .= "end\n";
							$tmp .= "\n";
						}

					//unset variables
						$tmp .= "\n";
						$tmp .= "--clear variables\n";
						$tmp .= "dialed_extension = \"\";\n";
						$tmp .= "new_extension = \"\";\n";
						$tmp .= "domain_name = \"\";\n";
						$tmp .= "\n";

					//remove invalid characters from the file names
						$huntgroup_extension = $row['hunt_group_extension'];
						$huntgroup_extension = str_replace(" ", "_", $huntgroup_extension);
						$huntgroup_extension = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $huntgroup_extension);

					//write the hungroup lua script
						if (strlen($row['hunt_group_extension']) > 0) {
							if ($row['hunt_group_enabled'] != "false") {
								$hunt_group_filename = "v_huntgroup_".$_SESSION['domains'][$domain_uuid]['domain_name']."_".$huntgroup_extension.".lua";
								//echo "location".$_SESSION['switch']['scripts']['dir']."/".$hunt_group_filename;
								$fout = fopen($_SESSION['switch']['scripts']['dir']."/".$hunt_group_filename,"w");
								fwrite($fout, $tmp);
								unset($hunt_group_filename);
								fclose($fout);
							}
						}
		} //end while

	//save the dialplan xml files
		save_dialplan_xml();

} //end huntgroup function lua


function get_recording_filename($id) {
	global $domain_uuid, $db;
	$sql = "select * from v_recordings ";
	$sql .= "where recording_uuid = '$id' ";
	$sql .= "and domain_uuid = '$domain_uuid' ";
	$prep_statement = $db->prepare(check_sql($sql));
	$prep_statement->execute();
	$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as &$row) {
		//$filename = $row["filename"];
		//$recording_name = $row["recording_name"];
		//$recording_uuid = $row["recording_uuid"];
		return $row["filename"];
		break; //limit to 1 row
	}
	unset ($prep_statement);
}

function dialplan_add($domain_uuid, $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid) {
	global $db, $db_type;
	$sql = "insert into v_dialplans ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	if (strlen($app_uuid) > 0) {
		$sql .= "app_uuid, ";
	}
	$sql .= "dialplan_name, ";
	$sql .= "dialplan_order, ";
	$sql .= "dialplan_context, ";
	$sql .= "dialplan_enabled, ";
	$sql .= "dialplan_description ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'$dialplan_uuid', ";
	if (strlen($app_uuid) > 0) {
		$sql .= "'$app_uuid', ";
	}
	$sql .= "'$dialplan_name', ";
	$sql .= "'$dialplan_order', ";
	$sql .= "'$dialplan_context', ";
	$sql .= "'$dialplan_enabled', ";
	$sql .= "'$dialplan_description' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);
}

function dialplan_detail_add($domain_uuid, $dialplan_uuid, $dialplan_detail_tag, $dialplan_detail_order, $dialplan_detail_group, $dialplan_detail_type, $dialplan_detail_data) {
	global $db;
	$dialplan_detail_uuid = uuid();
	$sql = "insert into v_dialplan_details ";
	$sql .= "(";
	$sql .= "domain_uuid, ";
	$sql .= "dialplan_uuid, ";
	$sql .= "dialplan_detail_uuid, ";
	$sql .= "dialplan_detail_tag, ";
	$sql .= "dialplan_detail_group, ";
	$sql .= "dialplan_detail_order, ";
	$sql .= "dialplan_detail_type, ";
	$sql .= "dialplan_detail_data ";
	$sql .= ")";
	$sql .= "values ";
	$sql .= "(";
	$sql .= "'$domain_uuid', ";
	$sql .= "'".check_str($dialplan_uuid)."', ";
	$sql .= "'".check_str($dialplan_detail_uuid)."', ";
	$sql .= "'".check_str($dialplan_detail_tag)."', ";
	if (strlen($dialplan_detail_group) == 0) {
		$sql .= "null, ";
	}
	else {
		$sql .= "'".check_str($dialplan_detail_group)."', ";
	}
	$sql .= "'".check_str($dialplan_detail_order)."', ";
	$sql .= "'".check_str($dialplan_detail_type)."', ";
	$sql .= "'".check_str($dialplan_detail_data)."' ";
	$sql .= ")";
	$db->exec(check_sql($sql));
	unset($sql);
}

function save_dialplan_xml() {
	global $db, $domain_uuid;

	//get the context based from the domain_uuid
		if (count($_SESSION['domains']) == 1) {
			$user_context = "default";
		}
		else {
			$user_context = $_SESSION['domains'][$domain_uuid]['domain_name'];
		}

	//prepare for dialplan .xml files to be written. delete all dialplan files that are prefixed with dialplan_ and have a file extension of .xml
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*v_dialplan*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*_v_*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}
		$dialplan_list = glob($_SESSION['switch']['dialplan']['dir'] . "/*/*/*_v_*.xml");
		foreach($dialplan_list as $name => $value) {
			unlink($value);
		}

	//if dialplan dir exists then build and save the dialplan xml
		if (is_dir($_SESSION['switch']['dialplan']['dir'])) {
			$sql = "select * from v_dialplans ";
			$sql .= "where dialplan_enabled = 'true' ";
			$prep_statement = $db->prepare(check_sql($sql));
			if ($prep_statement) {
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				foreach ($result as &$row) {
					$tmp = "";
					$tmp .= "\n";

					$dialplan_continue = '';
					if ($row['dialplan_continue'] == "true") {
						$dialplan_continue = "continue=\"true\"";
					}

					$tmp = "<extension name=\"".$row['dialplan_name']."\" $dialplan_continue>\n";

					$sql = " select * from v_dialplan_details ";
					$sql .= " where dialplan_uuid = '".$row['dialplan_uuid']."' ";
					$sql .= " and domain_uuid = '".$row['domain_uuid']."' ";
					$sql .= " order by dialplan_detail_group asc, dialplan_detail_order asc ";
					$prep_statement_2 = $db->prepare($sql);
					if ($prep_statement_2) {
						$prep_statement_2->execute();
						$result2 = $prep_statement_2->fetchAll(PDO::FETCH_NAMED);
						$result_count2 = count($result2);
						unset ($prep_statement_2, $sql);

						//create a new array that is sorted into groups and put the tags in order conditions, actions, anti-actions
							$details = '';
							$previous_tag = '';
							$details[$group]['condition_count'] = '';
							//conditions
								$x = 0;
								$y = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "condition") {
										//get the group
											$group = $row2['dialplan_detail_group'];
										//get the generic type
											switch ($row2['dialplan_detail_type']) {
											case "hour":
												$type = 'time';
												break;
											case "minute":
												$type = 'time';
												break;
											case "minute-of-day":
												$type = 'time';
												break;
											case "mday":
												$type = 'time';
												break;
											case "mweek":
												$type = 'time';
												break;
											case "mon":
												$type = 'time';
												break;
											case "yday":
												$type = 'time';
												break;
											case "year":
												$type = 'time';
												break;
											case "wday":
												$type = 'time';
												break;
											case "week":
												$type = 'time';
												break;
											default:
												$type = 'default';
											}

										//add the conditions to the details array
											$details[$group]['condition-'.$x]['dialplan_detail_tag'] = $row2['dialplan_detail_tag'];
											$details[$group]['condition-'.$x]['dialplan_detail_type'] = $row2['dialplan_detail_type'];
											$details[$group]['condition-'.$x]['dialplan_uuid'] = $row2['dialplan_uuid'];
											$details[$group]['condition-'.$x]['dialplan_detail_order'] = $row2['dialplan_detail_order'];
											$details[$group]['condition-'.$x]['field'][$y]['type'] = $row2['dialplan_detail_type'];
											$details[$group]['condition-'.$x]['field'][$y]['data'] = $row2['dialplan_detail_data'];
											$details[$group]['condition-'.$x]['dialplan_detail_break'] = $row2['dialplan_detail_break'];
											$details[$group]['condition-'.$x]['dialplan_detail_group'] = $row2['dialplan_detail_group'];
											$details[$group]['condition-'.$x]['dialplan_detail_inline'] = $row2['dialplan_detail_inline'];
											if ($type == "time") {
												$y++;
											}
									}
									if ($type == "default") {
										$x++;
										$y = 0;
									}
								}

							//actions
								$x = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "action") {
										$group = $row2['dialplan_detail_group'];
										foreach ($row2 as $key => $val) {
											$details[$group]['action-'.$x][$key] = $val;
										}
									}
									$x++;
								}
							//anti-actions
								$x = 0;
								foreach($result2 as $row2) {
									if ($row2['dialplan_detail_tag'] == "anti-action") {
										$group = $row2['dialplan_detail_group'];
										foreach ($row2 as $key => $val) {
											$details[$group]['anti-action-'.$x][$key] = $val;
										}
									}
									$x++;
								}
							unset($result2);
					}

					$i=1;
					if ($result_count2 > 0) {
						foreach($details as $group) {
							$current_count = 0;
							$x = 0;
							foreach($group as $ent) {
								$close_condition_tag = true;
								if (empty($ent)) {
									$close_condition_tag = false;
								}
								$current_tag = $ent['dialplan_detail_tag'];
								$c = 0;
								if ($ent['dialplan_detail_tag'] == "condition") {
									//get the generic type
										switch ($ent['dialplan_detail_type']) {
										case "hour":
											$type = 'time';
											break;
										case "minute":
											$type = 'time';
											break;
										case "minute-of-day":
											$type = 'time';
											break;
										case "mday":
											$type = 'time';
											break;
										case "mweek":
											$type = 'time';
											break;
										case "mon":
											$type = 'time';
											break;
										case "yday":
											$type = 'time';
											break;
										case "year":
											$type = 'time';
											break;
										case "wday":
											$type = 'time';
											break;
										case "week":
											$type = 'time';
											break;
										default:
											$type = 'default';
										}

									//set the attribute and expression
										$condition_attribute = '';
										foreach($ent['field'] as $field) {
											if ($type == "time") {
												if (strlen($field['type']) > 0) {
													$condition_attribute .= $field['type'].'="'.$field['data'].'" ';
												}
												$condition_expression = '';
											}
											if ($type == "default") {
												$condition_attribute = '';
												if (strlen($field['type']) > 0) {
													$condition_attribute = 'field="'.$field['type'].'" ';
												}
												$condition_expression = '';
												if (strlen($field['data']) > 0) {
													$condition_expression = 'expression="'.$field['data'].'" ';
												}
											}
										}

									//get the condition break attribute
										$condition_break = '';
										if (strlen($ent['dialplan_detail_break']) > 0) {
											$condition_break = "break=\"".$ent['dialplan_detail_break']."\" ";
										}

									//get the count
										$count = 0;
										foreach($details as $group2) {
											foreach($group2 as $ent2) {
												if ($ent2['dialplan_detail_group'] == $ent['dialplan_detail_group'] && $ent2['dialplan_detail_tag'] == "condition") {
													$count++;
												}
											}
										}

									//use the correct type of dialplan_detail_tag open or self closed
										if ($count == 1) { //single condition
											//start dialplan_detail_tag
											$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break.">\n";
										}
										else { //more than one condition
											$current_count++;
											if ($current_count < $count) {
												//all tags should be self-closing except the last one
												$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break."/>\n";
											}
											else {
												//for the last dialplan_detail_tag use the start dialplan_detail_tag
												$tmp .= "   <condition ".$condition_attribute."".$condition_expression."".$condition_break.">\n";
											}
										}
								}
								//actions
									if ($ent['dialplan_detail_tag'] == "action") {
										//get the action inline attribute
										$action_inline = '';
										if (strlen($ent['dialplan_detail_inline']) > 0) {
											$action_inline = "inline=\"".$ent['dialplan_detail_inline']."\"";
										}
										if (strlen($ent['dialplan_detail_data']) > 0) {
											$tmp .= "       <action application=\"".$ent['dialplan_detail_type']."\" data=\"".$ent['dialplan_detail_data']."\" $action_inline/>\n";
										}
										else {
											$tmp .= "       <action application=\"".$ent['dialplan_detail_type']."\" $action_inline/>\n";
										}
									}
								//anti-actions
									if ($ent['dialplan_detail_tag'] == "anti-action") {
										if (strlen($ent['dialplan_detail_data']) > 0) {
											$tmp .= "       <anti-action application=\"".$ent['dialplan_detail_type']."\" data=\"".$ent['dialplan_detail_data']."\"/>\n";
										}
										else {
											$tmp .= "       <anti-action application=\"".$ent['dialplan_detail_type']."\"/>\n";
										}
									}
								//set the previous dialplan_detail_tag
									$previous_tag = $ent['dialplan_detail_tag'];
								$i++;
							} //end foreach
							if ($close_condition_tag == true) {
								$tmp .= "   </condition>\n";
							}
							$x++;
						}
						if ($condition_count > 0) {
							$condition_count = $result_count2;
						}
						unset($sql, $result_count2, $result2, $row_count2);
					} //end if results
					$tmp .= "</extension>\n";

					$dialplan_order = $row['dialplan_order'];
					if (strlen($dialplan_order) == 0) { $dialplan_order = "000".$dialplan_order; }
					if (strlen($dialplan_order) == 1) { $dialplan_order = "00".$dialplan_order; }
					if (strlen($dialplan_order) == 2) { $dialplan_order = "0".$dialplan_order; }
					if (strlen($dialplan_order) == 4) { $dialplan_order = "999"; }
					if (strlen($dialplan_order) == 5) { $dialplan_order = "999"; }

					//remove invalid characters from the file names
					$dialplan_name = $row['dialplan_name'];
					$dialplan_name = str_replace(" ", "_", $dialplan_name);
					$dialplan_name = preg_replace("/[\*\:\\/\<\>\|\'\"\?]/", "", $dialplan_name);

					$dialplan_filename = $dialplan_order."_v_".$dialplan_name.".xml";
					if (strlen($row['dialplan_context']) > 0) {
						if (!is_dir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'])) {
							mkdir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'],0755,true);
						}
						if ($row['dialplan_context'] == "public") {
							if (count($_SESSION['domains']) > 1) {
								if (!is_dir($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name'])) {
									mkdir($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name'],0755,true);
								}
								file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$_SESSION['domains'][$row['domain_uuid']]['domain_name']."/".$dialplan_filename, $tmp);
							}
							else {
								file_put_contents($_SESSION['switch']['dialplan']['dir']."/public/".$dialplan_filename, $tmp);
							}
						}
						else {
							if (!is_dir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'])) {
								mkdir($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context'],0755,true);
							}
							file_put_contents($_SESSION['switch']['dialplan']['dir']."/".$row['dialplan_context']."/".$dialplan_filename, $tmp);
						}
					}
					unset($dialplan_filename);
					unset($tmp);
				} //end while

				//apply settings
					$_SESSION["reload_xml"] = true;
			}
		} //end if (is_dir($_SESSION['switch']['dialplan']['dir']))
}


if (!function_exists('phone_letter_to_number')) {
	function phone_letter_to_number($tmp) {
		$tmp = strtolower($tmp);
		if ($tmp == "a" | $tmp == "b" | $tmp == "c") { return 2; }
		if ($tmp == "d" | $tmp == "e" | $tmp == "f") { return 3; }
		if ($tmp == "g" | $tmp == "h" | $tmp == "i") { return 4; }
		if ($tmp == "j" | $tmp == "k" | $tmp == "l") { return 5; }
		if ($tmp == "m" | $tmp == "n" | $tmp == "o") { return 6; }
		if ($tmp == "p" | $tmp == "q" | $tmp == "r" | $tmp == "s") { return 7; }
		if ($tmp == "t" | $tmp == "u" | $tmp == "v") { return 8; }
		if ($tmp == "w" | $tmp == "x" | $tmp == "y" | $tmp == "z") { return 9; }
	}
}


if (!function_exists('save_call_center_xml')) {
	function save_call_center_xml() {
		global $db, $domain_uuid;

		//include the classes
		include "includes/classes/switch_dialplan.php";

		$sql = "select * from v_call_center_queues ";
		$prep_statement = $db->prepare(check_sql($sql));
		$prep_statement->execute();
		$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
		$result_count = count($result);
		unset ($prep_statement, $sql);
		if ($result_count > 0) { //found results
			foreach($result as $row) {
				$call_center_queue_uuid = $row["call_center_queue_uuid"];
				$domain_uuid = $row["domain_uuid"];
				$dialplan_uuid = $row["dialplan_uuid"];
				$queue_name = check_str($row["queue_name"]);
				$queue_extension = $row["queue_extension"];
				$queue_strategy = $row["queue_strategy"];
				$queue_moh_sound = $row["queue_moh_sound"];
				$queue_record_template = $row["queue_record_template"];
				$queue_time_base_score = $row["queue_time_base_score"];
				$queue_max_wait_time = $row["queue_max_wait_time"];
				$queue_max_wait_time_with_no_agent = $row["queue_max_wait_time_with_no_agent"];
				$queue_tier_rules_apply = $row["queue_tier_rules_apply"];
				$queue_tier_rule_wait_second = $row["queue_tier_rule_wait_second"];
				$queue_tier_rule_wait_multiply_level = $row["queue_tier_rule_wait_multiply_level"];
				$queue_tier_rule_no_agent_no_wait = $row["queue_tier_rule_no_agent_no_wait"];
				$queue_timeout_action = $row["queue_timeout_action"];
				$queue_discard_abandoned_after = $row["queue_discard_abandoned_after"];
				$queue_abandoned_resume_allowed = $row["queue_abandoned_resume_allowed"];
				$queue_cid_prefix = $row["queue_cid_prefix"];
				$queue_description = check_str($row["queue_description"]);

				//replace space with an underscore
					$queue_name = str_replace(" ", "_", $queue_name);

				//add each Queue to the dialplan
					if (strlen($row['call_center_queue_uuid']) > 0) {
						$action = 'add'; //set default action to add
						$i = 0;

						//determine the action add or update
						if (strlen($dialplan_uuid) > 0) {
							$sql = "select * from v_dialplans ";
							$sql .= "where dialplan_uuid = '".$dialplan_uuid."' ";
							$prep_statement_2 = $db->prepare($sql);
							$prep_statement_2->execute();
							while($row2 = $prep_statement_2->fetch(PDO::FETCH_ASSOC)) {
								$action = 'update';
								break; //limit to 1 row
							}
							unset ($sql, $prep_statement_2);
						}

						if ($action == 'add') {
							//create queue entry in the dialplan
								$dialplan_name = $queue_name;
								$dialplan_order ='210';
								$dialplan_context = $_SESSION['context'];
								$dialplan_enabled = 'true';
								$dialplan_description = $queue_description;
								$app_uuid = '95788e50-9500-079e-2807-fd530b0ea370';
								$dialplan_uuid = uuid();
								dialplan_add($domain_uuid, $dialplan_uuid, $dialplan_name, $dialplan_order, $dialplan_context, $dialplan_enabled, $dialplan_description, $app_uuid);

								//add the dialplan_uuid to the call center table
									$sql = "update v_call_center_queues set ";
									$sql .= "dialplan_uuid = '$dialplan_uuid' ";
									$sql .= "where domain_uuid = '$domain_uuid' ";
									$sql .= "and call_center_queue_uuid = '".$row['call_center_queue_uuid']."' ";
									$db->exec(check_sql($sql));
									unset($sql);

								//group 1
									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = '${caller_id_name}';
									$dialplan->dialplan_detail_data = '^([^#]+#)(.*)$';
									$dialplan->dialplan_detail_break = 'never';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '1';
									$dialplan->dialplan_detail_order = '000';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'set';
									$dialplan->dialplan_detail_data = 'caller_id_name=$2';
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '1';
									$dialplan->dialplan_detail_order = '001';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

								//group 2
									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'condition'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'destination_number';
									$dialplan->dialplan_detail_data = '^'.$row['queue_extension'].'$';
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '000';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'answer';
									$dialplan->dialplan_detail_data = '';
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '001';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'set';
									$dialplan->dialplan_detail_data = 'hangup_after_bridge=true';
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '002';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'set';
									$dialplan->dialplan_detail_data = "caller_id_name=".$queue_cid_prefix."#\${caller_id_name}";
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '003';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'system';
									$dialplan->dialplan_detail_data = 'mkdir -p $${base_dir}/recordings/archive/${strftime(%Y)}/${strftime(%b)}/${strftime(%d)}/';
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '004';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'callcenter';
									$dialplan->dialplan_detail_data = $queue_name."@".$_SESSION['domains'][$domain_uuid]['domain_name'];
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '005';
									$dialplan->dialplan_detail_add();
									unset($dialplan);

									if (strlen($queue_timeout_action) > 0) {
										$action_array = explode(":",$queue_timeout_action);
										$dialplan = new dialplan;
										$dialplan->domain_uuid = $domain_uuid;
										$dialplan->dialplan_uuid = $dialplan_uuid;
										$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
										$dialplan->dialplan_detail_type = $action_array[0];
										$dialplan->dialplan_detail_data = substr($queue_timeout_action, strlen($action_array[0])+1, strlen($queue_timeout_action));
										$dialplan->dialplan_detail_break = '';
										$dialplan->dialplan_detail_inline = '';
										$dialplan->dialplan_detail_group = '2';
										$dialplan->dialplan_detail_order = '006';
										$dialplan->dialplan_detail_add();
										unset($dialplan);
									}

									$dialplan = new dialplan;
									$dialplan->domain_uuid = $domain_uuid;
									$dialplan->dialplan_uuid = $dialplan_uuid;
									$dialplan->dialplan_detail_tag = 'action'; //condition, action, antiaction
									$dialplan->dialplan_detail_type = 'hangup';
									$dialplan->dialplan_detail_data = '';
									$dialplan->dialplan_detail_break = '';
									$dialplan->dialplan_detail_inline = '';
									$dialplan->dialplan_detail_group = '2';
									$dialplan->dialplan_detail_order = '007';
									$dialplan->dialplan_detail_add();
									unset($dialplan);
						}
						if ($action == 'update') {
							//update the queue entry in the dialplan

								$dialplan_name = $queue_name;
								$dialplan_order = '9';
								//$context = $row['queue_context'];
								$context = 'default';
								$enabled = 'true';
								$descr = $queue_description;
								$call_center_queue_uuid = $row['call_center_queue_uuid'];

								$sql = "update v_dialplans set ";
								$sql .= "dialplan_name = '$dialplan_name', ";
								$sql .= "dialplan_order = '$dialplan_order', ";
								$sql .= "context = '$context', ";
								$sql .= "enabled = '$enabled', ";
								$sql .= "descr = '$descr' ";
								$sql .= "where domain_uuid = '$domain_uuid' ";
								$sql .= "and dialplan_uuid = 'dialplan_uuid' ";
								//echo "sql: ".$sql."<br />";
								$db->query($sql);
								unset($sql);

								//update the condition
								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = '^".$row['queue_extension']."$' ";
								$sql .= "where domain_uuid = '$domain_uuid' ";
								$sql .= "and dialplan_detail_tag = 'condition' ";
								$sql .= "and dialplan_detail_type = 'destination_number' ";
								$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
								//echo $sql."<br />";
								$db->query($sql);
								unset($sql);

								//update the action
								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = 'caller_id_name=".$queue_cid_prefix."\${caller_id_name}' ";
								$sql .= "where domain_uuid = '$domain_uuid' ";
								$sql .= "and dialplan_detail_tag = 'action' ";
								$sql .= "and dialplan_detail_type = 'set' ";
								$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
								$sql .= "and dialplan_detail_data like '%{caller_id_name}%' ";
								//echo $sql."<br />";
								$db->query($sql);

								//update the action
								$sql = "update v_dialplan_details set ";
								$sql .= "dialplan_detail_data = '".$queue_name."@".$_SESSION['domains'][$domain_uuid]['domain_name']."' ";
								$sql .= "where domain_uuid = '$domain_uuid' ";
								$sql .= "and dialplan_detail_tag = 'action' ";
								$sql .= "and dialplan_detail_type = 'callcenter' ";
								$sql .= "and dialplan_uuid = '$dialplan_uuid' ";
								//echo $sql."<br />";
								$db->query($sql);

								unset($dialplan_name);
								unset($order);
								unset($context);
								unset($enabled);
								unset($descr);
								unset($dialplan_uuid);
						}
						unset($action);
						unset($dialplanincludeid);
					} //end if strlen call_center_queue_uuid; add the call center queue to the dialplan
			}

			//prepare Queue XML string
				$v_queues = '';
				$sql = "select * from v_call_center_queues ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				$x=0;
				foreach ($result as &$row) {
					$queue_name = $row["queue_name"];
					$queue_extension = $row["queue_extension"];
					$queue_strategy = $row["queue_strategy"];
					$queue_moh_sound = $row["queue_moh_sound"];
					$queue_record_template = $row["queue_record_template"];
					$queue_time_base_score = $row["queue_time_base_score"];
					$queue_max_wait_time = $row["queue_max_wait_time"];
					$queue_max_wait_time_with_no_agent = $row["queue_max_wait_time_with_no_agent"];
					$queue_tier_rules_apply = $row["queue_tier_rules_apply"];
					$queue_tier_rule_wait_second = $row["queue_tier_rule_wait_second"];
					$queue_tier_rule_wait_multiply_level = $row["queue_tier_rule_wait_multiply_level"];
					$queue_tier_rule_no_agent_no_wait = $row["queue_tier_rule_no_agent_no_wait"];
					$queue_discard_abandoned_after = $row["queue_discard_abandoned_after"];
					$queue_abandoned_resume_allowed = $row["queue_abandoned_resume_allowed"];
					$queue_description = $row["queue_description"];
					if ($x > 0) {
						$v_queues .= "\n";
						$v_queues .= "		";
					}
					$v_queues .= "<queue name=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\">\n";
					$v_queues .= "			<param name=\"strategy\" value=\"$queue_strategy\"/>\n";
					$v_queues .= "			<param name=\"moh-sound\" value=\"$queue_moh_sound\"/>\n";
					if (strlen($queue_record_template) > 0) {
						$v_queues .= "			<param name=\"record-template\" value=\"$queue_record_template\"/>\n";
					}
					$v_queues .= "			<param name=\"time-base-score\" value=\"$queue_time_base_score\"/>\n";
					$v_queues .= "			<param name=\"max-wait-time\" value=\"$queue_max_wait_time\"/>\n";
					$v_queues .= "			<param name=\"max-wait-time-with-no-agent\" value=\"$queue_max_wait_time_with_no_agent\"/>\n";
					$v_queues .= "			<param name=\"max-wait-time-with-no-agent-time-reached\" value=\"$queue_max_wait_time_with_no_agent_time_reached\"/>\n";
					$v_queues .= "			<param name=\"tier-rules-apply\" value=\"$queue_tier_rules_apply\"/>\n";
					$v_queues .= "			<param name=\"tier-rule-wait-second\" value=\"$queue_tier_rule_wait_second\"/>\n";
					$v_queues .= "			<param name=\"tier-rule-wait-multiply-level\" value=\"$queue_tier_rule_wait_multiply_level\"/>\n";
					$v_queues .= "			<param name=\"tier-rule-no-agent-no-wait\" value=\"$queue_tier_rule_no_agent_no_wait\"/>\n";
					$v_queues .= "			<param name=\"discard-abandoned-after\" value=\"$queue_discard_abandoned_after\"/>\n";
					$v_queues .= "			<param name=\"abandoned-resume-allowed\" value=\"$queue_abandoned_resume_allowed\"/>\n";
					$v_queues .= "		</queue>";
					$x++;
				}
				unset ($prep_statement);

			//prepare Agent XML string
				$v_agents = '';
				$sql = "select * from v_call_center_agents ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				$x=0;
				foreach ($result as &$row) {
					//get the values from the db and set as php variables
						$agent_name = $row["agent_name"];
						$agent_type = $row["agent_type"];
						$agent_call_timeout = $row["agent_call_timeout"];
						$agent_contact = $row["agent_contact"];
						$agent_status = $row["agent_status"];
						$agent_no_answer_delay_time = $row["agent_no_answer_delay_time"];
						$agent_max_no_answer = $row["agent_max_no_answer"];
						$agent_wrap_up_time = $row["agent_wrap_up_time"];
						$agent_reject_delay_time = $row["agent_reject_delay_time"];
						$agent_busy_delay_time = $row["agent_busy_delay_time"];
						if ($x > 0) {
							$v_agents .= "\n";
							$v_agents .= "		";

						}

					//get and then set the complete agent_contact with the call_timeout and when necessary confirm
						$tmp_confirm = "group_confirm_file=custom/press_1_to_accept_this_call.wav,group_confirm_key=1";
						if(strstr($agent_contact, '}') === FALSE) {
							//not found
							if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
								//add the call_timeout
								$tmp_agent_contact = "{call_timeout=".$agent_call_timeout."}".$agent_contact;
							}
							else {
								//add the call_timeout and confirm
								$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
								$tmp_agent_contact = "{".$tmp_confirm.",call_timeout=".$agent_call_timeout."}".$agent_contact;
							}
						}
						else {
							//found
							if(stristr($agent_contact, 'sofia/gateway') === FALSE) {
								//not found
								if(stristr($agent_contact, 'call_timeout') === FALSE) {
									//add the call_timeout
									$tmp_pos = strrpos($agent_contact, "}");
									$tmp_first = substr($agent_contact, 0, $tmp_pos);
									$tmp_last = substr($agent_contact, $tmp_pos);
									$tmp_agent_contact = $tmp_first.',call_timeout='.$agent_call_timeout.$tmp_last;
								}
								else {
									//the string has the call timeout
									$tmp_agent_contact = $agent_contact;
								}
							}
							else {
								//found
								$tmp_pos = strrpos($agent_contact, "}");
								$tmp_first = substr($agent_contact, 0, $tmp_pos);
								$tmp_last = substr($agent_contact, $tmp_pos);
								if(stristr($agent_contact, 'call_timeout') === FALSE) {
									//add the call_timeout and confirm
									$tmp_agent_contact = $tmp_first.','.$tmp_confirm.',call_timeout='.$agent_call_timeout.$tmp_last;
								}
								else {
									//add confirm
									$tmp_agent_contact = $tmp_first.','.$tmp_confirm.$tmp_last;
								}
							}
						}

					$v_agents .= "<agent ";
					$v_agents .= "name=\"$agent_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" ";
					$v_agents .= "type=\"$agent_type\" ";
					$v_agents .= "contact=\"$tmp_agent_contact\" ";
					$v_agents .= "status=\"$agent_status\" ";
					$v_agents .= "no-answer-delay-time=\"$agent_no_answer_delay_time\" ";
					$v_agents .= "max-no-answer=\"$agent_max_no_answer\" ";
					$v_agents .= "wrap-up-time=\"$agent_wrap_up_time\" ";
					$v_agents .= "reject-delay-time=\"$agent_reject_delay_time\" ";
					$v_agents .= "busy-delay-time=\"$agent_busy_delay_time\" ";
					$v_agents .= "/>";
					$x++;
				}
				unset ($prep_statement);

			//prepare Tier XML string
				$v_tiers = '';
				$sql = "select * from v_call_center_tiers ";
				$prep_statement = $db->prepare(check_sql($sql));
				$prep_statement->execute();
				$result = $prep_statement->fetchAll(PDO::FETCH_ASSOC);
				$x=0;
				foreach ($result as &$row) {
					$agent_name = $row["agent_name"];
					$queue_name = $row["queue_name"];
					$tier_level = $row["tier_level"];
					$tier_position = $row["tier_position"];
					if ($x > 0) {
						$v_tiers .= "\n";
						$v_tiers .= "		";
					}
					$v_tiers .= "<tier agent=\"$agent_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" queue=\"$queue_name@".$_SESSION['domains'][$row["domain_uuid"]]['domain_name']."\" level=\"$tier_level\" position=\"$tier_position\"/>";
					$x++;
				}

			//get the contents of the template
				$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/conf/autoload_configs/callcenter.conf.xml");

			//add the Call Center Queues, Agents and Tiers to the XML config
				$file_contents = str_replace("{v_queues}", $v_queues, $file_contents);
				unset ($v_queues);

				$file_contents = str_replace("{v_agents}", $v_agents, $file_contents);
				unset ($v_agents);

				$file_contents = str_replace("{v_tiers}", $v_tiers, $file_contents);
				unset ($v_tiers);

			//write the XML config file
				$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/callcenter.conf.xml","w");
				fwrite($fout, $file_contents);
				fclose($fout);

			//save the dialplan xml files
				save_dialplan_xml();

			//apply settings
				$_SESSION["reload_xml"] = true;

		}
	}
}

if (!function_exists('switch_conf_xml')) {
	function switch_conf_xml() {
		//get the global variables
			global $db, $domain_uuid;

		//get the contents of the template
			$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/conf/autoload_configs/switch.conf.xml");

		//prepare the php variables
			if (stristr(PHP_OS, 'WIN')) {
				$bindir = getenv(PHPRC);
				$v_mailer_app ='""'. $bindir."/php". '" -f  '.$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH.'/secure/v_mailto.php -- "';
				$v_mailer_app = sprintf("'%s'", $v_mailer_app);
				$v_mailer_app_args = "";
			}
			else {
				if (file_exists(PHP_BINDIR.'/php')) { define("PHP_BIN", "php"); }
				$v_mailer_app = PHP_BINDIR."/".PHP_BIN." ".$_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/secure/v_mailto.php";
				$v_mailer_app = sprintf('"%s"', $v_mailer_app);
				$v_mailer_app_args = "-t";
			}

		//replace the values in the template
			$file_contents = str_replace("{v_mailer_app}", $v_mailer_app, $file_contents);
			unset ($v_mailer_app);

		//replace the values in the template
			$file_contents = str_replace("{v_mailer_app_args}", $v_mailer_app_args, $file_contents);
			unset ($v_mailer_app_args);

		//write the XML config file
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/switch.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('xml_cdr_conf_xml')) {
	function xml_cdr_conf_xml() {

		//get the global variables
			global $db, $domain_uuid;

		//get the contents of the template
			$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/includes/templates/conf/autoload_configs/xml_cdr.conf.xml");

		//replace the values in the template
			$file_contents = str_replace("{v_http_protocol}", "http", $file_contents);
			$file_contents = str_replace("{domain_name}", "127.0.0.1", $file_contents);
			$file_contents = str_replace("{v_project_path}", PROJECT_PATH, $file_contents);

			$v_user = generate_password();
			$file_contents = str_replace("{v_user}", $v_user, $file_contents);
			unset ($v_user);

			$v_pass = generate_password();
			$file_contents = str_replace("{v_pass}", $v_pass, $file_contents);
			unset ($v_pass);

		//write the XML config file
			$fout = fopen($_SESSION['switch']['conf']['dir']."/autoload_configs/xml_cdr.conf.xml","w");
			fwrite($fout, $file_contents);
			fclose($fout);

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_sip_profile_xml')) {
	function save_sip_profile_xml() {

		//get the global variables
			global $db, $domain_uuid;

		//get the sip profiles from the database
			$sql = "select * from v_sip_profiles ";
			$prep_statement = $db->prepare(check_sql($sql));
			$prep_statement->execute();
			$result = $prep_statement->fetchAll();
			$result_count = count($result);
			unset ($prep_statement, $sql);
			if ($result_count > 0) {
				foreach($result as $row) {
					$sip_profile_uuid = $row['sip_profile_uuid'];
					$sip_profile_name = $row['sip_profile_name'];

					//get the xml sip profile template
						if ($sip_profile_name == "internal" || $sip_profile_name == "external" || $sip_profile_name == "internal-ipv6") {
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/sip_profiles/resources/xml/sip_profiles/".$sip_profile_name.".xml");
						}
						else {
							$file_contents = file_get_contents($_SERVER["DOCUMENT_ROOT"].PROJECT_PATH."/app/sip_profiles/resources/xml/sip_profiles/default.xml");
						}

					//get the sip profile settings
						$sql = "select * from v_sip_profile_settings ";
						$sql .= "where sip_profile_uuid = '$sip_profile_uuid' ";
						$sql .= "and sip_profile_setting_enabled = 'true' ";
						$prep_statement = $db->prepare(check_sql($sql));
						$prep_statement->execute();
						$result = $prep_statement->fetchAll();
						$sip_profile_settings = '';
						foreach ($result as &$row) {
							$sip_profile_settings .= "		<param name=\"".$row["sip_profile_setting_name"]."\" value=\"".$row["sip_profile_setting_value"]."\"/>\n";
						}
						unset ($prep_statement);

					//replace the values in the template
						$file_contents = str_replace("{v_sip_profile_name}", $sip_profile_name, $file_contents);
						$file_contents = str_replace("{v_sip_profile_settings}", $sip_profile_settings, $file_contents);

					//write the XML config file
						if (is_readable($_SESSION['switch']['conf']['dir']."/sip_profiles/")) {
							$fout = fopen($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name.".xml","w");
							fwrite($fout, $file_contents);
							fclose($fout);
						}

					//if the directory does not exist then create it
						if (!is_readable($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name)) { mkdir($_SESSION['switch']['conf']['dir']."/sip_profiles/".$sip_profile_name,0775,true); }

				} //end foreach
				unset($sql, $result, $row_count);
			} //end if results

		//apply settings
			$_SESSION["reload_xml"] = true;
	}
}

if (!function_exists('save_switch_xml')) {
	function save_switch_xml() {
		if (is_readable($_SESSION['switch']['dialplan']['dir'])) {
			save_dialplan_xml();
		}
		if (is_readable($_SESSION['switch']['extensions']['dir'])) {
			require_once "app/extensions/resources/extension.php";
			$extension = new extension;
			$extension->xml();
		}
		if (is_readable($_SESSION['switch']['conf']['dir'])) {
			save_setting_xml();
			save_module_xml();
			save_var_xml();
			save_call_center_xml();
			save_gateway_xml();
			//save_ivr_menu_xml();
			save_sip_profile_xml();
		}
		if (is_readable($_SESSION['switch']['scripts']['dir'])) {
			save_hunt_group_xml();
		}
	}
}

?>