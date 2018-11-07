# PyriteView

[![license](https://img.shields.io/github/license/vphantom/pyriteview.svg?style=plastic)]() [![GitHub release](https://img.shields.io/github/release/vphantom/pyriteview.svg?style=plastic)]()

Article versioning hub for peer-reviewed publications.

This simple hub allows publication editors, single edition editors, article authors as well as third-party reviewers to come together to centralize the peer-reviewing workflow.  It was developed as a multilingual application in English and French, and 100% of linguistic content is in templates and dedicated "locale" files, so translating to new languages should be very easy.

### Why the name "Pyrite"?

"PyriteView" is a bilingual play on the words "Peer Review".  The [PyritePHP](https://github.com/vphantom/pyritephp) framework which it uses was originally created specifically for this application.

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->


- [Usage](#usage)
- [Installation](#installation)
  - [Download](#download)
  - [Requirements](#requirements)
  - [Initialization](#initialization)
  - [Configuration](#configuration)
  - [Crontab](#crontab)
  - [Web Server Configuration](#web-server-configuration)
    - [Apache](#apache)
    - [Nginx](#nginx)
- [Updating](#updating)
- [Acknowledgements](#acknowledgements)
  - [Server-side](#server-side)
  - [Client-side](#client-side)
    - [Frameworks](#frameworks)
    - [Utilities](#utilities)
  - [Build Tools](#build-tools)
- [Developers](#developers)
- [GNU Affero GPL v3.0 License](#gnu-affero-gpl-v30-license)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Usage

After installation, just point your browser to your freshly configured server and log in using the account you just created.

**Power user login:** if you want to use your password instead of e-mail validation, just hit the _TAB_ key after typing your e-mail address, which will reveal the password field.


## Installation

### Download

To get the latest stable release, download and unpack a [release archive file](https://github.com/vphantom/pyriteview/releases).

### Requirements

* PHP 5.5 or later
* PHP extension modules: mbstring, mcrypt, pdo_sqlite, readline
* SQLite 3
* Typical Linux command line tools: make, wget, gzip
* A web server of course

### Initialization

Run `make init`.  This will automatically download and set up PHP's Composer package manager, then use it to download runtime dependencies locally.  Finally, it will create the database tables and the administrative user so you can log into your new installation.  You will be prompted on the command line for an e-mail address and password to use for that unrestricted account.  (**NOTE:** This prompt requires PHP's `readline`, so *it will not work on Windows*.)

You will also need to make sure that your web server or PHP process has read-write access to the `var/` directory where the database, logs and template cache are stored.

### Configuration

Edit `var/config.ini` to change any defaults as needed.

### Crontab

The same user as your web server (in order to have access to `var/`) should trigger the 'daily' event every day and 'hourly' every hour.  For example:

```crontab
7	4	*	*	*	/usr/bin/php /web/pyriteview/index.php --trigger daily
11	*	*	*	*	/usr/bin/php /web/pyriteview/index.php --trigger hourly
```

### Web Server Configuration

In order to produce clean, technology-agnostic URLs such as `http://www.yourdomain.com/articles/127`, you need to tell your web server to internally redirect requests for non-existent files to `/index.php`, which will look in `PATH_INFO` for details.  We also want to prevent access to private files.  You should also double-check that your php.ini allows file uploads with something like this:

```
upload_max_filesize = 8M
post_max_size = 25M
```

Here are sample configurations for major server software:

#### Apache

See the included `.htaccess` file.

#### Nginx

```
http {
	...
	client_max_body_size 34m;
	...
}

location ~ /(bin|locales|modules|node_modules|templates|var|vendor) {
    deny all;
    return 404;
}

location ~ \.php$ {
	# Usual FastCGI configuration
}

location / {
    index index.html index.htm index.php;
    try_files $uri $uri/ $uri/index.php /index.php?$args;
}
```


## Updating

While the structure of the framework isn't upgradeable since it consisted of a simple starting point for your own application, the bulk of the core components is safely packaged in NPM and Packagist (Composer).  Therefore, `make update` should provide all the bug fixes you'll need down the road.

## Acknowledgements

This application would not have been possible within a reasonable time frame without the help of the following:

### Server-side

* The PHP language
* The SQLite database engine
* The Sphido Events library to facilitate event-driven design
* The Twig PHP templating library
* The 100% PHP Gettext implementation

### Client-side

#### Frameworks

* jQuery 2 (there were performance issues with version 3)
* Twitter Bootstrap 3, including its gracious Glyphicon license

#### Utilities

* ParsleyJS to validate forms client-side
* Selectize to create rich, interactive form inputs
* Timeago to display human-readable timestamp descriptions

### Build Tools

* Browserify
* Clean-CSS
* Uglify-JS


## Developers

For more information on the framework used to build this application, see related project [PyritePHP](https://github.com/vphantom/pyritephp).


## GNU Affero GPL v3.0 License

Copyright (c) 2016-2018 Stéphane Lavergne <https://github.com/vphantom>

The GNU Affero GPL license fits this end-user software well because it allows:

* Commercial use
* Modifications and creation of derivatives
* Distribution in original or modified form

...while prohibiting:

* Granting other licenses
* Liability to the license owner

...and requiring:

* Original copyright be retained in derivatives
* License be kept intact in derivatives
* Documenting changes made vs the original
* Keep source code available when distributing *or serving using a modified version* (the "Affero" bit)
* Include/keep installation instructions in derivatives

It protects the original copyright and keeps the project *and its derivatives* available, free and open source.

For full details, see [LICENSE.txt](LICENSE.txt).
