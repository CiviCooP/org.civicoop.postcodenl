{literal}
<script type="text/javascript">

function init_postcodenl_contact_form() {
    var addressBlocks = cj('.crm-edit-address-block');
    addressBlocks.each(function(index, item) {
        var block = cj(item).attr('id').replace('Address_Block_', '');
        alert('table#address_'+block);
        postcodenl_init_addressBlock(block, 'table#address_'+block);
    });
}

function reset_postcodenl_contact_form() {
    postcodenl_reset();
}

cj(function() {
    reset_postcodenl_contact_form();
    init_postcodenl_contact_form();
});

</script>
{/literal}