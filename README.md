StrigelCMS
==========

_A flexible, but still simple and easy to use Content-Management-System._


Introduction
------------

When I had the task to create a new website for my [former school][], I realized
that all the common Content-Management-Systems indeed were feature-rich and
well maintained, but not easy to use and what's more, not very customizable.
You could not just write 10 lines of PHP and include them without any hassle.

At this time I decided to write my own Content-Management-System which should be
intuitive to manage and easy to extend: StrigelCMS (SMCS)

  [former school]: http://strigel.de

Features
--------

 * Includes TinyMCE for easy creation of sites by people who don't have any HTML
   or CSS knownledge.
 * Module system to include your own scripts easily.
 * Event-base plugin system which allows you to customise SCMS to your needs
 * Templates allow you to apply your own designs.

Security
--------

_Note_: If you are using Apache, the following should already be handled by the
included `.htaccess` files.

 * You should make sure that your webserver does not execute scripts in
   `/resources/site_files`, because uploaded files are not checked.
 * Your webserver should not allow access to `/modules`, where user modules are
   stored, and to `/admin/backup`, where sql dumps are stored.

Licence
-------

StrigelCMS is licenced under GPLv3.

TinyMCE is licenced under LGPLv2.1.

The icons used in the backend are in public domain. (Thanks to the
[Tango Desktop Project][1]!)

  [1]: http://tango.freedesktop.org/
