--
--	FusionPBX
--	Version: MPL 1.1
--
--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/
--
--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.
--
--	The Original Code is FusionPBX
--
--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Copyright (C) 2010
--	the Initial Developer. All Rights Reserved.
--
--	Contributor(s):
--	Mark J Crane <markjcrane@fusionpbx.com>

--set default variables
	max_tries = "3";
	digit_timeout = "5000";

--set the debug level
	debug["sql"] = false;
	debug["var"] = false;
	db_dial_string = "";
	db_extension_uuid = "";

--include config.lua
	scripts_dir = string.sub(debug.getinfo(1).source,2,string.len(debug.getinfo(1).source)-(string.len(argv[0])+1));
	dofile(scripts_dir.."/resources/functions/config.lua");
	dofile(config());

--connect to the database
	dofile(scripts_dir.."/resources/functions/database_handle.lua");
	dbh = database_handle('system');

if ( session:ready() ) then
	session:answer();
	domain_uuid = session:getVariable("domain_uuid");
	pin_number = session:getVariable("pin_number");
	sounds_dir = session:getVariable("sounds_dir");
	sip_from_user = session:getVariable("sip_from_user");
	sip_from_host = session:getVariable("sip_from_host");
	direction = session:getVariable("direction"); --in, out, both
	extension = tostring(session:getVariable("extension")); --true, false
	dial_string = tostring(session:getVariable("dial_string"));
	if (dial_string == "nil") then dial_string = ""; end

	--set the sounds path for the language, dialect and voice
		default_language = session:getVariable("default_language");
		default_dialect = session:getVariable("default_dialect");
		default_voice = session:getVariable("default_voice");
		if (not default_language) then default_language = 'en'; end
		if (not default_dialect) then default_dialect = 'us'; end
		if (not default_voice) then default_voice = 'callie'; end

	--get the unique_id
		min_digits = 1;
		max_digits = 15;
		unique_id = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_id:#", "", "\\d+");

	--add the unique_id value to the dial_string
		if (string.len(dial_string) > 0) then
			dial_string = string.gsub(dial_string, '{v_unique_id}', unique_id);
		end

	--authenticate the user
		if (pin_number) then
			--get the pin number from the caller
				min_digits = string.len(pin_number);
				max_digits = string.len(pin_number)+1;
				caller_pin_number = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
		else
			--get the vm_password
				min_digits = 1;
				max_digits = 12;
				vm_password = session:playAndGetDigits(min_digits, max_digits, max_tries, digit_timeout, "#", "phrase:voicemail_enter_pass:#", "", "\\d+");
				if (debug["sql"]) then
					freeswitch.consoleLog("NOTICE", "unique_id ".. unique_id .. " vm_password " .. vm_password .. "\n");
				end
		end

	--get the dial_string, and extension_uuid
		sql = "SELECT * FROM v_extensions as e, v_domains as d ";
		sql = sql .. "WHERE e.domain_uuid = d.domain_uuid ";
		if (extension == "true") then
			sql = sql .. "AND e.extension = '" .. unique_id .."' ";
			sql = sql .. "AND e.domain_uuid = '" .. domain_uuid .."' ";
		else
			sql = sql .. "AND e.unique_id = '" .. unique_id .."' ";
		end
		if (pin_number) then
			--do nothing
		else
			sql = sql .. "AND e.vm_password = '" .. vm_password .."' ";
		end
		if (debug["sql"]) then
			freeswitch.consoleLog("NOTICE", "sql: ".. sql .. "\n");
		end
		dbh:query(sql, function(row)
			db_domain_uuid = row.domain_uuid;
			db_extension_uuid = row.extension_uuid;
			db_dial_string = row.dial_string;
			db_dial_user = row.dial_user;
			db_dial_domain = row.dial_domain;
		end);

		--check to see if the pin number is correct
		if (pin_number) then
			if (pin_number ~= caller_pin_number) then
				--access denied
				db_extension_uuid = "";
			end
		end

		if (string.len(db_extension_uuid) > 0) then
			--add the dial string
				if (direction == "in") then
					if (string.len(dial_string) == 0) then
						dial_string = [[{sip_invite_domain=]] .. sip_from_host .. [[,presence_id=]] .. sip_from_user .. [[@]] .. sip_from_host .. [[}${sofia_contact(]] .. sip_from_user .. [[@]] .. sip_from_host .. [[)}]];
					end
					sql = "UPDATE v_extensions SET ";
					sql = sql .. "dial_string = '" .. dial_string .."', ";
					sql = sql .. "dial_user = '" .. sip_from_user .."', ";
					sql = sql .. "dial_domain = '" .. sip_from_host .."' ";
					sql = sql .. "WHERE extension_uuid = '" .. db_extension_uuid .."' ";
					if (debug["sql"]) then
						freeswitch.consoleLog("NOTICE", "[dial_string] sql: ".. sql .. "\n");
					end
					dbh:query(sql);
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-saved.wav");
				end
			--remove the dialstring
				if (direction == "out") then
					--if the the dial_string has a value then clear the dial string
					sql = "UPDATE v_extensions SET ";
					sql = sql .. "dial_string = null, ";
					sql = sql .. "dial_user = null, ";
					sql = sql .. "dial_domain = null ";
					sql = sql .. "WHERE extension_uuid = '" .. db_extension_uuid .."' ";
					if (debug["sql"]) then
						freeswitch.consoleLog("NOTICE", "[dial_string] sql: ".. sql .. "\n");
					end
					dbh:query(sql);
					session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-deleted.wav");
				end
			--toggle the dial string
				if (direction == "both") then
					if (string.len(db_dial_string) > 1) then
						--if the the dial_string has a value then clear the dial string
						sql = "UPDATE v_extensions SET ";
						sql = sql .. "dial_string = null, ";
						sql = sql .. "dial_user = null, ";
						sql = sql .. "dial_domain = null ";
						sql = sql .. "WHERE extension_uuid = '" .. db_extension_uuid .."' ";
						if (debug["sql"]) then
							freeswitch.consoleLog("NOTICE", "[dial_string] sql: ".. sql .. "\n");
						end
						dbh:query(sql);
						session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-deleted.wav");
					else
						--if the dial string is empty then set the dial string
						if (string.len(dial_string) == 0) then
							dial_string = [[{sip_invite_domain=]] .. sip_from_host .. [[,presence_id=]] .. sip_from_user .. [[@]] .. sip_from_host .. [[}${sofia_contact(]] .. sip_from_user .. [[@]] .. sip_from_host .. [[)}]];
						end
						sql = "UPDATE v_extensions SET ";
						sql = sql .. "dial_string = '" .. dial_string .."', ";
						sql = sql .. "dial_user = '" .. sip_from_user .."', ";
						sql = sql .. "dial_domain = '" .. sip_from_host .."' ";
						sql = sql .. "WHERE extension_uuid = '" .. db_extension_uuid .."' ";
						if (debug["sql"]) then
							freeswitch.consoleLog("NOTICE", "[dial_string] sql: ".. sql .. "\n");
						end
						dbh:query(sql);
						session:streamFile(sounds_dir.."/"..default_language.."/"..default_dialect.."/"..default_voice.."/voicemail/vm-saved.wav");
					end
				end
		else
			session:streamFile("phrase:voicemail_fail_auth:#");
			session:hangup("NORMAL_CLEARING");
			return;
		end

	--log to the console
		if (debug["var"]) then
			freeswitch.consoleLog("NOTICE", "sip_from_host: ".. sip_from_host .. "\n");
			freeswitch.consoleLog("NOTICE", "extension_uuid: ".. extension_uuid .. "\n");
			freeswitch.consoleLog("NOTICE", "dial_string: ".. dial_string .. "\n");
		end

	--show call variables
		--session:execute("info", "");

end
