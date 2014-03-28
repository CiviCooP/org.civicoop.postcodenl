/* 
 * Function to retrieve the postcode and fill the fields
 */
function postcodenl_retrieve(blockId, housenumber, postcode) {

    //check if country is NL.
    if ((cj('#address_' + blockId + '_country_id').val()) != 1152) {
        return;
    }

    //run only when a postcode is present
    if (postcode.length == 0) {
        return;
    }

    CRM.api('PostcodeNL', 'get', {'sequential': 1, 'huisnummer': housenumber, 'postcode': postcode},
    {success: function(data) {
            if (data.is_error == 0 && data.count == 1) {
                var obj = data.values[0];
                cj('#address_' + blockId + '_street_name').val(obj.adres);
                cj('#address_' + blockId + '_city').val(obj.woonplaats);
                cj('#address_' + blockId + '_street_number').val(housenumber);
                cj('#address_' + blockId + '_postal_code').val(postcode);
                cj('#address_' + blockId + '_street_address').val(obj.adres + ' ' + housenumber);
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Gemeente"]').val(obj.gemeente);
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Buurt"]').val(obj.cbs_buurtnaam);


            } else if (data.is_error == 0 && data.count == 0) {
                cj('#address_' + blockId + '_street_address').val('');
                cj('#address_' + blockId + '_street_name').val('');
                cj('#address_' + blockId + '_city').val('');
                cj('#address_' + blockId + '_street_number').val(housenumber);
                cj('#address_' + blockId + '_postal_code').val(postcode);
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Gemeente"]').val();
                cj('.crm-address-custom-set-block-' + blockId + ' input[data-crm-custom="Adresgegevens:Buurt"]').val();

            }
        }
    });
}

function postcodenl_init_addressBlock(blockId, address_table_id) {
    //var first_row = cj('#address_table_'+blockId+' tbody tr:first');
    var first_row = cj(address_table_id + ' tbody tr:first');

    first_row.before('<tr class="hiddenElement postcodenl_input_row" id="postcodenl_row_' + blockId + '"><td>Postcode<br /><input class="form-text" id="postcodenl_postcode_' + blockId + '" /></td><td>Huisnummer<br /><input id="postcodenl_huisnummer_' + blockId + '" class="form-text six"></td><td></td></tr>');

    var postcode_field = cj('#postcodenl_postcode_' + blockId);
    var housenumber_field = cj('#postcodenl_huisnummer_' + blockId);

    postcode_field.change(function(e) {
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val());
    });

    housenumber_field.change(function(e) {
        postcodenl_retrieve(blockId, housenumber_field.val(), postcode_field.val());
    });

    cj('#address_' + blockId + '_country_id').change(function(e) {
        if ((cj('#address_' + blockId + '_country_id').val()) == 1152) {
            cj('#postcodenl_row_' + blockId).show();
            cj('#address_' + blockId + '_street_number').hide();
            cj('#address_' + blockId + '_postal_code').hide();
            cj('#address_' + blockId + '_street_number').prev().prev().hide();
            cj('#address_' + blockId + '_postal_code').prev().prev().hide();
        } else {
            cj('#postcodenl_row_' + blockId).hide();
            cj('#address_' + blockId + '_street_number').show();
            cj('#address_' + blockId + '_postal_code').show();
            cj('#address_' + blockId + '_street_number').prev().prev().show();
            cj('#address_' + blockId + '_postal_code').prev().prev().show();
        }
    });
    
    cj('#address_' + blockId + '_country_id').trigger('change');
}

/**
 * remove all the input elements for postcodes
 */
function postcodenl_reset() {
    cj('.postcodenl_input_row').remove();
}