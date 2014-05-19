{literal}
<script type="text/javascript">

function init_postcodenl_manageevent_form() {
    var addressBlocks = cj('.crm-event-manage-location-form-block #newLocation > div');
    addressBlocks.each(function(index, item) {
        if (cj(item).attr('id')) {
            var block = cj(item).attr('id').replace('Address_Block_', '');
            postcodenl_init_addressBlock(block, 'table#address_'+block);
        }
    });
}

function reset_postcodenl_manageevent_form() {
    postcodenl_reset();
}

cj(function() {
    reset_postcodenl_manageevent_form();
    init_postcodenl_manageevent_form();
});

</script>
{/literal}