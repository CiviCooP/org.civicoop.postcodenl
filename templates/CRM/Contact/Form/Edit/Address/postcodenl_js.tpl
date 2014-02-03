{literal}
<script type="text/javascript">

cj('#address_{/literal}{$blockId}{literal}_street_number').change(function(e) {
    retrievePostcodeNL_{/literal}{$blockId}{literal}();
});

cj('#address_{/literal}{$blockId}{literal}_postal_code').change(function(e) {
    retrievePostcodeNL_{/literal}{$blockId}{literal}();
});

/* On tab go to postal code, only if country is NL */
cj('#address_{/literal}{$blockId}{literal}_street_number').keydown(function(e) {
    //check if country is NL.
    if ((cj('#address_{/literal}{$blockId}{literal}_country_id').val())==1152) {
        if (e.keyCode == 9) {
            e.preventDefault();
            cj('#address_{/literal}{$blockId}{literal}_postal_code').focus();
        }            
    }
    
});

function retrievePostcodeNL_{/literal}{$blockId}{literal}() {
    var country = false;
    var housenumber = false;
    var postcode = false;
    
    //check if country is NL.
    if ((cj('#address_{/literal}{$blockId}{literal}_country_id').val())==1152) {
        country = 1152;
    }

    if (country == false) {
        return;
    }

    //country is NL
    housenumber = cj('#address_{/literal}{$blockId}{literal}_street_number').val();
    postcode = cj('#address_{/literal}{$blockId}{literal}_postal_code').val();

    //run only when a postcode is present
    if (postcode.length == 0) {
        return;
    }

    CRM.api('PostcodeNL', 'get', {'sequential': 1, 'huisnummer': housenumber, 'postcode': postcode},
        {success: function(data) {
            if (data.is_error == 0 && data.count == 1) {
                var obj = data.values[0];
                cj('#address_{/literal}{$blockId}{literal}_street_name').val(obj.adres);
                cj('#address_{/literal}{$blockId}{literal}_city').val(obj.woonplaats);
            } else if (data.is_error == 0 && data.count == 0) {
                cj('#address_{/literal}{$blockId}{literal}_street_name').val('');
                cj('#address_{/literal}{$blockId}{literal}_city').val('');
            }
        }
    });
}

</script>
{/literal}