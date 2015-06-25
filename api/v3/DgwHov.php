<?php

/*
+--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCoop Academic Free License v3.02013                  |
+--------------------------------------------------------------------+
*/

function civicrm_api3_dgw_hov_create( $inparms ) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    $outparms['is_error'] = '1';
    /*
     * if no hov_nummer passed, error
     */
    if ( !isset( $inparms['hov_nummer'] ) ) {
        return civicrm_api3_create_error( "Hov_nummer ontbreekt" );
    } else {
        $hov_nummer = trim( $inparms['hov_nummer'] );
    }
    /*
     * Corr_name can not be empty
     */
    if ( !isset($inparms['corr_name'] ) || empty( $inparms['corr_name'] ) ) {
        return civicrm_api3_create_error( "Corr_name ontbreekt" );
    } else {
        $corr_name = trim( $inparms['corr_name'] );
    }
    /*
     * if no hh_persoon passed and no mh_persoon passed, error
     */
    if ( !isset( $inparms['hh_persoon'] ) && !isset( $inparms['mh_persoon'] ) ) {
        return civicrm_api3_create_error( "Hoofdhuurder of medehuurder ontbreekt" );
    } else {
        if ( isset( $inparms['hh_persoon'] ) ) {
            $hh_persoon = trim( $inparms['hh_persoon'] );
        } else {
            $hh_persoon = null;
        }
        if (isset( $inparms['mh_persoon'] ) ) {
            $mh_persoon = trim( $inparms['mh_persoon'] );
        } else {
            $mh_persoon = null;
        }
    }
    /*
     * if hh_persoon and mh_persoon empty, error
     */
    if ( empty( $hh_persoon ) && empty( $mh_persoon ) ) {
        return civicrm_api3_create_error( "Hoofdhuurder en medehuurder ontbreken beide" );
    }
    $persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'persoonsnummer_first' );
    /*
     * if hh_persoon not found in CiviCRM, error
     */
    $hh_type = null;
    if ( !empty( $hh_persoon ) ) {
        $hhparms = array( "custom_".$persoonsnummer_first_field['id'] => $hh_persoon );
        $hhparms['version'] = 3;
        $res_hh = civicrm_api( 'Contact', 'getsingle', $hhparms );
        if ( isset( $res_hh['count'] ) ) {
            if ( $res_hh['count'] == 0 ) {
                $persoonsnummer_org_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'nr_in_first' );
                $hhparms = array(
                    'version'                                   =>  3,
                    'custom_'.$persoonsnummer_org_field['id']   =>  $hh_persoon
                );
                $res_hh = civicrm_api( 'Contact', 'getsingle', $hhparms );
            }
        }
        if ( isset( $res_hh['contact_id'] ) ) {
            $hh_id = $res_hh['contact_id'];
            if ( isset( $res_hh['contact_type'] ) ) {
                $hh_type = strtolower( $res_hh['contact_type']);
            }
        } else {
            if ( isset( $res_hh['error_message'] ) ) {
                $returnMessage = "Contact niet gevonden, foutmelding van API Contact Getsingle : ".$res_hh['error_message'];
            } else {
                $returnMessage = "Contact niet gevonden";
            }
            return civicrm_api3_create_error( $returnMessage );
        }
    }
    /*
     * if mh_persoon not found in CiviCRM, error
     */
    if ( !empty( $mh_persoon ) ) {
        $mhparms = array( "custom_".$persoonsnummer_first_field['id'] => $mh_persoon );
        $mhparms['version'] = 3;
        $res_mh = civicrm_api( 'Contact', 'getsingle', $mhparms );
        if ( isset( $res_mh['count'] ) ) {
            if ( $res_mh['count'] == 0 ) {
                $persoonsnummer_org_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'nr_in_first' );
                $mhparms = array(
                    'version'                                   =>  3,
                    'custom_'.$persoonsnummer_org_field['id']   =>  $mh_persoon
                );
                $res_mh = civicrm_api( 'Contact', 'getsingle', $mhparms );
            }
        }
        if ( isset( $res_mh['contact_id'] ) ) {
            $mh_id = $res_mh['contact_id'];
        } else {
            if ( isset( $res_mh['error_message'] ) ) {
                $returnMessage = "Contact niet gevonden, foutmelding van API Contact Getsingle : ".$res_mh['error_message'];
            } else {
                $returnMessage = "Contact niet gevonden";
            }
            return civicrm_api3_create_error( $returnMessage );
        }
    }
    /*
     * if start_date passed and format invalid, error
     */
    if ( isset( $inparms['start_date'] ) && !empty( $inparms['start_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat( $inparms['start_date'] );
        if ( !$valid_date ) {
            return civicrm_api3_create_error( "Onjuiste formaat start_date" );
        } else {
            $start_date = date( "Ymd", strtotime( $inparms['start_date'] ) );
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if ( isset( $inparms['end_date'] ) && !empty( $inparms['end_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat( $inparms['end_date'] );
        if ( !$valid_date ) {
            return civicrm_api3_create_error( "Onjuiste formaat end_date" );
        } else {
            $end_date = date( "Ymd", strtotime( $inparms['end_date'] ) );
        }
    }
    /*
     * if hh_start_date passed and format invalid, error
     */
    if ( isset( $inparms['hh_start_date'] ) && !empty( $inparms['hh_start_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat( $inparms['hh_start_date'] );
        if ( !$valid_date ) {
            return civicrm_api3_create_error( "Onjuiste formaat hh_start_date" );
        } else {
            $hh_start_date = date( "Ymd", strtotime( $inparms['hh_start_date'] ) );
        }
    }
    /*
     * if hh_end_date passed and format invalid, error
     */
    if ( isset( $inparms['hh_end_date'] ) && !empty( $inparms['hh_end_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat( $inparms['hh_end_date'] );
        if ( !$valid_date ) {
            return civicrm_api3_create_error( "Onjuiste formaat hh_end_date" );
        } else {
            $hh_end_date = date( "Ymd", strtotime( $inparms['hh_end_date'] ) );
        }
    }
    /*
     * if mh_start_date passed and format invalid, error
     */
    if ( isset( $inparms['mh_start_date'] ) && !empty( $inparms['mh_start_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat( $inparms['mh_start_date'] );
        if ( !$valid_date ) {
            return civicrm_api3_create_error( "Onjuiste formaat mh_start_date" );
        } else {
            $mh_start_date = date( "Ymd", strtotime( $inparms['mh_start_date'] ) );
        }
    }
    /*
     * if mh_end_date passed and format invalid, error
     */
    if ( isset( $inparms['mh_end_date'] ) && !empty( $inparms['mh_end_date'] ) ) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat( $inparms['mh_end_date'] );
        if ( !$valid_date ) {
            return civicrm_api3_create_error( "Onjuiste formaat mh_end_date" );
        } else {
            $mh_end_date = date( "Ymd", strtotime( $inparms['mh_end_date'] ) );
        }
    }
    $hov_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName( 'Huurovereenkomst (huishouden)' );
    if ( !is_array( $hov_group ) ) {
        return civicrm_api3_create_error( "CustomGroup Huurovereenkomst niet gevonden" );
    }
    $hov_group_id = $hov_group['id'];
    $hov_group_table = $hov_group['table_name'];
    $hov_group_org = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst (organisatie)');
    if ( !is_array( $hov_group_org ) ) {
        return civicrm_api3_create_error( "CustomGroup Huurovereenkomst Org niet gevonden" );
    }
    $hov_group_org_id = $hov_group_org['id'];
    /*
     * Validation passed, processing depends on contact type (issue 240)
     * Huurovereenkomst can be for individual or organization
     */
    if ( $hh_type == "organization" ) {
        $customparms['version'] = 3;
        $customparms['entity_id'] = $hh_id;
        /*
         * check if huurovereenkomst already exists
         */
        $values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted( $hh_id, $hov_group_org_id );
        $key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
        foreach( $values as $id => $field ) {
            if ( $field['hov_nummer'] == $hov_nummer) {
                $key = ':'.$id;
                break; //stop loop
            }
        }
        $hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'hov_nummer' );
        $begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'begindatum_overeenkomst' );
        $einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'einddatum_overeenkomst' );
        $vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'vge_nummer' );
        $vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'vge_adres' );
        $naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'naam_op_overeenkomst' );
        $customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
        if (isset( $start_date ) && !empty( $start_date ) ) {
            $customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
        }
        if ( isset( $end_date ) && !empty( $end_date ) ) {
            $customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
        }
        if ( isset( $inparms['vge_nummer'] ) ) {
            $customparms['custom_'.$vge_nummer_field['id'].$key] = trim( $inparms['vge_nummer'] );
        }
        if ( isset( $inparms['vge_adres'] ) ) {
            $customparms['custom_'.$vge_adres_field['id'].$key] = trim( $inparms['vge_adres'] );
        }
        if ( isset( $inparms['corr_name'] ) ) {
            $customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim( $inparms['corr_name'] );
        }
        $res_custom = civicrm_api( 'CustomValue', 'Create', $customparms );
        if ( civicrm_error( $res_custom ) ) {
            return civicrm_api3_create_error( $res_custom['error_message'] );
        }
        $outparms['is_error'] = 0;
    /*
     * if type = individual
     */
    } else {
        $huishouden_id = 0;
        /*
         * huurovereenkomst for individual (household), first check if HOV exists
         */
        require_once 'CRM/Utils/DgwUtils.php';
        $huurOvereenkomst = CRM_Utils_DgwUtils::getHuurovereenkomstHuishouden( $hov_nummer );
        if ( empty( $huurOvereenkomst ) ) {
            /*
             * create huishouden for new huurovereenkomst
             */
            $hh_parms = array(
                'version'           =>  3,
                'contact_type'      =>  'Household',
                'household_name'    =>  $inparms['corr_name']
            );
            $hh_res = civicrm_api( 'Contact', 'Create', $hh_parms );
            if ( isset( $hh_res['id'] ) ) {
                $huishouden_id = (int) $hh_res['id'];
                $key = "";
                /*
                 * for both persons, check if a relation hoofdhuurder to household is
                 * present. If so, update with incoming dates. If not so, create.
                 */
                if (isset($hh_persoon)) {
                    $rel_med_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Medehuurder');
                    $rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
                    $parms = array(
                        'version' => 3,
                        'relationship_type_id' => $rel_med_id,
                        'contact_id_a' => $huishouden_id,
                    );
                    $res = civicrm_api('Relationship', 'get', $parms);
                    $updated = false;
                    if (!civicrm_error($res)) {
                        foreach($res['values'] as $rid => $value) {
                            $rel_params['version'] = 3;
                            $rel_params['id'] = $rid;
                            $rel_params['relationship_type_id'] = $rel_hfd_id;
                            if (isset($hh_start_date) && !empty($hh_start_date)) {
                                $rel_params['start_date'] = $hh_start_date;
                            }
                            if (isset($hh_end_date) && !empty($hh_end_date)) {
                                $rel_params['end_date'] = $hh_end_date;
                            }
                            civicrm_api('Relationship', 'Create', $rel_params);
                            $updated = true;
                        }
                    }
                    if (!$updated) {
                        $rel_params['version'] = 3;
                        $rel_params['contact_id_a'] = $hh_id;
                        $rel_params['contact_id_b'] = $huishouden_id;
                        $rel_params['is_active'] = 1;
                        $rel_params['relationship_type_id'] = $rel_hfd_id;
                        if (isset($hh_start_date) && !empty($hh_start_date)) {
                            $rel_params['start_date'] = $hh_start_date;
                        }
                        if (isset($hh_end_date) && !empty($hh_end_date)) {
                            $rel_params['end_date'] = $hh_end_date;
                        }
                        civicrm_api('Relationship', 'Create', $rel_params);
                    }
                }
                if (isset($mh_persoon) && !empty($mh_persoon)) {
                    $rel_med_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Medehuurder');
                    $rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
                    $parms = array(
                        'version' => 3,
                        'relationship_type_id' => $rel_med_id,
                        'contact_id_a' => $mh_id,
                    );
                    $res = civicrm_api('Relationship', 'get', $parms);
                    $updated = false;
                    if (!civicrm_error($res)) {
                        foreach($res['values'] as $rid => $value) {
                            $rel_params['version'] = 3;
                            $rel_params['id'] = $rid;
                            if (isset($mh_start_date) && !empty($mh_start_date)) {
                                $rel_params['start_date'] = $mh_start_date;
                            }
                            if (isset($mh_end_date) && !empty($mh_end_date)) {
                                $rel_params['end_date'] = $mh_end_date;
                            }
                        civicrm_api('Relationship', 'Create', $rel_params);
                        $updated = true;
                        }
                    }
                    if (!$updated) {
                        $rel_params['version'] = 3;
                        $rel_params['contact_id_a'] = $mh_id;
                        $rel_params['contact_id_b'] = $huishouden_id;
                        $rel_params['is_active'] = 1;
                        $rel_params['relationship_type_id'] = $rel_med_id;
                        if (isset($mh_start_date) && !empty($mh_start_date)) {
                            $rel_params['start_date'] = $mh_start_date;
                        }
                        if (isset($mh_end_date) && !empty($mh_end_date)) {
                            $rel_params['end_date'] = $mh_end_date;
                        }
                        civicrm_api('Relationship', 'Create', $rel_params);
                    }
                }
                /*
                 * copy address, email and phone from hoofdhuurder
                 */
                CRM_Utils_DgwUtils::processAddressesHoofdHuurder( $hh_id );
                CRM_Utils_DgwUtils::processEmailsHoofdHuurder( $hh_id );
                CRM_Utils_DgwUtils::processPhonesHoofdHuurder( $hh_id );
            } else {
                $returnMsg = "Onverwachte fout: huishouden niet aangemaakt in CiviCRM";
                if ( isset( $hh_res['error_message'] ) ) {
                    $returnMsg .= " , melding vanuit CiviCRM API : {$hh_res['error_message']}";
                }
                return civicrm_api3_create_error( $returnMsg );
            }

        } else {
            $huishouden_id = $huurOvereenkomst->entity_id;
            $key = ":".$huurOvereenkomst->id;
        }
        $hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('HOV_nummer_First');
        $begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Begindatum_HOV');
        $einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Einddatum_HOV');
        $vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_nummer_First');
        $vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_adres_First');
        $naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Correspondentienaam_First');
        /*
         * huurovereenkomst aanmaken
         */
        $customparms['version'] = 3;
        $customparms['entity_id'] = $huishouden_id;
        $customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
        if (isset($start_date) && !empty($start_date)) {
            $customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
        }
        if (isset($end_date) && !empty($end_date)) {
            $customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
        }
        if (isset($inparms['vge_nummer'])) {
            $customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
        }
        if (isset($inparms['vge_adres'])) {
            $customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
        }
        if (isset($inparms['corr_name'])) {
            $customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
        }
        $res_custom = civicrm_api('CustomValue', 'Create', $customparms);
        if (civicrm_error($res_custom)) {
            return civicrm_api3_create_error($res_custom['error_message']);
        }
        $outparms['is_error'] = 0;
        //update correspondentie naam bij huishouden
        if (isset($inparms['corr_name'])) {
            $cor_parms['version'] = 3;
            $cor_parms['name'] = trim($inparms['corr_name']);
            $cor_parms['contact_id'] = $huishouden_id;
            $res_cor = civicrm_api('Contact', 'Create', $cor_parms);
        }
    }
    unset($GLOBALS['dgw_api']);
    return $outparms;
}

/*
 * Function to update huurovereenkomst
 */
function civicrm_api3_dgw_hov_update($inparms) {
    /*
     * set superglobal to avoid double update via post or pre hook
     */
    $GLOBALS['dgw_api'] = "nosync";
    /*
     * if no hov_nummer passed, error
     */
    if (!isset($inparms['hov_nummer'])) {
        return civicrm_api3_create_error("Hov_nummer ontbreekt");
    } else {
        $hov_nummer = trim($inparms['hov_nummer']);
    }
    /*
     * if hov not found in CiviCRM, error (issue 240 check for
     * household table and organization table
     */
    $type = null;
    $org_id = null;
    $huis_id = CRM_Utils_DgwApiUtils::getHovFromTable($hov_nummer, 'HOV_nummer_First');
    $type = "huishouden";
    if (!$huis_id) {
        $org_id = CRM_Utils_DgwApiUtils::getHovFromTable($hov_nummer, 'hov_nummer');
        $type = "organisatie";
        if (!$org_id) {
            return civicrm_api3_create_error("Huurovereenkomst niet gevonden");
        }
    }
    $persoonsnummer_first_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('persoonsnummer_first');
    /*
     * if hh_persoon passed and not found in CiviCRM, error
     * issue 240: or if type = organization
     */
    if (isset($inparms['hh_persoon']) && !empty($inparms['hh_persoon'])) {
        $hh_persoon = trim($inparms['hh_persoon']);
        $hhparms = array("custom_".$persoonsnummer_first_field['id'] => $hh_persoon);
        $hhparms['version'] = 3;
        $res_hh = civicrm_api('Contact', 'getsingle', $hhparms);
        if ( isset( $res_hh['count'] ) ) {
            if ( $res_hh['count'] == 0 ) {
                $persoonsnummer_org_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName( 'nr_in_first' );
                $hhparms = array(
                    'version'                                   =>  3,
                    'custom_'.$persoonsnummer_org_field['id']   =>  $hh_persoon
                );
                $res_hh = civicrm_api('Contact', 'getsingle', $hhparms );
            }
        }
        if ( isset( $res_hh['contact_id'] ) ) {
            $hh_id = $res_hh['contact_id'];
        } else {
            if ( isset( $res_hh['error_message'] ) ) {
                $returnMessage = "Contact niet gevonden, foutmelding van API Contact Getsingle : ".$res_hh['error_message'];
            } else {
                $returnMessage = "Contact niet gevonden";
            }
            return civicrm_api3_create_error( $returnMessage );
        }
    }
    /*
     * if mh_persoon passed and not found in CiviCRM, error (also check
     * new type for issue 240)
     */
    if (isset($inparms['mh_persoon']) && !empty($inparms['mh_persoon'])) {
        if ($type == "organization") {
            return civicrm_api3_create_error("Medehuurder kan niet opgegeven worden bij een huurovereenkomst van een organisatie");
        }
        $mh_persoon = trim($inparms['mh_persoon']);
        $mhparms = array("custom_".$persoonsnummer_first_field['id'] => $mh_persoon);
        $mhparms['version'] = 3;
        $res_mh = civicrm_api('Contact', 'get', $mhparms);
        if (civicrm_error($res_mh)) {
            return civicrm_api3_create_error("Medehuurder niet gevonden");
        }
        $res_mh = reset($res_mh['values']);
        $mh_id = $res_mh['contact_id'];
    }
    /*
     * if start_date passed and format invalid, error
     */
    if (isset($inparms['start_date']) && !empty($inparms['start_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['start_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat start_date");
        } else {
            $start_date = date("Ymd", strtotime($inparms['start_date']));
        }
    }
    /*
     * if end_date passed and format invalid, error
     */
    if (isset($inparms['end_date']) && !empty($inparms['end_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['end_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat end_date");
        } else {
            $end_date = date("Ymd", strtotime($inparms['end_date']));
        }
    }
    /*
     * if hh_start_date passed and format invalid, error
     */
    if (isset($inparms['hh_start_date']) && !empty($inparms['hh_start_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['hh_start_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat hh_start_date");
        } else {
            $hh_start_date = date("Ymd", strtotime($inparms['hh_start_date']));
        }
    }
    /*
     * if hh_end_date passed and format invalid, error
     */
    if (isset($inparms['hh_end_date']) && !empty($inparms['hh_end_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['hh_end_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat hh_end_date");
        } else {
            $hh_end_date = date("Ymd", strtotime($inparms['hh_end_date']));
        }
    }
    /*
     * if mh_start_date passed and format invalid, error
     */
    if (isset($inparms['mh_start_date']) && !empty($inparms['mh_start_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['mh_start_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat mh_start_date");
        } else {
            $mh_start_date = date("Ymd", strtotime($inparms['mh_start_date']));
        }
    }
    /*
     * if mh_end_date passed and format invalid, error
     */
    if (isset($inparms['mh_end_date']) && !empty($inparms['mh_end_date'])) {
        $valid_date = CRM_Utils_DgwUtils::checkDateFormat($inparms['mh_end_date']);
        if (!$valid_date) {
            return civicrm_api3_create_error("Onjuiste formaat mh_end_date");
        } else {
            $mh_end_date = date("Ymd", strtotime($inparms['mh_end_date']));
        }
    }
    $hov_group = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst (huishouden)');
    if (!is_array($hov_group)) {
        return civicrm_api3_create_error("CustomGroup Huurovereenkomst niet gevonden");
    }
    $hov_group_id = $hov_group['id'];
    $hov_group_org = CRM_Utils_DgwApiUtils::retrieveCustomGroupByName('Huurovereenkomst (organisatie)');
    if (!is_array($hov_group_org)) {
        return civicrm_api3_create_error("CustomGroup Huurovereenkomst Org niet gevonden");
    }
    $hov_group_org_id = $hov_group_org['id'];
    /*
     * Validation passed, process depending on type (issue 240)
     */
    if ($type == "organisatie") {
        /*
         * organization: update fields if passed in parms (issue 240)
         */
        $customparms['version'] = 3;
        $customparms['entity_id'] = $org_id;
        /*
         * check if huurovereenkomst already exists
         */
        $values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($org_id, $hov_group_org_id);
        $key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
        foreach($values as $id => $field) {
            if ($field['hov_nummer'] == $hov_nummer) {
                $key = ':'.$id;
                break; //stop loop
            }
        }
        $hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('hov_nummer');
        $begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('begindatum_overeenkomst');
        $einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('einddatum_overeenkomst');
        $vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('vge_nummer');
        $vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('vge_adres');
        $naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('naam_op_overeenkomst');
        $customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
        if (isset($start_date) && !empty($start_date)) {
            $customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
        }
        if (isset($end_date) && !empty($end_date)) {
            $customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
        }
        if (isset($inparms['vge_nummer'])) {
            $customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
        }
        if (isset($inparms['vge_adres'])) {
            $customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
        }
        if (isset($inparms['corr_name'])) {
            $customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
        }
        $res_custom = civicrm_api('CustomValue', 'Create', $customparms);
        if (civicrm_error($res_custom)) {
            return civicrm_api3_create_error($res_custom['error_message']);
        }
            $outparms['is_error'] = 0;
    } else {
        /*
         * individual/household
         */
        $values = CRM_Utils_DgwApiUtils::retrieveCustomValuesForContactAndCustomGroupSorted($huis_id, $hov_group_id);
        $key = ""; //update alle records van een huurovereenkomt, als leeg nieuwe invoegen
        foreach($values as $id => $field) {
            if ($field['HOV_nummer_First'] == $hov_nummer) {
                $key = ':'.$id;
                break; //stop loop
            }
        }
        $hov_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('HOV_nummer_First');
        $begindatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Begindatum_HOV');
        $einddatum_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Einddatum_HOV');
        $vge_nummer_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_nummer_First');
        $vge_adres_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('VGE_adres_First');
        $naam_op_overeenkomst_field = CRM_Utils_DgwApiUtils::retrieveCustomFieldByName('Correspondentienaam_First');
        /*
         * huurovereenkomst aanmaken
         */
        $customparms['version'] = 3;
        $customparms['entity_id'] = $huis_id;
        $customparms['custom_'.$hov_nummer_field['id'].$key] = $hov_nummer;
        if (isset($start_date) && !empty($start_date)) {
            $customparms['custom_'.$begindatum_overeenkomst_field['id'].$key] = $start_date;
        }
        if (isset($end_date) && !empty($end_date)) {
            $customparms['custom_'.$einddatum_overeenkomst_field['id'].$key] = $end_date;
        }
        if (isset($inparms['vge_nummer'])) {
            $customparms['custom_'.$vge_nummer_field['id'].$key] = trim($inparms['vge_nummer']);
        }
        if (isset($inparms['vge_adres'])) {
            $customparms['custom_'.$vge_adres_field['id'].$key] = trim($inparms['vge_adres']);
        }
        if (isset($inparms['corr_name'])) {
            $customparms['custom_'.$naam_op_overeenkomst_field['id'].$key] = trim($inparms['corr_name']);
        }
        $res_custom = civicrm_api('CustomValue', 'Create', $customparms);
        if (civicrm_error($res_custom)) {
            return civicrm_api3_create_error($res_custom['error_message']);
        }
        $outparms['is_error'] = 0;
        //update correspondentie naam bij huishouden
        if (isset($inparms['corr_name'])) {
            $cor_parms['version'] = 3;
            $cor_parms['household_name'] = trim($inparms['corr_name']);
            $cor_parms['contact_id'] = $huis_id;
            $res_cor = civicrm_api('Contact', 'Create', $cor_parms);
        }
        /*
         * if hh_persoon passed or end_date set, check if relation hoofdhuurder or
         * medehuurder already exists between persoon and huishouden.
         */
        if ( isset( $hh_persoon ) || isset( $end_date) ) {
            $rel_hfd_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Hoofdhuurder');
            $parms = array(
                'version' => 3,
                'relationship_type_id' => $rel_hfd_id,
                'contact_id_b' => $huis_id,
            );
            $res = civicrm_api3('Relationship', 'Get', $parms);
            $updated = false;
            if (!civicrm_error($res)) {
                if (isset($hh_start_date) && !empty($hh_start_date)) {
                    $rel_params['start_date'] = $hh_start_date;
                }
                if (isset($hh_end_date) && !empty($hh_end_date)) {
                    $rel_params['end_date'] = $hh_end_date;
                }
                /*
                 * if none exist yet, create
                 */
                $rel_params['contact_id_a'] = $hh_id;
                if (isset($res['count']) && $res['count'] == 0) {
                    $rel_params['relationship_type_id'] = $rel_hfd_id;
                    $rel_params['contact_id_b'] = $huis_id;
                    $createRel = civicrm_api3('Relationship', 'Create', $rel_params);
                } else {
                    foreach($res['values'] as $rid => $value) {
                        $rel_params['id'] = $rid;
                        civicrm_api3('Relationship', 'Create', $rel_params);
                        $updated = true;
                    }
                }
            }
        }
        /*
         * if mh_persoon passed, check if relation hoofdhuurder or medehuurder
         * already exists between persoon and huishouden.
         */
        if (isset($mh_persoon)) {
            $rel_med_id = CRM_Utils_DgwApiUtils::retrieveRelationshipTypeIdByNameAB('Medehuurder');
            $parms = array(
                'version' => 3,
                'relationship_type_id' => $rel_med_id,
                'contact_id_a' => $mh_persoon,
            );
            $res = civicrm_api('Relationship', 'get', $parms);
            $updated = false;
            if (!civicrm_error($res)) {
                $rel_params['version'] = 3;
                if (isset($mh_start_date) && !empty($mh_start_date)) {
                    $rel_params['start_date'] = $mh_start_date;
                }
                if (isset($mh_end_date) && !empty($mh_end_date)) {
                    $rel_params['end_date'] = $mh_end_date;
                }
                /*
                 * if none exist yet, create
                 */
                $rel_params['contact_id_a'] = $mh_id;
                if (isset($res['count']) && $res['count'] == 0) {
                    $rel_params['relationship_type_id'] = $rel_med_id;
                    $rel_params['contact_id_b'] = $huis_id;
                    $createRel = civicrm_api('Relationship', 'Create', $rel_params);
                } else {
                    foreach($res['values'] as $rid => $value) {
                        $rel_params['id'] = $rid;
                        civicrm_api('Relationship', 'Create', $rel_params);
                        $updated = true;
                    }
                }
            }
        }
    }
    $outparms['is_error'] = "0";
    unset($GLOBALS['dgw_api']);
    return $outparms;
}