/* 
 * Function to retrieve the postcode and fill the fields
 */
function postcodenl_retrieve(blockId, housenumber, postcode, toevoeging) {

    //check if country is NL.
    if ((cj('#address_' + blockId + '_country_id').val()) != 1152) {
        return;
    }

    //only when no manual processing is checked
    if (postcodenl_is_manual_processing(blockId)) {
        return;
    }

    //run only when a postcode is present
    if (postcode.length == 0) {
        return;
    }   
    
    cj('#address_' + blockId + '_street_name').addClass('ac_loading');
    cj('#address_' + blockId + '_city').addClass('ac_loading');

    CRM.api('PostcodeNL', 'get', {'sequential': 1, 'huisnummer': housenumber, 'postcode': postcode},
    {success: function(data) {
            if (data.is_error == 0 && data.count == 1) {
                var obj = data.values[0];
                cj('#address_' + blockId + '_street_name').val(obj.adres);
                cj('#address_' + blockId + '_city').val(obj.woonplaats);
                cj('#address_' + blockId + '_street_number').val(housenumber);
                cj('#address_' + blockId + '_street_unit').val(toevoeging);
                cj('#address_' + blockId + '_postal_code').val(postcode);
                cj('#address_' + blockId + '_street_address').val(obj.adres + ' ' + housenumber+toevoeging);
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Gemeente"]').val(obj.gemeente);
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Buurt"]').val(obj.cbs_buurtnaam);
                cj('#address_'+blockId+'_state_province_id option').filter(function() {
                  return cj(this).html() == obj.provincie;  
                }).prop('selected', true);

            } else if (data.is_error == 0 && data.count == 0) {
                cj('#address_' + blockId + '_street_address').val('');
                cj('#address_' + blockId + '_street_name').val('');
                cj('#address_' + blockId + '_city').val('');
                cj('#address_' + blockId + '_street_number').val(housenumber);
                cj('#address_' + blockId + '_street_unit').val(toevoeging);
                cj('#address_' + blockId + '_postal_code').val(postcode);
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Gemeente"]').val();
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Buurt"]').val();
                cj('#address_'+blockId+'_state_province_id option:selected').prop('selected', false);
            }
            
            cj('#address_' + blockId + '_street_name').removeClass('ac_loading');
            cj('#address_' + blockId + '_city').removeClass('ac_loading');
        }
    });
}

function postcodenl_init_addressBlock(blockId, address_table_id) {
    var first_row = cj(address_table_id + ' tbody tr:first');
    
    first_row.before('<tr class="hiddenElement postcodenl_input_row" id="postcodenl_row_' + blockId + '"><td>Postcode<br /><input class="form-text" id="postcodenl_postcode_' + blockId + '" /></td><td>Huisnummer<br /><input id="postcodenl_huisnummer_' + blockId + '" class="form-text six"></td><td>Toevoeging<br /><input id="postcodenl_huisnummer_toev_' + blockId + '" class="form-text six"></td></tr>');

    var postcode_field = cj('#postcodenl_postcode_' + blockId);
    var housenumber_field = cj('#postcodenl_huisnummer_' + blockId);
    var housenumber_toev_field = cj('#postcodenl_huisnummer_toev_' + blockId);
    var street_number_td = cj('#address_'+blockId+'_street_number').parent();
    var street_name_td = cj('#address_'+blockId+'_street_name').parent();
    var street_unit_td = cj('#address_'+blockId+'_street_unit').parent();
    var postalcode_td = cj('#address_'+blockId+'_postal_code').parent();

    postcode_field.change(function(e) {
        cj('#address_' + blockId + '_postal_code').val(postcode_field.val());
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val(), housenumber_toev_field.val());
    });
    
    postcode_field.keyup(function(e) {
        cj('#address_' + blockId + '_postal_code').val(postcode_field.val());
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val(), housenumber_toev_field.val());
    });

    housenumber_field.change(function(e) {
        cj('#address_' + blockId + '_street_number').val(housenumber_field.val());
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val(), housenumber_toev_field.val());
    });
    
    housenumber_field.keyup(function(e) {
        cj('#address_' + blockId + '_street_number').val(housenumber_field.val());
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val(), housenumber_toev_field.val());
    });
    
    housenumber_toev_field.change(function(e) {
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val(), housenumber_toev_field.val());
    });
    
    housenumber_toev_field.keyup(function(e) {
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val(), housenumber_toev_field.val());
    });
        
    cj('#address_' + blockId + '_country_id').change(function(e) {
        if ((cj('#address_' + blockId + '_country_id').val()) == 1152) {
            if (typeof processAddressFields == 'function' && cj('#addressElements_'+blockId).length > 0) {
                processAddressFields('addressElements', blockId, 1);
            }
            
            cj('#postcodenl_row_' + blockId).show();
                        
            street_number_td.hide();
            street_unit_td.hide();
            postalcode_td.hide();
            
            postcode_field.val(cj('#address_' + blockId + '_postal_code').val());
            housenumber_field.val(cj('#address_' + blockId + '_street_number').val());
            housenumber_toev_field.val(cj('#address_'+blockId+'_street_unit').val());

            if (postcodenl_is_manual_processing(blockId)) {
                housenumber_field.parent().hide();
                housenumber_toev_field.parent().hide();
                street_number_td.show();
                street_unit_td.show();
            } else {
                housenumber_field.parent().show();
                housenumber_toev_field.parent().show();
                street_number_td.hide();
                street_unit_td.hide();
            }

        } else {
            cj('#postcodenl_row_' + blockId).hide();
            street_number_td.show();
            street_unit_td.show();
            postalcode_td.show();
        }


    });

    cj('div.crm-address-custom-set-block-'+blockId+' input[data-crm-custom="Adresgegevens:cbs_manual_entry"]').change(function (e) {
        cj('#address_' + blockId + '_country_id').trigger('change');
    })
    
    cj('#address_' + blockId + '_country_id').trigger('change');
}

function postcodenl_is_manual_processing(blockId) {
    var manual_processing = false;
    if (cj('div.crm-address-custom-set-block-'+blockId+' input[data-crm-custom="Adresgegevens:cbs_manual_entry"]:checked')) {
        if (cj('div.crm-address-custom-set-block-'+blockId+' input[data-crm-custom="Adresgegevens:cbs_manual_entry"]:checked').val() == 1) {
            manual_processing = true;
        }
    }
    return manual_processing;
}

/**
 * remove all the input elements for postcodes
 */
function postcodenl_reset() {
    cj('.postcodenl_input_row').remove();
}


cj(function() {
    cj.each(['show', 'hide'], function (i, ev) {
        var el = cj.fn[ev];
        cj.fn[ev] = function () {
          this.trigger(ev);
          return el.apply(this, arguments);
        };
      });
});