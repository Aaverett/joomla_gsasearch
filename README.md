joomla_gsasearch
================
Aaron Averett
Bureau of Economic Geology, The University of Texas at Austin
2013-04-05

This is a plugin for Joomla 1.5 that provides support for searching on a Google Search Appliance or Google Mini.  These instructions will assume that the user is reasonably familiar with Joomla and the administration of the search appliance.

Two plugins are provided:

gsasearch provides the core search function.  It accepts the user's input, composes a query to run on the search appliance, and parses the returned XML from the search results.

Installation:
1.  Create a .zip file containing gsasearch.xml and gsasearch.php.
2.  In the Joomla administrator console, open the extensions menu, and select "Install/Uninstall"
3.  Use install page to install the plugin by uploading the .zip file
4.  Use the administrator console to enable the plugin, which should appear as "Search - Google Search Appliance"
5.  Finally, configure the parameters of the plugin for the specific values appropriate for your search appliance



gsahttpbasic provides a means for the Google Search Appliance to construct its search index, even if the bulk of the site is protected by user access restrictions.  The search appliance should be provided with a username and password, and the plugin will allow Joomla to accept these credentials, even if they are not submitted through the standard login form.

Installation:
1.  Create a .zip file containing gsahttpbasic.xml and gsahttpbasic.php.
2.  In the Joomla administrator console, open the extensions menu, and select "Install/Uninstall"
3.  Use install page to install the plugin by uploading the .zip file
4.  Create a Joomla user account that has access to all content you wish to have the search appliance provide an index for.
5.  Within the Search Appliance's administrator control panel, navigate to "Crawl and Index" -> "Crawler Access"
6.  Enter the pattern matching the URL of the front page of your Joomla Instance, and the username and password you created in step 5.
7.  Optionally, you may wish to either reset the index on the appliance, or force the appliance to re-crawl the Joomla directory.