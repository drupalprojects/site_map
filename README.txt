Drupal site map module:
----------------------
Original author - Nic Ivy http://njivy.org
Author - Fredrik Jonsson fredrik at combonet dot se
Requires - Drupal 4.6
License - GPL (see LICENSE)


Overview:
--------
Provides a site map, a page that can act as a map page that can act as a
map of links to the site. Administrators choose what should be displays,
the options are as follows.

* A message to be displayed at the top of the page
* The latest blogs.
* All books, only title.
* Any menu, including the navigation menu.
  It will be displayd fully expanded.
* Any categories, i.e vocabalary, with all the terms expanded.
  Optionally with node counts and RSS feeds.

Support for menus was contributed by keve. Blogs, books and layout
changes by frjo. The rest is by the original author.

Installation:
------------
1. Copy the site_map directory to the Drupal modules/ directory.
2. Go to "administer" -> "modules" and enable the module.
3. Check the "administer" -> "access control" page to enable use of
   this module to different roles.
4. Make sure the menu item is enabled in "administer" -> "menus".
5. Visit http://example.com/sitemap.


Last updated:
------------
$Id$