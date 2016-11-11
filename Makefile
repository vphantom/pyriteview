COMPOSER   := bin/composer
NPM        := npm
CSS        := node_modules/.bin/cleancss --skip-rebase
BROWSERIFY := node_modules/.bin/browserify
JS         := node_modules/.bin/uglifyjs >/dev/null --compress --mangle
JSLINT     := node_modules/.bin/eslint --fix
GZIP       := gzip -f -n -k -9

help:
	@echo
	@echo "  [ Users ]  make init    - First-time initialization"
	@echo
	@echo "  [ Devel ]  make distrib - Build a new release"
	@echo "  [ Devel ]  make clean   - Remove all dev files, become a release"
	@echo

deps:
	@if ! which gzip >/dev/null; then echo "  **  Please install gzip."; exit 1; fi
	@if ! which wget >/dev/null; then echo "  **  Please install wget."; exit 1; fi
	@if ! which npm  >/dev/null; then echo "  **  Please install NPM, part of NodeJS."; exit 1; fi
	@if ! which php  >/dev/null; then echo "  **  Please install PHP 5+."; exit 1; fi
	@if ! php -m |grep -q memcached;  then echo "  **  Please install the PHP extension memcached."; exit 1; fi
	@if ! php -m |grep -q pdo_sqlite; then echo "  **  Please install the PHP extension pdo_sqlite."; exit 1; fi

bin/composer:	deps
	@mkdir -p bin
	wget https://raw.githubusercontent.com/composer/getcomposer.org/6cf720ddb5567ef7f97b6855fda18dba92209f27/web/installer -O composer-setup.php
	php composer-setup.php --install-dir=bin --filename=composer
	rm -f composer-setup.php
	
init:	deps bin/composer
	$(COMPOSER) install
	mkdir -p var
	mkdir -p var/twig_cache
	$(NPM) install
	# TODO: Create fresh SQLite DB file

clean:
	rm -fr bin node_modules vendor var

distrib:
	# TODO: Build css and css.gz
	# TODO: Build js and js.gz

.PHONY:	help deps clean init distrib
