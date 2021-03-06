<?php

declare(strict_types=1);

namespace NotifyStatusPollerTest\Unit\Query\Handler;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use NotifyStatusPoller\Authentication\JwtAuthenticator;
use NotifyStatusPoller\Query\Handler\GetInProgressDocumentsHandler;
use NotifyStatusPoller\Query\Model\GetNotifyStatus;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class GetInProgressDocumentsHandlerTest extends TestCase
{
    private const ENDPOINT_URI = 'test-web-uri';
    private GuzzleClient $guzzleClientMock;
    private JwtAuthenticator $mockAuthenticator;

    public function setUp(): void
    {
        $this->guzzleClientMock = $this->createMock(GuzzleClient::class);
        $this->mockAuthenticator = $this->createMock(JwtAuthenticator::class);
    }

    /**
     * @throws GuzzleException
     */
    public function test_handle_gets_document_statuses_success()
    {
        $handler = new GetInProgressDocumentsHandler($this->guzzleClientMock, self::ENDPOINT_URI,$this->mockAuthenticator);
        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->createMock(StreamInterface::class);
        $response = [
            ['id' => 1, 'notifyId' => 'send-id-1'],
            ['id' => 2, 'notifyId' => 'send-id-2'],
            ['id' => 3, 'notifyId' => 'send-id-3'],
        ];
        $expectedResult = [
            new GetNotifyStatus(
                ['documentId' => $response[0]['id'], 'notifyId' => $response[0]['notifyId']]
            ),
            new GetNotifyStatus(
                ['documentId' => $response[1]['id'], 'notifyId' => $response[1]['notifyId']]
            ),
            new GetNotifyStatus(
                ['documentId' => $response[2]['id'], 'notifyId' => $response[2]['notifyId']]
            ),
        ];

        $this->guzzleClientMock->expects(self::once())->method('get')->with(self::ENDPOINT_URI,
            ['headers' => $this->mockAuthenticator->createToken()])->willReturn($responseMock);
        $responseMock->method('getBody')->willReturn($streamMock);
        $streamMock->method('getContents')->willReturn(json_encode($response));

        $actualResult = $handler->handle();

        self::assertCount(count($expectedResult), $actualResult);

        foreach ($expectedResult as $k => $v) {
            self::assertEquals($v->getDocumentId(), $actualResult[$k]->getDocumentId());
            self::assertEquals($v->getNotifyId(), $actualResult[$k]->getNotifyId());
        }
    }
}
