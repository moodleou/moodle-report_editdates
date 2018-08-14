Edit dates report
https://moodle.org/plugins/report_editdates

This 'report' is actually a tool that lets you edit all the dates for all
the activities in your course on a single page.

You can install it from the Moodle plugins database using the link above.

Alternatively, you can install it using git. In the top-level folder of your
Moodle install, type the command:
    git clone git://github.com/moodleou/moodle-report_editdates.git report/editdates
    echo '/report/editdates/' >> .git/info/exclude

Then visit the admin screen to allow the install to complete.

Once the plugin is installed, you can access the functionality by going to
Reports -> Dates in the Course administration block.
