<?php
/**
 +--------------------------------------------------------------------+
 | De Goede Woning CiviCRM  Specifieke Contact API, gebaseerd op      |
 |                          standaard CiviCRM Contact API             |
 |                          Beschreven in 'Detailontwerp API ophalen  |
 |                          contact uit CiviCRM.doc'                  |
 | Copyright (C) 2010 Erik Hommel and De Goede Woning                 |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
 +--------------------------------------------------------------------+
 | This file is based on CiviCRM, and owned by De Goede Woning        |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 | Author   :   Erik Hommel (EE-atWork, hommel@ee-atwork.nl           |
 |                  www.ee-atwork.nl)                                 |
 | Date     :   28 October 2010                                       |
 | Project  :   Implementation CiviCRM at De Goede Woning             |
 | Descr.   :   Specific versions wrapped around standard API's to    |
 |              facilitate synchronization of data between different  |
 |              systems and website. At time of creation, called by   |
 |              the CiviCRM REST interface                            |
 +--------------------------------------------------------------------+
 | Incident 19 06 12 004 Add custom data to household for             |
 | dgwcontact_get function                                            |
 |                                                                    |
 | Author	:	Erik Hommel (EE-atWork, hommel@ee-atwork.nl)  |
 | Date		:	19 June 2012                                  |
 +--------------------------------------------------------------------+
 | Incident 05 02 13 003 Organization name always empty when synced   |
 |                       from First. Check for org name               |
 | Author	:	Erik Hommel (erik.hommel@civicoop.org)        |
 | Date		:	07 Feb 2013                                   |
 +--------------------------------------------------------------------+
 */

/**
 * Function to get details of a contact
 */
function civicrm_api3_dgw_contact_get($inparms) {
    /*
     * initialize output array
     */
    $outparms = array("");

    /**
     * array to hold all possible input parms
     *
     */
    $valid_input = array("contact_id", "persoonsnummer_first", "achternaam",
        "geboortedatum", "bsn", "contact_type");
    /*
     * check if input parms hold at least one valid parameter
     */
    $valid = false;
    foreach ($valid_input as $validparm) {
        if (isset($inparms[$validparm])) {
            $valid = true;
        }
    }
    if (!$valid) {
        return civicrm_api3_create_error( 'Geen geldige input parameters voor dgwcontact_get' );
    }
    /*
     * only if valid parameters used
     */
    if ($valid) {
        /*
         * standard API returns default 25 rows. For DGW changed here: if no
         * rowCount passed, default = 100
         */
        if (isset($inparms['rowCount'])) {
            $rowCount = $inparms['rowCount'];
        } else {
            $rowCount = 100;
        }
        $civiparms1 = array(
        	"rowCount"   => $rowCount,
        	"version"    => 3
        );
        $persoonsnr_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Persoonsnummer_First');
        $nr_in_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Nr_in_First');
        $bsn_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('BSN');
        /*
         * if contact_id entered, no further parms needed
         */
        if (isset($inparms['contact_id'])) {
            $civiparms1['contact_id'] = $inparms['contact_id'];
            $civires1 = civicrm_api('Contact', 'get', $civiparms1);
        } elseif (isset($inparms['persoonsnummer_first'])) {
            /*
             * if persoonsnummer_first entered, no further parms needed
             * (issue 240 ook voor organisatie)
             */
            $civiparms1['contact_type'] = 'Individual';
            $civiparms1['custom_'.$persoonsnr_first_field['id']] = $inparms['persoonsnummer_first'];
            $civires1 = civicrm_api('Contact', 'get', $civiparms1);
            if (key($civires1) == null) {
            	unset($civiparms1[CFPERSNR]);
            	$inparms['contact_type'] = "Organization";
            	$civiparms1['contact_type'] = 'Organization';
            	$civiparms1['custom_'.$nr_in_first_field['id']] = $inparms['persoonsnummer_first'];
            	$civires1 = civicrm_api('Contact', 'get', $civiparms1);
            }
        } else {
            if (isset($inparms['bsn']) && !empty($inparms['bsn'])) {
                $civiparms1['custom_'.$bsn_field['id']] = $inparms['bsn'];
            }
            if (isset($inparms['achternaam']) && !empty($inparms['achternaam'])) {
                $civiparms1['last_name'] = trim($inparms['achternaam']);
            }
            if (isset($inparms['geboortedatum']) && !empty($inparms['geboortedatum'])) {
                $civiparms1['birth_date'] = $inparms['geboortedatum'];
            }
            if (isset($inparms['contact_type']) && !empty($inparms['contact_type'])) {
                $civiparms1['contact_type'] = $inparms['contact_type'];
            }
            $civires1 = civicrm_api('Contact', 'get', $civiparms1);
        }
        /*
         * check results from civicrm_contact_get, if error return error
         */
        if (civicrm_error($civires1)) {
            return civicrm_api3_create_error($civires1['error_message']);
        } else {
            /*
             * if no error, set contact part of output parms. Result could
             * contain more contacts, so for each contact in $civires
             */
            $i = 1;
            foreach ($civires1['values'] as $result) {
                $contact_id = $result['contact_id'];

                $data = $result;

                //retrieve custom values for contact
                $customvalues = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContact($data);
                if ($customvalues['is_error'] == '0') {
                    if ( isset( $customvalues['values'] ) ) {
                	foreach($customvalues['values'] as $value) {
                            if (isset($value['normalized_value'])) {
                                $data[$value['name'].'_id'] = $value['value'];
                                $data[$value['name']] = $value['normalized_value'];
                            } else {
                                $data[$value['name']] = $value['value'];
                            }
                	}
                    }
                }
                /*
                 * incident 20 11 12 002 retrieve is_deleted for contact
                 */
                $data['is_deleted'] = $data['contact_is_deleted'];
                unset($data['contact_is_deleted']);
                /*
                 * vanaf CiviCRM 3.3.4 website in aparte tabel
                 * en niet meer in standaard API
                 */
                $civires4 = civicrm_api('Website', 'get', array(
                    'version' => 3,
                    'contact_id' => $contact_id
                ));
                if ($civires4['is_error'] == '0' && isset($civires4['values']) && is_array($civires4['values']) && count($civires4['values'])) {
                    $website = reset($civires4['values']);
                    if (isset($website['url'])) {
                        $data['home_URL'] = $website['url'];
                    }
                }
                $outparms[$i] = $data;
                $i++;
            }
        }
    }
    $outparms[0]['record_count'] = ($i - 1);
    return ($outparms);
}
/**
 * Function to create new contact
 */
function civicrm_api3_dgw_contact_create($inparms) {
    /*
     * set superglobal to avoid double create via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * If contact_type passed and not valid, error. Else set contact_type
     * to default 'Individual'
     */
    if (isset($inparms['contact_type'])) {
        $contact_type = trim(ucfirst(strtolower($inparms['contact_type'])));
        if ($contact_type != "Individual" && $contact_type != "Household" && $contact_type != "Organization") {
            return civicrm_api3_create_error("Ongeldig contact_type $contact_type");
        }
    } else {
        $contact_type = "Individual";
    }
    /*
     * If type is not Individual, name is mandatory
     */
    if ($contact_type != "Individual") {
        if (!isset($inparms['name'])) {
            return civicrm_api3_create_error("Geen first_name/last_name of name gevonden");
        } else {
            $name = trim($inparms['name']);
        }
        if (empty($name)) {
            return civicrm_api3_create_error("Geen first_name/last_name of name gevonden");
        }
    }
    
    /*
     * BOS1503891 insite - qoutes en apostrophes in civi
     * First sends contacts with slashes before qoutes
     * Here they extra slashes are removed
     */
    foreach($inparms as $field => $value){
      if(!is_array($value)){
        $inparms[$field] = stripslashes($value);
      }
    }
    
    $gender_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('gender');
    $gender_values = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($gender_group_id);
    /*
     * If type is Individual, a number of checks need to be done
     */
    if ($contact_type == "Individual") {
        /*
         * first and last name are mandatory
         * issue 85: not for First org (gender = 4)
         */
        if (!isset($inparms['first_name']) && (isset($inparms['gender_id']) && $inparms['gender_id'] != 4)) {
            return civicrm_api3_create_error("Geen first_name/last_name of name gevonden");
        } else {
            if (isset($inparms['first_name'])) {
                $first_name = trim($inparms['first_name']);
            } else {
                $first_name = '';
            }
        }
        if (empty($first_name) && (isset($inparms['gender_id']) && $inparms['gender_id'] != 4)) {
            return civicrm_api3_create_error("Geen first_name/last_name of name gevonden");
        }
        if (!isset($inparms['last_name'])) {
            return civicrm_api3_create_error("Geen first_name/last_name of name gevonden");
        } else {
            $last_name = trim($inparms['last_name']);
        }
        if (empty($last_name)) {
            return civicrm_api3_create_error("Geen first_name/last_name of name gevonden");
        }
        /*
         * gender_id has to be valid if entered. If not entered, use default
         */
        if (isset($inparms['gender_id'])) {
            $gender_id = trim($inparms['gender_id']);
            if (!array_key_exists($inparms['gender_id'], $gender_values) && $gender_id != 4) {
                return civicrm_api3_create_error("Gender_id is ongeldig");
            }
        } else {
            foreach($gender_values as $val) {
                if (strtolower($val['name']) == 'onbekend') {
                    $gender_id = $val['value'];
                    break;
                }
            }
        }
        /*
         * issue 149: if gender = 4, persoonsnummer first has to be passed
         */
        if ($gender_id == 4) {
            if (!isset($inparms['persoonsnummer_first']) || empty($inparms['persoonsnummer_first'])) {
                return civicrm_api3_create_error("Gender_id 4 mag alleen als persoonsnummer first ook gevuld is");
            }
        }
        /*
         * BSN will have to pass 11-check if entered
         */
        if (isset($inparms['bsn'])) {
            $bsn = trim($inparms['bsn']);
            if (!empty($bsn)) {
                $bsn_valid = CRM_Utils_DgwUtils::validateBsn($bsn);
                if (!$bsn_valid) {
                    return civicrm_api3_create_error("Bsn voldoet niet aan 11-proef");
                }
            }
        }
        /*
         * if birth date is entered, format has to be valid
         */
        if (isset($inparms['birth_date']) && !empty($inparms['birth_date'])) {
            $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['birth_date']);
            if (!$valid_date) {
                return civicrm_api3_create_error("Onjuiste formaat birth_date");
            } else {
                $birth_date = $inparms['birth_date'];
            }
        } else {
            $birth_date = "";
        }
        /*
         * if individual already exists with persoonsnummer_first, error
         */
        $persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
        if (isset($inparms['persoonsnummer_first'])) {
            $pers_first = trim($inparms['persoonsnummer_first']);
            $checkparms = array("custom_".$persoonsnummer_first_field['id'] => $pers_first);
            $checkparms['version'] = 3;
            $check_contact = civicrm_api('Contact', 'get', $checkparms);
            if (!civicrm_error($check_contact) && $check_contact['count'] > 0) {
            	return civicrm_api3_create_error("Persoon bestaat al");
            }
        }
        /*
         * if burg_staat entered and invalid, error
         */
        if (isset($inparms['burg_staat_id'])) {
            $burg_staat_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('burgerlijke_staat_20110110165605');
            $burg_staat_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($burg_staat_group_id);
            if (!array_key_exists($inparms['burg_staat_id'], $burg_staat_options)) {
                return civicrm_api3_create_error("Burg_staat_id is ongeldig");
            }
            $burg_staat_id = $inparms['burg_staat_id'];
        }
        /*
         * if huidige woonsituatie entered, explode and if any value invalid,
         * error
         */
        if (isset($inparms['huidige_woonsituatie'])) {
            $woon_sit_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('huidige_woonsituatie_20110111121521');
            $woon_sit_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($woon_sit_group_id);
            $values = explode(",", $inparms['huidige_woonsituatie']);
            $teller = 0;
            $huidige_woonsit = null;
            foreach ($values as $value) {
                if (!empty($value)) {
                    if (!array_key_exists($value, $woon_sit_options)) {
                        return civicrm_api3_create_error("Huidige woonsituatie is ongeldig");
                    } else {
                        $huidige_woonsit = $huidige_woonsit.$value.CRM_Core_DAO::VALUE_SEPARATOR;
                        $teller ++;
                    }
                }
            }
            if ($teller > 0) {
                $huidige_woonsit = CRM_Core_DAO::VALUE_SEPARATOR.$huidige_woonsit;
            }
        }
        /*
         * if hoofdhuurder entered, only 0 or 1 are allowed
         */
        if (isset($inparms['hoofdhuurder'])) {
            $hoofdhuurder = (int) trim($inparms['hoofdhuurder']);
            if ($hoofdhuurder != 0 and $hoofdhuurder != 1) {
                return civicrm_api3_create_error("Hoofdhuurder is ongeldig");
            }
        }
        /*
         * if andere corporatie entered, error if invalid
         */
        if (isset($inparms['andere_corporatie'])) {
            $andere_corp = (int) trim($inparms['andere_corporatie']);
            $andere_corp_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('welke_andere_corporatie_20110111121815');
            $andere_corp_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($andere_corp_group_id);
            if (!array_key_exists($andere_corp, $andere_corp_options)) {
                return civicrm_api3_create_error("Andere corporatie is ongeldig");
            }
        }
        /*
         * if bruto jaarinkomen entered, only empty or numeric allowed
         */
        if (isset($inparms['bruto_jaarinkomen'])) {
            $bruto_jaarinkomen = trim($inparms['bruto_jaarinkomen']);
            if (empty($bruto_jaarinkomen)) {
                $bruto_jaarinkomen = 0;
            }
            if (!is_numeric($bruto_jaarinkomen)) {
                return civicrm_api3_create_error("Bruto jaarinkomen heeft ongeldige tekens");
            }
        }
        /*
         * if huishoudgrootte entered, error if invalid
         */
        if (isset($inparms['huishoudgrootte'])) {
            $huishoudgrootte = (int) trim($inparms['huishoudgrootte']);
            $huishoudgrootte_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('huishoudgrootte_20110111122358');
            $huishoudgrootte_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($huishoudgrootte_group_id);
            if (!array_key_exists($huishoudgrootte, $huishoudgrootte_options)) {
                return civicrm_api3_create_error("Huishoudgrootte is ongeldig");
            }
        }
        /*
         * if aanbod bekend entered, explode and if any value invalid,
         * error
         */
        if (isset($inparms['aanbod_bekend'])) {
            $aanbod_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('bekend_met_koopaanbod_20110111122551');
            $aanbod_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($aanbod_group_id);
            $aanbod_bekend = null;
            $teller = 0;
            $values = explode(",", $inparms['aanbod_bekend']);
            foreach ($values as $value) {
                if (!empty($value)) {
                    if (!array_key_exists($value, $aanbod_options)) {
                        return civicrm_api3_create_error("Aanbod bekend is ongeldig");
                    } else {
                        $aanbod_bekend = $aanbod_bekend.$value.CRM_Core_DAO::VALUE_SEPARATOR;
                        $teller ++;
                    }
                }
            }
            if ($teller > 0) {
                $aanbod_bekend = $aanbod_bekend.CRM_Core_DAO::VALUE_SEPARATOR;
            }
        }
        /*
         * if particulier entered, only  0 or 1 are allowed
         */
        if (isset($inparms['particulier'])) {
            $particulier = (int) trim($inparms['particulier']);
            if ($particulier != 0 and $particulier != 1) {
                return civicrm_api3_create_error("Particulier is ongeldig");
            }
        }
        /*
         * if woonkeusdatum is entered, format has to be valid
         */
        if (isset($inparms['woonkeusdatum'])) {
            $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['woonkeusdatum']);
            if (!$valid_date) {
                return civicrm_api3_create_error("Onjuiste formaat woonkeusdatum");
            } else {
                $woonkeusdatum = $inparms['woonkeusdatum'];
            }
        }
    }
    /*
     * If we get here, all validation has been succesful. Now first the
     * CiviCRM contact can be created. First set parameters based on
     * contact type
     *
     * Issue 149: gender_id 4 means organization has to be set with
     * persoonsnummer first and name as concat(first, middle and last) name
     */
    $middle_name = "";
    if (isset($inparms['middle_name'])) {
    	$middle_name = trim($inparms['middle_name']);
    }
    if ($gender_id == 4) {
        $contact_type = "Organization";
        $name = null;
        if (isset($first_name) && !empty($first_name)) {
            $name = $first_name;
        }
        if (isset($middle_name) && !empty($middle_name)) {
           if (empty($name)) {
               $name = $middle_name;
           } else {
               $name .= " ".$middle_name;
           }
        }
        if (isset($last_name) && !empty($last_name)) {
            if (empty($name)) {
                $name = $last_name;
            } else {
                $name .= " ".$last_name;
            }
        }
    }
    $civiparms['version'] = 3;
    switch ($contact_type) {
        case "Household":
            $civiparms['contact_type'] = 'Household';
            $civiparms['household_name'] = $name;
            break;
        case "Organization":
            if (isset($inparms['home_url'])) {
                $homeURL = trim(stripslashes($inparms['home_url']));
            } else {
                $homeURL = "";
            }
            if (isset($inparms['kvk_nummer'])) {
                $kvk_nummer = trim($inparms['kvk_nummer']);
            } else {
                $kvk_nummer = "";
            }
            $civiparms['contact_type'] = 'Organization';
            $civiparms['organization_name'] = $name;
            $civiparms['sic_code'] = $kvk_nummer;
            break;
        case "Individual":
            if (isset($inparms['middle_name'])) {
                $middle_name = trim($inparms['middle_name']);
            } else {
                $middle_name = "";
            }
            $civiparms["contact_type"] = 'Individual';
            $civiparms["first_name"] = $first_name;
            $civiparms["last_name"] = $last_name;
            $civiparms["middle_name"] = CRM_Core_DAO::escapeString($middle_name);
            $civiparms["gender_id"] = $gender_id;
            if (isset($inparms['show_all'])) {
            	$civiparms["show_all"] = $inparms['show_all'];
            }
            if (isset($birth_date) && !empty($birth_date)) {
                $civiparms['birth_date'] = date("Ymd", strtotime($birth_date));
            }
            break;
    }
    /*
     * use standard API to create CiviCRM contact
     */
    $create_contact = civicrm_api('Contact', 'create', $civiparms);
    if (civicrm_error($create_contact)) {
    	return civicrm_api3_create_error('Onbekende fout: '.$create_contact['error_message']);
    }
    $contact_id = $create_contact['id'];
    $customparms['entity_id'] = $contact_id;
    $customparms['version'] = 3;
    /**
     * Set website
     */
    if (isset($homeURL) && !empty($homeURL)) {
    	$home_url_params['version'] = 3;
    	$home_url_params['contact_id'] = $contact_id;
    	$home_url_params['website'] = $homeURL;
    	civicrm_api('Website', 'Create', $home_url_params);
    }
    /*
     * create custom data for Individual
     */
    if ($contact_type=="Individual") {
        $bsn_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('BSN');
        $burg_staat_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Burgerlijke_staat');
        $saldo_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Totaal_debiteur');
        $woonkeusnr_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Inschrijfnummer_Woonkeus');
        $woonkeusdatum_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Datum_inschrijving_woonkeus');
        $woonsit_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Huidige_woonsituatie');
        $hoofdhuurder_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Hoofdhuurder');
        $anderecorp_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Welke_andere_corporatie');
        $jaarinkomen_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Bruto_jaarinkomen');
        $huishoudgrootte_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Huishoudgrootte');
        $aanbod_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Bekend_met_koopaanbod');
        $particulier_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Particuliere_markt');

       $customparms['contact_id'] = $contact_id;
       if (isset($pers_first)) {
           $customparms['custom_'.$persoonsnummer_first_field['id']] = $pers_first;
       }
       if (isset($bsn)) {
           $customparms['custom_'.$bsn_field['id']] = $bsn;
       }
       if (isset($burg_staat_id)) {
           $customparms['custom_'.$burg_staat_field['id']] = $burg_staat_id;
       }
       if (isset($saldo)) {
           $customparms['custom_'.$saldo_field['id']] = $saldo;
       }
       if (isset($inparms['woonkeusnummer'])) {
           $customparms['custom_'.$woonkeusnr_field['id']] = trim($inparms['woonkeusnummer']);
       }
       if (isset($woonkeusdatum) && !empty($woonkeusdatum)) {
           $customparms['custom_'.$woonkeusdatum_field['id']] = date("Ymd", strtotime($woonkeusdatum));
       }
       if (isset($huidige_woonsit)) {
           $customparms['custom_'.$woonsit_field['id']] = $huidige_woonsit;
       }
       if (isset($hoofdhuurder)) {
           $customparms['custom_'.$hoofdhuurder_field['id']] = $hoofdhuurder;
       }
       if (isset($andere_corp)) {
           $customparms['custom_'.$anderecorp_field['id']] = $andere_corp;
       }
       if (isset($bruto_jaarinkomen)) {
           $customparms['custom_'.$jaarinkomen_field['id']] = $bruto_jaarinkomen;
       }
       if (isset($huishoudgrootte)) {
           $customparms['custom_'.$huishoudgrootte_field['id']] = $huishoudgrootte;
       }
       if (isset($aanbod_bekend)) {
           $customparms['custom_'.$aanbod_field['id']] = $aanbod_bekend;
       }
       if (isset($particulier)) {
           $customparms['custom_'.$particulier_field['id']] = $particulier;
       }
       /*
        * following fields have to be entered for the synchronization of
        * contact, address, email and phone if persoonsnummer first has
        * been entered. Contain key values from First
        **/
        if (isset($pers_first) && !empty($pers_first)) {
            $action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
            $entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
            $entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
            $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
            $changeDate = date('Ymd');
            $customparms['custom_'.$action_field['id']] = "none";
            $customparms['custom_'.$entity_field['id']] = "contact";
            $customparms['custom_'.$entity_id_field['id']] = $contact_id;
            $customparms['custom_'.$key_first_field['id']] = $pers_first;
            $customparms['custom_'.$change_date_field['id']] = $changeDate;
        }
        $civires2 = civicrm_api('CustomValue', 'Create', $customparms);
    }
    /*
     * create custom data for Organization
     **/
     if ($contact_type == "Organization") {
     	/*
     	 * add custom fields if entered
     	 **/
     	if ($gender_id == 4) {
            $nr_in_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Nr_in_First');
            $action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
            $entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
            $entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
            $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            $customparms['custom_'.$action_field['id']] = "none";
            $customparms['custom_'.$entity_field['id']] = "contact";
            $customparms['custom_'.$entity_id_field['id']] = $contact_id;
            $customparms['custom_'.$key_first_field['id']] = $pers_first;
            $customparms['custom_'.$nr_in_first_field['id']] = $pers_first;
            $customparms['contact_id'] = $contact_id;
            $civicres2 = civicrm_api('CustomValue', 'Create', $customparms);
        }
     }
     unset($GLOBALS['dgw_api']);
     $outparms = array(
        "contact_id"    =>  $contact_id,
        "is_error"      =>  0);
     return $outparms;
}
/**
 * Function to update contact
 */
function civicrm_api3_dgw_contact_update($inparms) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * if no contact_id or persoonsnummer_first passed, error
     */
    if (!isset($inparms['contact_id']) && !isset($inparms['persoonsnummer_first'])) {
        return civicrm_api3_create_error("Contact_id en persoonsnummer_first ontbreken beiden");
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
     * BOS1503891 insite - qoutes en apostrophes in civi
     * First sends contacts with slashes before qoutes
     * Here they extra slashes are removed
     */
    foreach($inparms as $field => $value){
      if(!is_array($value)){
        $inparms[$field] = stripslashes($value);
      }
    }
    
    /*
     * contact has to exist in CiviCRM, either with contact_id or with
     * persoonsnummer_first. This needs to be checked with contact_id first,
     * because persoonsnummer_first can be passed when still empty in CiviCRM
     */
    $persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
    $nr_in_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Nr_in_First');
    if (isset($pers_nr) && !empty($pers_nr)) {
        if (!isset($contact_id)) {
            $checkparms = array("custom_".$persoonsnummer_first_field['id'] => $pers_nr);
            $checkparms['version'] = 3;
            $check_contact = civicrm_api('Contact', 'get', $checkparms);
            if (civicrm_error($check_contact) || $check_contact['count'] < 1) {
                $checkparms = array("custom_".$nr_in_first_field['id'] => $pers_nr);
                $checkparms['version'] = 3;
                $check_contact = civicrm_api('Contact', 'get', $checkparms);
                if (civicrm_error($check_contact) || $check_contact['count'] < 1) {
                    return civicrm_api3_create_error("Contact niet gevonden");
                }
            }
            $contact_id = $check_contact['id'];
        }
    }
    $checkparms = array("contact_id" => $contact_id);
    $checkparms['version'] = 3;
    $check_contact = civicrm_api('Contact', 'get', $checkparms);
    if (civicrm_error($check_contact)) {
    	return civicrm_api3_create_error("Contact niet gevonden");
    } else {
    	$check_contact = reset($check_contact['values']);
    	$contact_id = $check_contact['contact_id'];
    	$contact_type = $check_contact['contact_type'];
    }
    /*
     * gender_id has to be valid if entered.
     *
     * In first gender_id  = 4 wordt gebruikt om te geven dat het contact een organisatie is.
     * Nummer 4 bestaat niet in Civi vandaar dat bij nummer 4 de validatie wel correct is
     */
    $gender_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('gender');
    $gender_values = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($gender_group_id);
    $default_gender_id = 3;
    if (isset($inparms['gender_id'])) {
    	$gender_id = trim($inparms['gender_id']);
        if (!array_key_exists($inparms['gender_id'], $gender_values) && $gender_id != 4) {
            return civicrm_api3_create_error("Gender_id is ongeldig");
        }
    }
    //set default gender id
    foreach($gender_values as $val) {
    	if (strtolower($val['name']) == 'onbekend') {
            $default_gender_id = $val['value'];
            break;
    	}
    }
    /*
     * issue 149: if contact type = organization and gender_id is not 4,
     * first_name, last_name and persoonsnummer_first have to be passed
     */
    if ($contact_type == "Organization" && isset($gender_id)) {
        if ($gender_id != 4) {
            if (!isset($inparms['first_name']) || !isset($inparms['last_name']) || !isset($pers_nr)) {
               return civicrm_api3_create_error("First name, last name en persoonsnummer first moeten gevuld zijn als gewijzigd wordt van organisatie naar persoon");
            }
            if (empty($inparms['first_name']) || empty($inparms['last_name']) || empty($pers_nr)) {
                return civicrm_api3_create_error("First name, last name en persoonsnummer first moeten gevuld zijn als gewijzigd wordt van organisatie naar persoon");
            }
        }
    }
    /*
     * BSN will have to pass 11-check if entered
     */
    if (isset($inparms['bsn'])) {
        $bsn = trim($inparms['bsn']);
        if (!empty($bsn)) {
            $bsn_valid = CRM_Utils_DgwUtils::validateBsn($bsn);
            if (!$bsn_valid) {
                return civicrm_api3_create_error("Bsn voldoet niet aan 11-proef");
            }
        }
    }
    /*
     * if birth date is entered, format has to be valid
     */
    if ( isset( $inparms['birth_date'] ) && !empty( $inparms['birth_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['birth_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat birth_date");
        } else {
            $birth_date = $inparms['birth_date'];
        }
    }
    /*
     * if burg_staat entered and invalid, error
     */
    if (isset($inparms['burg_staat_id'])) {
        $burg_staat_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('burgerlijke_staat_20110110165605');
        $burg_staat_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($burg_staat_group_id);
        if (!array_key_exists($inparms['burg_staat_id'], $burg_staat_options)) {
            return civicrm_api3_create_error("Burg_staat_id is ongeldig");
        }
        $burg_staat_id = $inparms['burg_staat_id'];
    }
    /*
     * if huidige woonsituatie entered, explode and if any value invalid,
     * error
     */
    if (isset($inparms['huidige_woonsituatie'])) {
    	$woon_sit_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('huidige_woonsituatie_20110111121521');
    	$woon_sit_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($woon_sit_group_id);
    	$values = explode(",", $inparms['huidige_woonsituatie']);
    	$teller = 0;
    	$huidige_woonsit = null;
    	foreach ($values as $value) {
            if (!empty($value)) {
                if (!array_key_exists($value, $woon_sit_options)) {
                    return civicrm_api3_create_error("Huidige woonsituatie is ongeldig");
                } else {
                    $huidige_woonsit = $huidige_woonsit.$value.CRM_Core_DAO::VALUE_SEPARATOR;
                    $teller ++;
                }
            }
    	}
    	if ($teller > 0) {
            $huidige_woonsit = CRM_Core_DAO::VALUE_SEPARATOR.$huidige_woonsit;
    	}
    }
    /*
     * if hoofdhuurder entered, only 0 or 1 are allowed
     */
    if (isset($inparms['hoofdhuurder'])) {
        $hoofdhuurder = (int) trim($inparms['hoofdhuurder']);
        if ($hoofdhuurder != 0 and $hoofdhuurder != 1) {
            return civicrm_api3_create_error("Hoofdhuurder is ongeldig");
        }
    }
    /*
     * if andere corporatie entered, error if invalid
     */
    if (isset($inparms['andere_corporatie'])) {
    	$andere_corp = (int) trim($inparms['andere_corporatie']);
    	$andere_corp_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('welke_andere_corporatie_20110111121815');
    	$andere_corp_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($andere_corp_group_id);
    	if (!array_key_exists($andere_corp, $andere_corp_options)) {
            return civicrm_api3_create_error("Andere corporatie is ongeldig");
    	}
    }
    /*
     * if bruto jaarinkomen entered, only empty or numeric allowed
     */
    if (isset($inparms['bruto_jaarinkomen'])) {
        $bruto_jaarinkomen = trim($inparms['bruto_jaarinkomen']);
        if (empty($bruto_jaarinkomen)) {
            $bruto_jaarinkomen = 0;
        }
        if (!is_numeric($bruto_jaarinkomen)) {
            return civicrm_api3_create_error("Bruto jaarinkomen heeft ongeldige tekens");
        }
    }
    /*
     * if huishoudgrootte entered, error if invalid
     */
    if (isset($inparms['huishoudgrootte'])) {
    	$huishoudgrootte = (int) trim($inparms['huishoudgrootte']);
    	$huishoudgrootte_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('huishoudgrootte_20110111122358');
    	$huishoudgrootte_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($huishoudgrootte_group_id);
    	if (!array_key_exists($huishoudgrootte, $huishoudgrootte_options)) {
            return civicrm_api3_create_error("Huishoudgrootte is ongeldig");
    	}
    }
    /*
     * if aanbod bekend entered, explode and if any value invalid,
     * error
     */
    if (isset($inparms['aanbod_bekend'])) {
    	$aanbod_group_id = CRM_Utils_DgwApiUtils::getOptionGroupIdByTitle('bekend_met_koopaanbod_20110111122551');
    	$aanbod_options = CRM_Utils_DgwApiUtils::getOptionValuesByGroupId($aanbod_group_id);
    	$aanbod_bekend = null;
    	$teller = 0;
    	$values = explode(",", $inparms['aanbod_bekend']);
    	foreach ($values as $value) {
            if (!empty($value)) {
                if (!array_key_exists($value, $aanbod_options)) {
                    return civicrm_api3_create_error("Aanbod bekend is ongeldig");
                } else {
                    $aanbod_bekend = $aanbod_bekend.$value.CRM_Core_DAO::VALUE_SEPARATOR;
                    $teller ++;
                }
            }
    	}
    	if ($teller > 0) {
            $aanbod_bekend = $aanbod_bekend.CRM_Core_DAO::VALUE_SEPARATOR;
    	}
    }
    /*
     * if particulier entered, only  0 or 1 are allowed
     */
    if (isset($inparms['particulier'])) {
        $particulier = (int) trim($inparms['particulier']);
        if ($particulier != 0 and $particulier != 1) {
            return civicrm_api3_create_error("Particulier is ongeldig");
        }
    }
    /*
     * if woonkeusdatum is entered, format has to be valid
     */
    if (isset($inparms['woonkeusdatum'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['woonkeusdatum']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat woonkeusdatum");
        } else {
            $woonkeusdatum = $inparms['woonkeusdatum'];
        }
    }
    /*
     * If we get here, all validation has been succesful. If is_deleted is
     * 1, then contact has to be deleted
     */
    $custom_update = false;
    if (isset($inparms['is_deleted']) && $inparms['is_deleted'] == 1) {
        $civiparms = array("contact_id" => $contact_id, 'version' => 3);
        $res_del = civicrm_api('Contact', 'delete', $civiparms);
        if (civicrm_error($res_del)) {
            return civicrm_api3_create_error("Contact kon niet verwijderd worden uit CiviCRM, melding : ".$res_del['error_message']);
        }
        $outparms['is_error'] = "0";
    } else {
    	$action_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('action');
    	$entity_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity');
    	$entity_id_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('entity_id');
    	$key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
        /*
         * Contact needs to be updated in CiviCRM. Set parameters according to
         * contact_type
         *
         * Issue 149: gender_id 4 means organization has to be set with
         * persoonsnummer first and name as concat(first, middle and last) name
         */
    	$orgtopers = false;
    	$civiparms['version'] = 3;
        if (isset($gender_id) && $gender_id == 4) {
            $contact_type = "Organization";
            if (isset($pers_nr)) {
                $civiparms['custom_'.$nr_in_first_field['id']] = $pers_nr;
            }
        }
        $civiparms['contact_id'] = $contact_id;
        $civiparms['contact_type'] = $contact_type;
        switch ($contact_type) {
            case "Household":
                if (isset($inparms['name'])) {
                    $civiparms['household_name'] = trim($inparms['name']);
                }
                break;
            case "Organization":
                /*
                 * issue 149: if current contact is Organization and gender in
                 * update is not 4, then organization has been changed to person
                 * in First. In that case, delete Organization and create
                 * Individual
                 */
                if (isset($gender_id) && $gender_id != 4) {
                    $orgtopers = true;
                    $delparms['contact_id'] = $contact_id;
                    $delparms['version'] = 3;
                    $delres = civicrm_api('Contact', 'delete', $delparms);
                    /*
                     * create individual with new values
                     */
                    $addparms['contact_type'] = "Individual";
                    if (isset($inparms['first_name'])) {
                        $addparms['first_name'] = trim($inparms['first_name']);
                    }
                    if (isset($inparms['middle_name'])) {
                        $addparms['middle_name'] = trim($inparms['middle_name']);
                    }
                    if (isset($inparms['last_name'])) {
                        $addparms['last_name'] = trim($inparms['last_name']);
                    }
                    if (isset($gender_id)) {
                        $addparms['gender_id'] = $gender_id;
                    } else {
                        $addparms['gender_id'] = $default_gender_id;
                    }
                    if (isset($birth_date)) {
                        $addparms['birth_date'] = $birth_date;
                    }
                    $addparms['version'] = 3;
                    $addres = civicrm_api('Contact', 'Create', $addparms);
                    if (civicrm_error($addres)) {
                        return civicrm_api3_create_error("Onverwachte fout - persoon kon niet aangemaakt in dgwcontact_update voor organisatie naar persoon - ".$addres['error_message']);
                    } else {
                        $contact_id = $addres['contact_id'];
                        /*
                         * create record in synctable
                         */
                        $civiparms['custom_'.$action_field['id']] = "none";
                        $civiparms['custom_'.$entity_field['id']] = "contact";
                        $civiparms['custom_'.$entity_id_field['id']] = $contact_id;
                        $civiparms['custom_'.$key_first_field['id']] = $pers_nr;
                    }
                } else {
                    if (isset($inparms['name'])) {
                        $civiparms['organization_name'] = trim($inparms['name']);
                    } else {
                        /*
                         * if name is empty or not set, check first, middle and
                         * last name
                         */
                        $civiparms['organization_name'] = '';
                        if (isset($inparms['first_name']) && !empty($inparms['first_name'])) {
                            $civiparms['organization_name'] = trim($inparms['first_name']);
                        }
                        if (isset($inparms['middle_name']) && !empty($inparms['middle_name'])) {
                            $civiparms['organization_name'] .= trim($inparms['middle_name']);
                        }
                        if (isset($inparms['last_name']) && !empty($inparms['last_name'])) {
                            $civiparms['organization_name'] .= trim($inparms['last_name']);
                        }
                    }
                    if (isset($inparms['home_url'])) {
                    	$homeurl_params['version'] = 3;
                    	$homeurl_params['contact_id'] = $contact_id;
                    	$homeurl_res = civicrm_api('Website', 'get', $homeurl_params);
                    	if (!civicrm_error($homeurl_res)) {
                    		$homeurl_res = reset($homeurl_res['values']);
                    		$home_url_params = array();
                    		$home_url_params['version'] = 3;
                    		$home_url_params['contact_id'] = $contact_id;
                    		$home_url_params['website'] = $homeURL;
                    		$home_url_params['website_id'] = $homeurl_res['website_id'];
                    		civicrm_api('Website', 'Create', $home_url_params);
                    	}
                    }
                    if (isset($inparms['kvk_nummer'])) {
                        $civiparms['sic_code'] = trim($inparms['kvk_nummer']);
                    }
                }
                break;
            case "Individual":
                if (isset($inparms['first_name'])) {
                    $civiparms['first_name'] = trim($inparms['first_name']);
                }
                if (isset($inparms['middle_name'])) {
                    $civiparms['middle_name'] = trim($inparms['middle_name']);
                }
                if (isset($inparms['last_name'])) {
                    $civiparms['last_name'] = trim($inparms['last_name']);
                }
                if (isset($gender_id)) {
                    $civiparms['gender_id'] = $gender_id;
                }
                if (isset($birth_date)) {
                    $civiparms['birth_date'] = $birth_date;
                }
                break;
        }
        /*
         * retrieve additional name parts if required because core API empties
         * non-passed parts (only for individual)!
         *
         * JJ (may 2013): is this still the case?
         */
        if ($contact_type == "Individual") {
            if (!isset($civiparms['first_name']) || !isset($civiparms['middle_name']) || !isset($civiparms['last_name'])) {
            	if (!isset($civiparms['first_name'])) {
            		$civiparms['first_name'] = $check_contact['first_name'];
            	}
            	if (!isset($civiparms['middle_name'])) {
            		$civiparms['middle_name'] = $check_contact['middle_name'];
            	}
            	if (!isset($civiparms['last_name'])) {
            		$civiparms['last_name'] = $check_contact['last_name'];
            	}
            }
        }
        /*
         * issue 149: update only if not from org to per situation
         */
        if (!$orgtopers) {
            $res_contact = civicrm_api('Contact', 'Create',$civiparms);
            if (civicrm_error($res_contact)) {
                return civicrm_api3_create_error("Onverwachte fout, contact $contact_id kon niet bijgewerkt worden in CiviCRM, melding : ". $res_contact['error_message']);
            }
        }
        /*
         * update custom data for individual if any of the custom fields
         * have been entered
         */
        $bsn_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('BSN');
        $burg_staat_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Burgerlijke_staat');
        $woonkeusnr_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Inschrijfnummer_Woonkeus');
        $woonkeusdatum_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Datum_inschrijving_woonkeus');
        $woonsit_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Huidige_woonsituatie');
        $hoofdhuurder_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Hoofdhuurder');
        $anderecorp_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Welke_andere_corporatie');
        $jaarinkomen_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Bruto_jaarinkomen');
        $huishoudgrootte_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Huishoudgrootte');
        $aanbod_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Bekend_met_koopaanbod');
        $particulier_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Particuliere_markt');
        /*
         * update records in synctable for contact with persoonsnummer_first if not empty
         */
        if (!empty($pers_nr)) {
            $key_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('key_first');
            $change_date_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('change_date');
            $changeDate = date('Ymd');
            $group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Synchronisatie_First_Noa');
            $fields = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($contact_id, $group['id']);
            $fid = "";
            foreach($fields as $key => $field) {
                if ($field['entity_id'] == $contact_id  && $field['entity'] == "contact") {
                    $fid = ":".$key;
                    break;
                }
            }
            $civiparms2 = array (
                'version' => 3,
                'entity_id' => $contact_id,
                'custom_'.$persoonsnummer_first_field['id'] => $pers_nr,
                'custom_'.$key_first_field['id'].$fid => $pers_nr,
                'custom_'.$change_date_field['id'].$fid => $changeDate
        	);
            if (isset($bsn)) {
                $civiparms2['custom_'.$bsn_field['id']] = $bsn;
            }
            if (isset($burg_staat_id)) {
                $civiparms2['custom_'.$burg_staat_field['id']] = $burg_staat_id;
            }
            if (isset($inparms['woonkeusnummer'])) {
                $civiparms2['custom_'.$woonkeusnr_field['id']] = trim($inparms['woonkeusnummer']);
            }
            if (isset($woonkeusdatum)) {
                $civiparms2['custom_'.$woonkeusdatum_field['id']] = $woonkeusdatum;
            }
            if (isset($huidige_woonsit)) {
                $civiparms2['custom_'.$woonsit_field['id']] = $huidige_woonsit;
            }
            if (isset($hoofdhuurder)) {
                $civiparms2['custom_'.$hoofdhuurder_field['id']] = $hoofdhuurder;
            }
            if (isset($andere_corp)) {
                $civiparms2['custom_'.$anderecorp_field['id']] = $andere_corp;
            }
            if (isset($bruto_jaarinkomen)) {
                $civiparms2['custom_'.$jaarinkomen_field['id']] = $bruto_jaarinkomen;
            }
            if (isset($huishoudgrootte)) {
                $civiparms2['custom_'.$huishoudgrootte_field['id']] = $huishoudgrootte;
            }
            if (isset($aanbod_bekend)) {
                $civiparms2['custom_'.$aanbod_field['id']] = $aanbod_bekend;
            }
            if (isset($particulier)) {
                $civiparms2['custom_'.$particulier_field['id']] = $particulier;
            }
            $civicres2 = civicrm_api('CustomValue', 'Create', $civiparms2);
        }
    }
    unset($GLOBALS['dgw_api']);
    $outparms['is_error'] = "0";
    return $outparms;
}