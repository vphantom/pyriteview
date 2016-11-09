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

bin/composer:
	@mkdir -p bin
	wget https://raw.githubusercontent.com/composer/getcomposer.org/6cf720ddb5567ef7f97b6855fda18dba92209f27/web/installer -O composer-setup.php
	php composer-setup.php --install-dir=bin --filename=composer
	rm -f composer-setup.php
	
init:	bin/composer
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

.PHONY:	help clean init distrib
