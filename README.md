# pyriteview

Article versioning hub for peer-reviewed publications.

This simple hub allows publication editors, single edition editors, article authors as well as third-party reviewers to come together to centralize the peer-reviewing workflow.

## Installation

### Requirements

* PHP 5.x or later
* PHP's `mcrypt` extension module
* PHP's `pdo_sqlite` extension module
* SQLite 3
* Typical Linux command line tools: wget, gzip
* A web server of course

### Web Server Configuration

In order to produce clean, technology-agnostic URLs such as `http://www.yourdomain.com/articles/127`, you need to tell your web server to internally redirect requests for non-existent files to `/index.php`, which will look in `PATH_INFO` for details.  We also want to prevent access to private files.

Here are sample configurations for major server software:

#### Apache

```
RewriteEngine on

RewriteRule ^(bin|lib|modules|node_modules|templates|var|vendor) - [F,L,NC]

RewriteRule ^$ /index.php [L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+) /index.php/$1 [L]
```

#### Nginx

```
location ~ /(bin|lib|modules|node_modules|templates|var|vendor) {
    deny all;
    return 404;
}

location ~ \.php$ {
    fastcgi_pass 127.0.0.1:9000;
    fastcgi_param SCRIPT_FILENAME $document_root $fastcgi_script_name;
    include fastcgi_params;
}

location / {
    index index.html index.htm index.php;
    try_files $uri $uri/ $uri/index.php /index.php;
}
```

#### Lighttpd

```
# TODO: Deny private directories
url.rewrite-if-not-file (
    "^(.*)$" => "/index.php/$1"
)
```

### First-time initialization

Clone or unzip this repository into the document root of the web site this will become, and run `make init` in that directory.  This will automatically download and set up PHP's Composer package manager, then use it to download runtime dependencies locally.

You will also need to make sure that your web server or PHP process has read-write access to the `var/` directory where the database, logs and template cache are stored.

## Usage

TODO

## Developers

See [Developers](DEVELOPERS.md).
