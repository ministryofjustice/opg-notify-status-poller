<?php

declare(strict_types=1);

namespace NotifyStatusPoller\Authentication;

use Firebase\JWT\JWT;

class JwtAuthentication
{
    private string $jwtSecret;
    private string $sessionData;

    public function __construct(string $jwtSecret, string $sessionData)
    {
        $this->jwtSecret = $jwtSecret;
        $this->sessionData = $sessionData;
    }

    /**
     * Generates the headers expected by the API.
     *
     * @return array<string>
     */
    public function buildHeaders()
    {
        //Lcobucci work for generating a JWT token
        //        $token = (new Builder())->issuedBy($this->sessionData)
//            ->issuedAt(time())
//            ->expiresAt(time()+600)
//            ->withClaim('session-data', $this->sessionData)
//            ->getToken('HS256',$this->jwtSecret);

        $issueTime = time();

        $claims = array(
            /*
             * Then for every HTTP request you get from the client, the session id (given by the client) will point you to the correct session data (stored by the server) that contains the authenticated user id - that way your code will know what user it is talking to.
             */
            "session-data" => $this->sessionData, // who is it attrributed to - this id is stored in the session data then for every request, what do you want to preserve over page loads - in this instance this is the email address for the publicapi user - we decide what is placed in session
            "iat" => $issueTime,
            'exp' => $issueTime + 600
        );

        //session data in LPA is declared in terraform and it is sent through the parameters - in LPA and File Service, it seems to be the public api opgtest

        $encoded_jwt = JWT::encode($claims, $this->jwtSecret, 'HS256');

        return [
            'Authorization' => 'Bearer ' . $encoded_jwt,
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
         * 2. Add unit tests around new JWT class
         * 3. Ensure that the JWT authentication does actually work by adding a document with an in progress status and seeing if it is picked up by the poller
         * 4. Similarly a document with a status change needs to be checked so the status is updated by notify
         * 5. Change to use the module that Rich says is better for the JWT stuff.
         * 6. Need to add logic that if a JWT token has been generated and is still valid, to use that instead of generating a new one - have a chat with Rich
         *
         */
    }
}
