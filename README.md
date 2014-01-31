org.civicoop.postcodenl
=======================

CiviCRM extension for Dutch postcode checking

This extension uses a table with all the dutch postcodes to autocomplete the addresses on input.

This extension has also an API for retrieving the postcode by different parameters.

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
* wijk *area*
* buurt *neighbourhoud*
