all: lint static-analysis unit-test check-coverage functional-test build

lint: copy-env
	docker compose run lint

test-results:
	mkdir -p -m 0777 test-results

setup-directories: test-results

static-analysis phpstan: copy-env composer
	docker compose run phpstan

composer:
	docker compose run composer

unit-test: composer copy-env
	docker compose --project-name notify-status-poller run --rm test

check-coverage: copy-env
	docker compose run check-coverage

build: copy-env
	docker compose build status-poller

copy-env:
	cp local.env.example local.env

functional-test: copy-env
	docker compose up --wait --build -d mock-notify
	docker compose up --wait --build -d mock-sirius
	docker compose run --build test-functional
	docker compose down

metrics: copy-env
	docker compose run phpmetrics

up: copy-env
	docker compose up --wait status-poller

down:
	docker compose down
