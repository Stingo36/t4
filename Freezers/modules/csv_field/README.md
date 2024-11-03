# CSV Field

This module provides a CSV text field that displays data as a table.

The default formatter offers two options:

- Display as [Datatable](https://datatables.net/)
- Render CSV as table on the client: This option reduces bandwidth by not 
  sending the table HTML over the network.  The CSV data is 
  parsed by the [PapaParse](https://github.com/mholt/PapaParse) library.


## REQUIREMENTS

* Drupal 8 or 9

## INSTALLATION

The module can be installed via the
[standard Drupal installation process](http://drupal.org/node/895232).

## CONFIGURATION

Add the field to a fieldable entity and configure the display.
