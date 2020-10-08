<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Authentication;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class JwtAuthenticator
{
    private string $jwtSecret;
    private string $sessionData;

    public function __construct(string $jwtSecret, string $sessionData)
    {
        $this->jwtSecret = $jwtSecret;
        $this->sessionData = $sessionData;
    }

    /**
     * Generates the JWT token expected by the API.
     *
     * @return array<string>
     */
    public function createToken()
    {
        $token = (new Builder())
            ->withClaim('session-data', $this->sessionData)
            ->issuedAt(time())
            ->expiresAt(time() + 600)
            ->getToken(new Sha256(), new Key($this->jwtSecret));

        return [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json'
        ];

        /*
         * this returns the headers which will then be placed in the headers for the request to be authenticated against the api
         * the next steps is to use the mock sirius to allow for the JWT authentication and to ensure that only the JWT token created can then
         * authenticate and allow a response to come back
         *
         * Will also need to attach the header to any requests that are made using the endpoint - should a new one be generated per request or
         * if one has already been created and is still in time and not expired, should be just reuse that rather than regenerate
         * for every request
         */

        /*
         * WITH SWAGGER UI, HAD TO ADD THE BEARER SCHEME AND THEN ADD A TOKEN AS THOUGH IT HAD BEEN GIVEN A TOKEN
         * to then run some curl commands showing the headers top see the status code that comes back
         * the status code is successful which shows that the bearer component is wokring against the UI
         */

        /*
         * implementation done - to test need to build the latest verison of the service in the poller using the docker build command in the
         * read me and then building it
         * in the sirius repo, you need to check the image tag for the service and copy and paste this in place of the tag from the command
         * in the read me. once this has built, go back to sirius repo and make dev-up (may need to stop all the containers if get an error using
         * docker stop ($ docker ps -q) which stops all the running containers and then dev-up again. Check docker ps and see if the poller is up
         * and running. If it isn't may need to manually bring it up by using docker-compose up notify-status-poller and then check the logs if they
         * do not come up automatically
         *
         * At the moment, there is an exception as have not added the jwt stuff to the get in progress document handler which in turn is causing a 401
         * need to edit this including the services file to correctly use the authentication and have the correct number of arguments in the function
         */

        /*
         * 1. Ready for PR - do we need to add Public api email to AWS - already using JWT token - any further Jenkins testing - see if the log messages are present
         * 2. PR ready - may need to add unit tests
         * 3. Documentation for what did so far and how solved issues
         */
    }
}
