# If the first argument is one of the supported commands...
SUPPORTED_COMMANDS := "install"
SUPPORTS_MAKE_ARGS := $(findstring $(firstword $(MAKECMDGOALS)), $(SUPPORTED_COMMANDS))
ifneq "$(SUPPORTS_MAKE_ARGS)" ""
    # use the rest as arguments for the command
    COMMAND_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
    # ...and turn them into do-nothing targets
    $(eval $(COMMAND_ARGS):;@:)
endif

test: phpunit phpcs bugfree phpmd
test-analysis: phpcs bugfree phpmd
test-upload: scrutinizer

install:
	make -f Makefile.docker -- composer install $(COMMAND_ARGS)

update:

.PHONY: test test-analysis test-upload pretest phpunit phpcs phpmd bugfree ocular scrutinizer clean clean-env clean-deps

pretest:
	composer install --dev
	
phpunit: pretest
	[ ! -d tests/output ] || mkdir -p tests/output
	vendor/bin/phpunit --coverage-text --coverage-clover=tests/output/coverage.clover

ifndef STRICT
STRICT = 0
endif

ifeq "$(STRICT)" "1"
phpcs: pretest
	vendor/bin/phpcs --standard=phpcs.xml src
else
phpcs: pretest
	vendor/bin/phpcs --standard=phpcs.xml -n src
endif

bugfree: pretest
	[ ! -f bugfree.json ] || vendor/bin/bugfree generateConfig
	vendor/bin/bugfree lint src -c bugfree.json

phpmd: pretest
	vendor/bin/phpmd src/ text design,naming,cleancode,codesize,controversial,unusedcode

ocular:
	[ ! -f ocular.phar ] && wget https://scrutinizer-ci.com/ocular.phar

ifdef OCULAR_TOKEN
scrutinizer: ocular
	@php ocular.phar code-coverage:upload --format=php-clover tests/output/coverage.clover --access-token=$(OCULAR_TOKEN);
else
scrutinizer: ocular
	php ocular.phar code-coverage:upload --format=php-clover tests/output/coverage.clover;
endif

clean: clean-env clean-deps

clean-env:
	rm -rf coverage.clover
	rm -rf ocular.phar
	rm -rf tests/output/
	
clean-deps:
	rm -rf vendor/
