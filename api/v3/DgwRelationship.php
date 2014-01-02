<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to get relationship for a contact
*/
function civicrm_api3_dgw_relationship_get($inparms) {

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
            dgwcontact_relationshipget");
    } else {
        $contact_id = trim($inparms['contact_id']);
    }
    if (empty($contact_id)) {
        return civicrm_api3_create_error( 'Leeg contact_id voor
            dgwcontact_relationshipget' );
    } else {
        if (!is_numeric($contact_id)) {
            return civicrm_api3_create_error( 'Contact_id '.$contact_id.' heeft
                niet numerieke waarden in dgwcontact_relationshipget');
        }
    }
	
	/**
	 * Use the relationship api
	 */
	$civiparms['contact_id'] = $contact_id;
	$civires1 = civicrm_api('relationship', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {
		$data = array();
		
		$data['contact_id_from'] = $contact_id;
		
		if (isset($result['cid'])) {
			$data['contact_id_to'] = $result['cid'];
		}
		if (isset($result['name'])) {
			$data['contact_name_to'] = $result['name'];
		}
		if (isset($result['relationship_type_id'])) {
			$data['relationship_type_id'] = $result['relationship_type_id'];
		}
		if (isset($result['relation'])) {
			$data['relationship'] = $result['relation'];
		}
		if (isset($result['start_date'])) {
			$data['start_date'] = date("Y-m-d", strtotime($result['start_date']));
		}
		if (isset($result['end_date'])) {
			$data['end_date'] = date("Y-m-d", strtotime($result['end_date']));
		}
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
