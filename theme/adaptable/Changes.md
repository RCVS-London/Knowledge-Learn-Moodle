Change Log in version 3.8.1.0 (2020070104)
==========================================
1. Fix 'Quiz due date has no background colour'.

Change Log in version 2.4.4 (2020070103)
========================================
1. Fix 'Menu bar height loss' - #222.
2. Fix 'Activity meta information not showing when student first accesses page' - https://moodle.org/mod/forum/discuss.php?d=417731.
3. Fix 'Footer shown on popup layout' - https://moodle.org/mod/forum/discuss.php?d=417793.
4. Fix 'User profile image is cropped' - https://moodle.org/mod/forum/discuss.php?d=417776.
5. Fix 'Textured page background image does not extend down when the page height changes' - #223.
6. Grid format has changed method name of 'section_header_onsectionpage_topic0notattop' to 'section_header_onsectionpage'.
7. Fix 'Redirection loop' - #224.

Change Log in version 2.4.3 (2020070102)
========================================
1. Fix 'Header issues with logo and background image'.
2. Fix 'Custom menu items not shown even when "disablecustommenu" is false'.
3. Fix 'Header style two does not show logo when there is no site title'.
4. Fix 'Undefined property: stdClass::$tickertext1 when running behat tests' - #159.

Change Log in version 2.4.2 (2020070101)
========================================
1. Fix 'Cope when there is no first or full name' when showing a user profile.
2. Fix 'Frontpage tiles do not show course contacts' - #184.
3. Due date label doesn't honor overridden dates for mod_assign - #186,
   thanks to https://github.com/golenkovm for the original patch in Collapsed Topics.
4. Fix 'adaptable_setting_confightmleditor does not set setting as empty when there is no content' - #187.
5. Fix 'Sub sub menus and below show all at once' - #188.
6. Fix the ability for Behat to run without '$CFG->forced_plugin_settings' being set - dashboard.php issue only - #159.
7. Fix 'admin_setting_configselect defaults should use the index and not the value' - #189.
8. Fix 'regression - background colour on dashboard' - #190.
9. Add 'Book printing to PDF' - #173.
10. Fix 'Dashboard dropdown hover makes text unreadable' - #194.
11. Add 'Not submitted confusing when student can no longer submit' - #195.
12. Fix 'buttondropshadow does not use lang strings' - #152.
13. Port of Collapsed Topics accessible colours for the activity meta - https://github.com/gjb2048/moodle-format_topcoll/issues/88.
14. Tabbed settings and fixed use of $PAGE which gives invalid variable values when Adaptable is not the set theme.
15. Fix 'PHPUnit install fails' - #197.
16. Fix 'Install fails on Moodle 3.9' - #198 - thanks to https://gitlab.com/kiklopgs for the patch in https://gitlab.com/jezhops/moodle-theme_adaptable/-/merge_requests/34.
17. Fix 'Gradebook: Edit link not working' - #201.
18. Fix 'edit_button in renderers.php is not used' - #202.
19. Fix 'Redundant CSS' - #96.
20. Fix 'theme_adaptable_get_html_for_settings() is not used!' - #27.
21. Fix '$hasmiddle is not used!' - #26.
22. Fix '$hasfootnote is not used!' - #203.
23. Fix '$responsivealerts = $PAGE->theme->settings->responsivealerts; not used!' - #204.
24. Fix 'Improve Activity Completion Icons' - #8.
25. Fix 'User menu available when using "Full screen pop-up with some Javascript scurity" in Quiz' - #210.
26. Fix 'Adding Activity with Safari in Moodle 3.9' - #211.
27. Fix 'Second level links do not work when using 3 levels of sub-menu in custom menus' - #117.
28. Fix 'Course formatting in Safari and Moodle 3.9' - #212.
29. Fix ''About me' tab should be the default for the user profile page' - #206.
30. Add version information to settings pages.
31. Fix 'Calendar links on the page'.
32. Fix 'Navigation tweaks'.
33. Fix 'User details not visible on profile page' - #119.
34. Tabs update in line with MDL-69301.
35. Fix block header icons.
36. Fix block hide / show icon size.
37. Fix 'Wrong display of date user profile fields' - #214.
38. Fix property display can cause markup to be interpreted.
39. Allow Import / Export settings to work by separating from tabbed settings.
40. Fix 'Impossible to enter a course with Coventry tiles' - #156.
41. Fix 'stickynavbar' JS error on login page.
42. Fix 'Login page cookies popup not working' - #217.
43. Fix 'Onetopic: background color in tabs' - #215.
44. Fix 'Theme does not respect the before_footer callback' - #216.
45. Fix 'No multilang support in headers and footer' - #132.
46. Fix 'Drag&Drop Image question behaviour problem' - #220.
47. Fix 'Duplicate code in get_logo_title()' - #208.
48. Fix 'responsivealerts used?' - #205.
49. Fix 'fonttitlecolorcourse setting not used' - #154.
50. Fix 'enablealertcoursepages setting used?' - #151.

Change Log in version 2.3.2 (2019112606)
========================================
1. Fix 'Error in function quiz_num_submissions_ungraded' - #176.
2. Fix 'course_participant_count inaccurate' - #179.
3. Fix 'Lesson status inaccurate' - #180.

Change Log in 2.3.1
========================================
- Fixes in line with version 3.0.0 and 3.0.1

Change Log in 2.2.2
========================================

Main fixes & Enhancements done in this release.

- Fix mobile responsive settings in "layout responsive" settings page.
- Fix ability to set general box color in forums.
- Fix issues with login page when no header in use.
- Fix issue of footer riding up on short pages with little content.
- Fix close icon for activity chooser in Moodle 3.8.
- Fix combo list on mobile, now collapses into single column.

What's new?

- Layout responsive settings page.
- Setting to control color of forum "general box" background where forum description is displayed.
