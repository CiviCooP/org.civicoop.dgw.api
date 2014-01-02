<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

/*
 * Function to get note for a contact
*/
function civicrm_api3_dgw_note_get($inparms) {

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
            dgwcontact_noteget");
    } else {
        $contact_id = trim($inparms['contact_id']);
    }

    if (empty($contact_id)) {
        return civicrm_api3_create_error( 'Leeg contact_id voor
            dgwcontact_noteget' );
    } else {
        if (!is_numeric($contact_id)) {
            return civicrm_api3_create_error( 'Contact_id '.$contact_id.' heeft
                niet numerieke waarden in dgwcontact_noteget');
        }
    }
	
	/**
	 * Use the note api
	 */
	$civiparms['entity_id'] = $contact_id;
	$civiparms['entity_table'] = 'civicrm_contact';
	$civires1 = civicrm_api('note', 'get', $civiparms);
	if (civicrm_error($civires1)) {
		return civicrm_api3_create_error($civires1['error_message']);
	}

	$i = 1;
	foreach ($civires1['values'] as $result) {
		$data = array();
		
		$data['contact_id'] = $contact_id;
		
		if (isset($result['note'])) {
			$data['note'] = trim(strip_tags($result['note']));
		}
		if (isset($result['subject'])) {
			$data['subject'] = $result['subject'];
		}
		if (isset($result['modified_date'])) {
			$data['modified_date'] = date("Y-m-d", strtotime($result['modified_date']));
		}
		 
		$outparms[$i] = $data;
		$i++;
	}
	$outparms[0]['record_count'] = $i - 1;
	return $outparms;
}
