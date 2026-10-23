all: lint static-analysis unit-test check-coverage functional-test build

lint:
	docker compose run \
	--volume ${PWD}/build/output/:/output/ \
	--rm status-poller-test \
	vendor/bin/phpcs \
		-p --report=checkstyle --standard=PSR12 \
		--warning-severity=0 ./src ./public

test-results:
	mkdir -p -m 0777 test-results

setup-directories: test-results

static-analysis phpstan:
	docker compose run \
	--volume ${PWD}/build/output/:/output/ \
	--rm status-poller-test \
	vendor/bin/phpstan \
		analyse -c /var/www/tests/phpstan.neon

composer:
	docker compose run composer

unit-test: create-output-dir
		docker compose run \
		--volume ${PWD}/build/output/:/output/ \
		--rm status-poller-test \
		vendor/bin/phpunit \
			--configuration=/var/www/tests/phpunit.xml \
			--coverage-text=/output/unit-coverage.txt \
			--log-junit=/output/unit-test.xml \
			--testsuite=unit

build:
	docker compose build status-poller

copy-env:
	cp local.env.example local.env

create-output-dir:
	mkdir -p -m 0777 build/output

functional-test: create-output-dir
	docker compose up --wait --build -d mock-notify
	docker compose up --wait --build -d mock-sirius
	docker compose run \
		--volume ${PWD}/build/output/:/output/ \
		--env-from-file local.env.example \
		--rm status-poller-test \
		vendor/bin/phpunit \
			--configuration=/var/www/tests/phpunit.xml \
			--no-coverage \
			--log-junit=/output/functional-test.xml \
			--testsuite=functional

up: copy-env
	docker compose up --wait status-poller

down:
	docker compose down
