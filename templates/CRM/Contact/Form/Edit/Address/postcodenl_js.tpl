{literal}
<script type="text/javascript">
function insert_row_{/literal}{$blockId}{literal}() {
    postcodenl_init_addressBlock('{/literal}{$blockId}{literal}', '#address_table_{/literal}{$blockId}{literal}');
}

cj(function(e) {
    insert_row_{/literal}{$blockId}{literal}();
});

</script>
{/literal}