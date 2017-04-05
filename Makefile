COMPOSER   := bin/composer
NPM        := npm
SQLITE     := sqlite3
CSS        := node_modules/.bin/cleancss --skip-rebase
BROWSERIFY := node_modules/.bin/browserify
EXORCIST   := node_modules/.bin/exorcist
JS         := node_modules/.bin/uglifyjs >/dev/null --compress --mangle
JSLINT     := node_modules/.bin/eslint --fix
GZIP       := gzip -f -n -k -9
LANGUAGES  := `sed '/^languages\s*=/!d; s/^languages\s*=\s*"\([^"]*\)"\s*$$/\1/' var/config.ini |head -n1`

FONT_SRC := $(wildcard node_modules/bootstrap/dist/fonts/*.*) \
	$(wildcard node_modules/summernote/dist/font/*.*)

CSS_SRC := node_modules/bootstrap/dist/css/bootstrap.css node_modules/bootstrap/dist/css/bootstrap-theme.css \
	node_modules/selectize/dist/css/selectize.css node_modules/selectize/dist/css/selectize.bootstrap3.css \
	node_modules/summernote/dist/summernote.css \
	$(wildcard vendor/vphantom/pyritephp/assets/*.css) \
	$(wildcard modules/*.css)

JS_SRC := pyritephp.js locales/loader.js $(wildcard modules/*.js)

GETTEXT_TEMPLATES := $(wildcard templates/lib templates/*.html templates/*/lib templates/*/*.html)

BUILD_TARGETS := client.css.gz client.js.gz fonts locales/fr.po locales/en.po

BACKUP_TARGETS := var/main.sql var/main.db var/config.ini

help:
	@echo
	@echo "  [ Users ]  make init    - First-time initialization"
	@echo "  [ Users ]  make backup  - Create config+DB backup file"
	@echo "  [ Users ]  make archive - Create FULL backup including attachments"
	@echo
	@echo "  [ Devel ]  make build   - Rebuild any outdated/missing release files"
	@echo "  [ Devel ]  make clean   - Remove rebuildable files"
	@echo

deps:
	@if ! which zip >/dev/null;  then echo "  **  Please install zip."; exit 1; fi
	@if ! which gzip >/dev/null; then echo "  **  Please install gzip."; exit 1; fi
	@if ! which wget >/dev/null; then echo "  **  Please install wget."; exit 1; fi
	@if ! which php  >/dev/null; then echo "  **  Please install PHP 5+."; exit 1; fi
	@if ! which sqlite3 >/dev/null; then echo "  **  Please install SQLite 3."; exit 1; fi

bin/composer:
	@mkdir -p bin
	wget https://raw.githubusercontent.com/composer/getcomposer.org/6cf720ddb5567ef7f97b6855fda18dba92209f27/web/installer -O composer-setup.php
	php composer-setup.php --install-dir=bin --filename=composer
	rm -f composer-setup.php
	
init:	deps bin/composer var/config.ini
	$(COMPOSER) install
	@mkdir -p var/twig_cache var/sessions var/articles
	if [ ! -f var/main.db ]; then $(SQLITE) /dev/null '.save var/main.db'; fi
	php index.php --trigger install

var/config.ini:	var/config-example.ini
	cp $^ $@

dev-init:	deps
	@if ! which npm  >/dev/null; then echo "  **  Please install NPM, part of NodeJS."; exit 1; fi
	$(NPM) install

update:	deps bin/composer
	$(COMPOSER) self-update
	$(COMPOSER) update

dev-update:	update
	@if ! which npm  >/dev/null; then echo "  **  Please install NPM, part of NodeJS."; exit 1; fi
	$(NPM) update

clean:
	rm -f client.css client.css.map client.css.gz
	rm -f build.js build.js.map client.js client.js.map client.js.gz
	rm -fr fonts
	rm -f var/backup.zip var/archive.zip

build:	$(BUILD_TARGETS)

backup:	deps var/backup.zip

archive:	deps var/archive.zip

fonts:	$(FONT_SRC)
	mkdir -p fonts
	cp $^ $@/

# Bootstrap hard-codes "../fonts/" which we need to clean up
# Similarly, Summertime hard-codes "font/" whereas we need "fonts/"
client.css:	$(CSS_SRC)
	$(CSS) --source-map -o $@ $^
	mv $@ $@.tmp
	cat $@.tmp |sed 's/url(font/url(fonts/g; s/\.\.\/\(fonts\)/\1/g' >$@
	rm -f $@.tmp

client.js:	$(JS_SRC)
	$(BROWSERIFY) $^  -d |$(EXORCIST) build.js.map >build.js
	$(JS) --in-source-map build.js.map --source-map client.js.map -o client.js -- build.js
	rm -f build.js build.js.map

# Copying here so that Node/Browserify doesn't fall all over itself on paths
pyritephp.js:	vendor/vphantom/pyritephp/assets/pyritephp.js
	cp $^ $@

locales/messages.pot:	$(GETTEXT_TEMPLATES)
	grep -oh -E '__\([^)~]+\)' $(GETTEXT_TEMPLATES) |sort |uniq >var/tmp.src
	xgettext --from-code UTF-8 -L Lua -k -k__ -kn_:1,2 --force-po --no-location -o var/tmp.pot var/tmp.src
	if [ ! -e "$@" ]; then mv var/tmp.pot "$@"; else msgmerge --update $@ var/tmp.pot; fi
	rm -f var/tmp.src var/tmp.pot
	touch $@

locales/%.po:	locales/messages.pot $(GETTEXT_TEMPLATES)
	if [ ! -e "$@" ]; then cp "$<" "$@"; fi
	msgmerge  -N --update "$@" "$<"
	touch $@

locales/loader.js:	var/config.ini
	@echo "'use strict';" >$@
	@declare -A LOCALES=(["ar"]="ar-AR" ["bg"]="bg-BG" ["ca"]="ca-ES" ["cs"]="cs-CZ" ["da"]="da-DK" ["de"]="de-DE" ["es"]="es-ES" ["fa"]="fa-IR" ["fi"]="fi-FI" ["fr"]="fr-FR" ["gl"]="gl-ES" ["he"]="he-IL" ["hr"]="hr-HR" ["hu"]="hu-HU" ["id"]="id-ID" ["it"]="it-IT" ["ja"]="ja-JP" ["ko"]="ko-KR" ["lt"]="lt-LT" ["nb"]="nb-NO" ["nl"]="nl-NL" ["pl"]="pl-PL" ["pt"]="pt-PT" ["ro"]="ro-RO" ["ru"]="ru-RU" ["sk"]="sk-SK" ["sl"]="sl-SI" ["sv"]="sv-SE" ["th"]="th-TH" ["tr"]="tr-TR" ["uk"]="uk-UA" ["vi"]="vi-VN" ["zh"]="zh-CN"); \
	for LANG in $(LANGUAGES); do \
		if [ $$LANG != 'en' ]; then \
			echo "global.__timeago.register('$${LANG}', require('timeago.js/locales/$${LANG}'));" >>$@ ; \
			echo "require('parsleyjs/dist/i18n/$${LANG}');" >>$@ ; \
			echo "require('summernote/lang/summernote-$${LOCALES[$$LANG]}');" >>$@ ; \
		fi ; \
	done

var/main.sql:	var/main.db
	sqlite3 $^ .dump |grep -Ev '^(PRAGMA|BEGIN|COMMIT)' >$@

var/backup.zip:	$(BACKUP_TARGETS)
	rm -f $@
	zip -r9 $@ $^
	@echo "    * Backup file is: $@"

var/archive.zip:	$(BACKUP_TARGETS)
	rm -f $@
	zip -r9 $@ $^ var/articles/
	@echo "    * Archive file is: $@"

%.gz: %
	$(GZIP) $< -c >$@

.PHONY:	help deps clean init dev-init update dev-update build backup archive
