<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to remove contact from a CiviCRM group
*/
function civicrm_api3_dgw_group_remove($inparms) {
	/*
	 * if no contact_id or persoonsnummer_first passed, error
	*/
	if (!isset($inparms['contact_id']) && !isset($inparms['persoonsnummer_first'])) {
		return civicrm_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
	}
	if (isset($inparms['contact_id'])) {
		$contact_id = trim($inparms['contact_id']);
	} else {
		$contact_id = null;
	}
	if (isset($inparms['persoonsnummer_first'])) {
		$pers_nr = trim($inparms['persoonsnummer_first']);
	} else {
		$pers_nr = null;
	}
	if (empty($contact_id) && empty($pers_nr)) {
		return civicrm_api3_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
	}
	/*
	 * if no group_id or group_name passed, error
	*/
	if (!isset($inparms['group_id']) && !isset($inparms['group_name'])) {
		return civicrm_api3_create_error("Group_id en group_name ontbreken beiden");
	}
	if (isset($inparms['group_id'])) {
		$group_id = trim($inparms['group_id']);
	} else {
		$group_id = null;
	}
	if (isset($inparms['group_name'])) {
		$group_name = trim($inparms['group_name']);
	} else {
		$group_name = null;
	}
	if (empty($group_id) && empty($group_name)) {
		return civicrm_api3_create_error("Group_id en group_name ontbreken beiden");
	}
	
	$civiparms1['version'] = 3;
	/*
	 * contact has to exist in CiviCRM, either with contact_id or with
	* persoonsnummer_first
	*/
	if (isset($pers_nr) && !empty($pers_nr)) {
		$persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
		$civiparms1['custom_'.$persoonsnummer_first_field['id']] = $pers_nr;
	} else {
		$civiparms1['contact_id'] = $contact_id;
	}
	
	$res_contact = civicrm_api('Contact', 'get', $civiparms1);
	if (civicrm_error($res_contact)) {
		return civicrm_api3_create_error("Contact niet gevonden");
	}
	$res_contact = reset($res_contact['values']);
	$contact_id = $res_contact['contact_id'];
	
	/*
	 * if group not in civicrm, error
	*/
	$checkparms = array();
	if (empty($group_id)) {
		$checkparms = array("name"    =>  $group_name);
	} else {
		$checkparms = array("id"    =>  $group_id);
	}
	$checkparms['version'] = 3;
	$check_group = civicrm_api('Group', 'get', $checkparms);
	if (civicrm_error($check_group)) {
		return civicrm_api3_create_error("Groep niet gevonden");
	} else {
		$check_group = reset($check_group['values']);
		$group_id = $check_group['id'];
	}
	
	/*
	 * remove contact_id from group_id with standard API
	*/
	$civiparms = array(
			"version"		=> 3,
			"contact_id"    =>  $contact_id,
			"group_id"      =>  $group_id);
	$res_add = civicrm_api("GroupContact", "delete", $civiparms);
	if (civicrm_error($res_add)) {
		return civicrm_api3_create_error($res_add['error_message']);
	} else {
		$outparms['is_error'] = "0";
	}
	return $outparms;
}

/*
 * function to update group
 */
function civicrm_api3_dgw_group_create($inparms) {
	/*
	 * if no contact_id passed, error
	*/
	if (!isset($inparms['contact_id'])) {
		return civicrm_api3_create_error("Contact_id ontbreekt");
	} else {
		$contact_id = trim($inparms['contact_id']);
	}
	/*
	 * if no group_id passed and no group_name passed, error
	*/
	if (!isset($inparms['group_id']) && !isset($inparms['group_name'])) {
		return civicrm_api3_create_error("Group_id en group_name ontbreken");
	}
	/*
	 * if group_id passed, put in $group_id else null
	*/
	if (isset($inparms['group_id'])) {
		$group_id = trim($inparms['group_id']);
	} else {
		$group_id = null;
	}
	/*
	 * if group_name passed, put in $group_name else null
	*/
	if (isset($inparms['group_name'])) {
		$group_name = trim($inparms['group_name']);
	} else {
		$group_name = null;
	}
	/*
	 * if both $group_id and $group_name are empty, error
	*/
	if (empty($group_id) && empty($group_name)) {
		return civicrm_api3_create_error("Group_id en group_name ontbreken");
	}
	/*
	 * if contact not in civicrm, error
	*/
	$checkparms = array("contact_id" => $contact_id);
	$checkparms['version'] = 3;
	$check_contact = civicrm_api('Contact', 'get', $checkparms);
	if (civicrm_error($check_contact)) {
		return civicrm_api3_create_error("Contact niet gevonden");
	}
	
	/*
	 * if group not in civicrm, error
	*/
	$checkparms = array();
	if (empty($group_id)) {
		$checkparms = array("name"    =>  $group_name);
	} else {
		$checkparms = array("id"    =>  $group_id);
	}
	$checkparms['version'] = 3;
	$check_group = civicrm_api('Group', 'get', $checkparms);
	if (civicrm_error($check_group)) {
		return civicrm_api3_create_error("Groep niet gevonden");
	} else {
		$check_group = reset($check_group['values']);
		$group_id = $check_group['id'];
	}
	/*
	 * if validation passed, add contact to group in CiviCRM
	*/
	$civiparms = array(
		"version" => 3,
		"contact_id"  =>  $contact_id,
		"group_id"      =>  $group_id);
	$res_add = civicrm_api("GroupContact", "Create", $civiparms);
	if (civicrm_error($res_add)) {
		return civicrm_api3_create_error("Onverwachte fout, contact is niet aan groep toegevoegd, CiviCRM melding : ".$res_add['error_message']);
	} else {
		$outparms = array("is_error" => "0");
	}
	return $outparms;
}

/*
 * Function to get phones for a contact
*/
function civicrm_api3_dgw_group_get($inparms) {

	/*
	 * initialize output parameter array
	*/
	$outparms = array("");
	$civiparms = array (
			'version' => 3,
	);
	
	/*
	 * if contact_id empty or not numeric, error
	*/
	if (!isset($inparms['contact_id'])) {
		return civicrm_api3_create_error("Geen contact_id in parms in
            dgwcontact_groupget");
	} else {
		$contact_id = trim($inparms['contact_id']);
	}
	
	if (empty($contact_id)) {
		return civicrm_api3_create_error( 'Leeg contact_id voor
            dgwcontact_groupget' );
	} else {
		if (!is_numeric($contact_id)) {
			return civicrm_api3_create_error( 'Contact_id '.$contact_id.' heeft
                niet numerieke waarden in dgwcontact_groupget');
		}
	}

	$civiparms['contact_id'] = $contact_id;


	/**
	 * Use the group api
	 */
	$civires1 = civicrm_api('GroupContact', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {		
		$data = $result;
		 
		$data['group_id'] = $data['id'];
		unset($data['id']);
		
		$data['contact_id'] = $contact_id;
		
		$data['group_title'] = $data['title'];
		unset($data['title']);
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
