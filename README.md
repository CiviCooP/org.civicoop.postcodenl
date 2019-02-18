org.civicoop.postcodenl
=======================

CiviCRM extension for Dutch postcode checking

This extension uses a table with all the dutch postcodes to autocomplete the addresses on input.

This extension has also an API for retrieving the postcode by different parameters.

Requirements
------------

In order to use the pro6pp database import functionality you need PHP with the Zip extension enabled. See http://www.php.net/manual/en/book.zip.php

API
---

The following api is available *PostcodeNL* *get*

You can use one the following query paramaters:
* id
* postcode
* huisnummer
* adres
* woonplaats
* gemeente

**Return value**
The api returns the following fields
* id
* postcode_nr *four digits of the postcode e.g. 6716*
* postcode_letter *the two letters of the postcode e.g. RG*
* huisnummer_van *Start of the serie of housenumbers, e.g. 1 indicates the serie start at number 1*
* huisnummer_tot *End of the serie of housenumbers, e.g. 100 indicates the serie ends at number 100*
* even *Is the housenumber serie for even numbers (1) or odd numbers (0)
* adres *Street name*
* provincie *Province*
* gemeente *municipality*
* woonplaats *city*
* wijk *area*
* buurt *neighbourhoud*

Autocomplete
------------

There is an autocomplete available. You can use the autocomplete on any field by adding the follwoing code:

    <script type="text/javascript">
    {literal}
        cj(function() {
        var gemeenteUrl = "/civicrm/ajax/postcodenl/autocomplete?reset=1&field=gemeente";
        cj('#gemeente').autocomplete( gemeenteUrl, { 
            width : 280, 
            selectFirst : true, 
            matchContains: true
        });
    });
    {/literal}
    </script>

Field to use in the autocomplete are

* adres
* woonplaats
* provincie
* gemeente
* cbs_buurtnaam

Usage
-----

This extension provides a default mechanism for the address editing forms to autocomplete addresses by querieng the API.

It is recommended to enable Address Parsing under Administer --> Localisation --> Address settings.

Other extensions provide mechanisms to fill up the database table *civicrm_postcodenl* which contains all the postcode data. The reason for seperating this is so that we can focus on a working mechanism and other extension could focus on reading a certain format of the postcode table, e.g. the information from the kadaster, *BAG* is in XML, the information from d-centralize is in csv etc...

Reuse of core templates
-----------------------

This extension reuses the core templates CRM/Contact/Form/Edit/Address/street_addresses.tpl  and CRM/Contact/Form/Edit/Address.tpl

Hooks
-----

The following hooks are available in this module
* **hook_postcodenl_get** this hook is called after the api has queried the database. A feature you could build with the hook is to use it in your own module to call a webservice from e.g. d-centralize.
