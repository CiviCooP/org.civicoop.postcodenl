<form>
<div class="crm-block crm-form-block crm-import-pro6pp-form-block">
    <table class="form-layout">
        <tr>
            <td class="label">{ts}Pro6pp api authkey{/ts}</td>
            <td>
                <input name="pro6pp_authkey" id="pro6pp_authkey" type="text" class="form-text required">
            </td>
        </tr>
        <tr>
            <td class="label">{ts}Skip CBS Buurten{/ts}</td>
            <td>
                <input name="skip_cbs" id="skip_cbs" type="checkbox" value=1 class="crm-form-checkbox" >
            </td>

        </tr>
        
        <tr>
            <td class="label"></td>
            <td>
                <div class="result" id="pro6pp_import_result">
                    <p>{ts}Running the import can take a while. So please be patient and let the import job do its work{/ts}</p>
                </div> 
            </td>
        </tr>


    </table>
    <div class="crm-submit-buttons">
        <span class="crm-button crm-button-type-done">
            <input class="validate form-submit default" name="import" value="{ts}Import{/ts}" type="button" onclick="pro6pp_import();">
        </span>
    </div>
</div>
</form>

{literal}
<script type="text/javascript">

function pro6pp_import() {
    var authkey = cj('#pro6pp_authkey').val();
    cj('#pro6pp_import_result').html('<p><em>Importing....</em></p>');
    CRM.api('Pro6pp', 'import', {'authkey': authkey, 'skip_cbs': cj('#skip_cbs').prop('checked')},
        {success: function(data) {
            cj('#pro6pp_import_result').html('<p><span style="color: green; font-weight: bold;">Finished importing</span></p>');
          }
        }
      );
}

</script>
{/literal}