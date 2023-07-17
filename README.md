# opg-notify-status-poller

![Build Status](https://github.com/ministryofjustice/opg-notify-status-poller/actions/workflows/build.yml/badge.svg)

## Building

    docker build --file docker/Dockerfile --tag notify-status-poller:latest .

### Building with dev dependencies

    # Install dependencies on your host machine
    make composer

    # Update the local.env file with any secret credentials when testing external services
    make build-dev

### Running

    make up

If you are not developing against a local or test version of Notify or Sirius you can run the mock services with:

    docker compose --project-name notify-status-poller up -d --build --force-recreate mock-notify
    docker compose --project-name notify-status-poller up -d --build --force-recreate mock-sirius

## Testing

Unit tests

    make unit-test

Functional tests

    make functionl-test

## Check Linting / Static Analysis

    make lint
    make phpstan

## Updating composer.json dependencies

    docker compose run composer require <PACKAGE>>:<VERSION>

    E.g.
    docker compose run composer require package/name:^1.0

## References

- https://docs.notifications.service.gov.uk/php.html#send-a-precompiled-letter
- http://docs.guzzlephp.org/en/stable/
