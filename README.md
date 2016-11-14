# StrigelCMS

A flexible, but still simple and easy to use Content-Management-System
originally created to replace the old website at my former school,
the [Bernhard Strigel Gymnasium][bsg].

 * Includes TinyMCE for easy creation of sites by people who don't have any HTML
   or CSS knowledge
 * Templates allow you to apply your own designs
 * Module system to include your own scripts easily
 * Event-base plugin system which allows you to customise SCMS to your needs

## Usage

You need at least PHP 5.6, the mysqli extension and a recent version of MySQL
or MariaDB. Then clone or download the repository into your webroot and follow
the instructions of the installation wizard.

## Security

_Note_: If you are using Apache, the following should already be handled by the
included `.htaccess` files.

 * You should make sure that your webserver does not execute scripts in
   `/resources/site_files`, because uploaded files are not checked.
 * Your webserver should not allow access to `/modules`, where user modules are
   stored, and to `/admin/backup`, where sql dumps are stored.

## Development

You can use the webserver built into PHP for development:

```sh
php -S localhost:8080
```

## Licence

StrigelCMS is licenced under GPLv3.

TinyMCE is licenced under LGPLv2.1.

The icons used in the backend are in public domain. (Thanks to the
[Tango Desktop Project][tango]!)

[bsg]: http://bsg-mm.de/
[tango]: http://tango.freedesktop.org/
