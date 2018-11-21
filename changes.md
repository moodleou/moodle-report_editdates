# Change log for the Edit dates report


## Changes in 2.7

* There is now a way for other plugins to add themselves to this report
  using a class in that other plugin, rather than requring a class to be added here.
  See the readme for details.
* Fixed a problem with the CSS, where the CSS here would alter the appearance
  of forms throughout Moodle. Sorry about that.
* Update automated tests for Moodle 3.6.


## Changes in 2.6

* Privacy API implementation.
* Added support for mod_assign gradingduedate.
* Added support for third-party mod_zoom.
* Added support for course end times, and cours start time as
  well as date (matching MDL-61205 and MDL-43648).
* Setup Travis-CI automated testing integration.
* Fix some automated tests to pass with newer versions of Moodle.
* Fix some coding style.
* Due to privacy API support, this version now only works in Moodle 3.4+
  For older Moodles, you will need to use a previous version of this plugin.


## 2.5 and before

Changes were not documented here.
