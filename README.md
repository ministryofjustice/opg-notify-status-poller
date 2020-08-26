# opg-notify-status-poller

[![ministryofjustice](https://circleci.com/gh/ministryofjustice/opg-notify-status-poller.svg?style=svg)](https://github.com/ministryofjustice/opg-notify-status-poller)

### Building

    cp local.env.example local.env
    
    # Install dependencies on your host machine
    phpqa composer install --prefer-dist --no-interaction --no-scripts
    
    # Update the local.env file with any secret credentials when testing external services
    docker-compose build job-runner

### Running

    docker-compose --project-name notify-status-poller up job-runner
    
If you are not developing against a local or test version of Notify or Sirius you can run the mock services with:

    docker-compose --project-name notify-status-poller up -d --build --force-recreate mock-notify
    docker-compose --project-name notify-status-poller up -d --build --force-recreate mock-sirius

## Testing

Unit tests

    docker-compose --project-name notify-status-poller run --rm test

Functional tests
    
    docker-compose --project-name notify-status-poller up -d localstack
    docker-compose --project-name notify-status-poller up -d --build --force-recreate mock-notify
    docker-compose --project-name notify-status-poller up -d --build --force-recreate mock-sirius
    docker-compose --project-name notify-status-poller run --rm test-functional
    
## Check Linting / Static Analysis

    docker-compose --project-name notify-status-poller run --rm lint    
    docker-compose --project-name notify-status-poller run --rm phpstan
   
## References

- https://docs.notifications.service.gov.uk/php.html#send-a-precompiled-letter
- http://docs.guzzlephp.org/en/stable/
