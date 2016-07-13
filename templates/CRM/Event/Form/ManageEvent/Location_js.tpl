{literal}
<script type="text/javascript">

function init_postcodenl_manageevent_form() {
    var addressBlocks = cj('.crm-event-manage-location-form-block #newLocation > div');
    addressBlocks.each(function(index, item) {
        if (cj(item).attr('id')) {
            var block = cj(item).attr('id').replace('Address_Block_', '');
            if (cj('table#address_'+block).length > 0) {
                postcodenl_init_addressBlock(block, 'table#address_'+block);
            } else {
                postcodenl_init_addressBlock(block, 'table#address_table_'+block);
            }
        }
    });
}

function reset_postcodenl_manageevent_form() {
    postcodenl_reset();
}

function init_location_block_change() {
    if (!cj('#loc_event_id')) {
        return;
    }

    cj('#loc_event_id').change(function() {
        retrieve_location_block();
    });

    retrieve_location_block();
}

function retrieve_location_block() {
    if (!cj('#CIVICRM_QFID_2_location_option').is(":checked")) {
        return;
    }
    if (cj('#loc_event_id').val()) {
        CRM.api('LocBlock', 'getvalue', {'return': 'address_id', 'id': cj('#loc_event_id').val()},
        {
            success: function (data) {
                CRM.api('Address', 'getsingle', {'id': data.result},
                {
                    success: function (address) {
                        processAddressFields()

                        cj('#postcodenl_huisnummer_1').val(address.street_number);
                        cj('#address_1_street_number').val(address.street_number);
                        cj('#postcodenl_huisnummer_toev_1').val(address.street_unit);
                        cj('#address_1_street_unit').val(address.street_unit);
                        cj('#address_1_street_name').val(address.street_name);
                        cj('#address_1_street_address').val(address.street_address);
                    }
                });
            }
        });
    }
}

cj(function() {
    reset_postcodenl_manageevent_form();
    init_postcodenl_manageevent_form();
    init_location_block_change();

    {/literal}{if !$allAddressFieldValues}{literal}
    if (cj('#CIVICRM_QFID_1_location_option').is(":checked")) {
        cj('#postcodenl_postcode_1').focus();
    }
    {/literal}{/if}{literal}
});

</script>
{/literal}