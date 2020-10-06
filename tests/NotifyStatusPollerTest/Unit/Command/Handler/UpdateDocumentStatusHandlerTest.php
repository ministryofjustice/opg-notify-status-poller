<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Command\Handler;

use Alphagov\Notifications\Authentication\JWTAuthenticationInterface;
use NotifyStatusPoller\Authentication\JwtAuthentication;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use NotifyStatusPoller\Command\Model\UpdateDocumentStatus;
use NotifyStatusPoller\Command\Handler\UpdateDocumentStatusHandler;
use NotifyStatusPoller\Mapper\NotifyStatus;

class UpdateDocumentStatusHandlerTest extends TestCase
{
    private const ENDPOINT = '/update-status';
    private $mockGuzzleClient;
    private $mockNotifyStatusMapper;
    private $mockAuthenticator;

    private UpdateDocumentStatusHandler $handler;

    public function setUp(): void
    {
        parent::setUp();

        $this->mockNotifyStatusMapper = $this->createMock(NotifyStatus::class);
        $this->mockGuzzleClient = $this->createMock(GuzzleClient::class);
        $this->mockAuthenticator = $this->createMock(JwtAuthentication::class);
        $this->handler = new UpdateDocumentStatusHandler(
            $this->mockNotifyStatusMapper,
            $this->mockGuzzleClient,
            $this->mockAuthenticator,
            self::ENDPOINT
        );
    }

    /**
     * @throws GuzzleException
     */
    public function testUpdateStatusSuccess(): void
    {
        $command = $this->createUpdateDocumentStatusCommand();
        $siriusStatus = 'status';
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $siriusStatus,
        ];

        $this->mockNotifyStatusMapper
            ->expects(self::once())
            ->method('toSirius')
            ->with($command->getNotifyStatus())
            ->willReturn($siriusStatus);


        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->expects(self::once())->method('getStatusCode')->willReturn(204);

        $this->mockGuzzleClient
            ->expects(self::once())
            ->method('put')
            ->with(self::ENDPOINT, ['headers' => $this->mockAuthenticator->buildHeaders(),'json' => $payload])
            ->willReturn($mockResponse);

        $this->handler->handle($command);
    }

    /**
     * @throws GuzzleException
     */
    public function testInvalidResponseStatusCodeFailure(): void
    {
        $command = $this->createUpdateDocumentStatusCommand();
        $siriusStatus = 'status';
        $payload = [
            'documentId' => $command->getDocumentId(),
            'notifySendId' => $command->getNotifyId(),
            'notifyStatus' => $siriusStatus,
        ];

        $this->mockNotifyStatusMapper
            ->expects(self::once())
            ->method('toSirius')
            ->with($command->getNotifyStatus())
            ->willReturn($siriusStatus);


        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);

        $this->mockGuzzleClient
            ->expects(self::once())
            ->method('put')
            ->with(self::ENDPOINT, ['headers' => $this->mockAuthenticator->buildHeaders(),'json' => $payload])
            ->willReturn($mockResponse);

        self::expectException(UnexpectedValueException::class);
        self::expectExceptionMessage(
            sprintf('Expected status "%s" but received "%s"', 204, $mockResponse->getStatusCode())
        );

        $this->handler->handle($command);
    }

    private function createUpdateDocumentStatusCommand(): UpdateDocumentStatus
    {
        return new UpdateDocumentStatus([
            'notifyId' => '1',
            'notifyStatus' => NotifyStatus::ACCEPTED,
            'documentId' => '4545',
        ]);
    }
}
