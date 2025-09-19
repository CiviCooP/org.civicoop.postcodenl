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
        <span class="crm-button-type-done">
            <input class="validate form-submit default" name="import" value="{ts escape='htmlattribute'}Import{/ts}" type="button" onclick="pro6pp_import();">
        </span>
    </div>
</div>
</form>

{literal}
<script type="text/javascript">

function pro6pp_import() {
  var authkey = cj('#pro6pp_authkey').val();
  var skipCbsBuurten = cj('#skip_cbs').prop('checked') ? '1' : '0';
  var url = CRM.url('civicrm/admin/import/pro6pp', {"reset": 1, "run": 1, "authkey": authkey, "skipCbsBuurten": skipCbsBuurten});
  window.location.href = url;
}

</script>
{/literal}
