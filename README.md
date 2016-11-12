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

### First-time initialization

Clone or unzip this repository into the document root of the web site this will become, and run `make init` in that directory.  This will automatically download and set up PHP's Composer package manager, then use it to download runtime dependencies locally.

### Development requirements

If you intend to contribute to PyriteView, in addition to the above you will need Node.JS for its package manager "NPM".  Then, `make dev-init` will use it to locally install the necessary build tools.

The build process then simply consists of `make distrib` which rebuilds `client.css[.gz]` and `client.js[.gz]`.

## Usage

TODO
