# New Report Engine


## Quick Overview

* Move reports to file for each report in reports/ directory
* Add mechanism to rebuild menus when reports change
* New permission role: "senior staff"
* Sending email moved to "senior staff" role
* Reports that output CSV are included in same mechanism
* Adding Data Tables support to a report greatly simplified


## Report Customization

* Each report is in a separate file in reports/ directory or a subdirectory of the reports/ directory.
* Each file specifies the $report associative array.  All data necessary to specify
the report is in this array.
* If you change just the queries or output format of a report, there is nothing additional to do.
* If you add or delete a report, rerun Build Report Menus to change the menus.
* If you change the name, description, or categories of a report, rerun Build Report Menus to change the menus.
* If you want a report not to appear under any categories, set $report\['categories'] = array();


