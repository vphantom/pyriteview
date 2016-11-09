COMPOSER := bin/composer

help:
	@echo "Available targets: help, init, dist-clean"

bin/composer:
	@mkdir -p bin
	wget https://raw.githubusercontent.com/composer/getcomposer.org/6cf720ddb5567ef7f97b6855fda18dba92209f27/web/installer -O composer-setup.php
	php composer-setup.php --install-dir=bin --filename=composer
	rm -f composer-setup.php
	
init:	bin/composer
	$(COMPOSER) install
	mkdir -p var
	mkdir -p var/twig_cache

dist-clean:
	rm -fr bin vendor var

.PHONY:	help init dist-clean
