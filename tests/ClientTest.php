<?php
namespace veldor\PhpFirebaseCloudMessaging\Tests;

use veldor\PhpFirebaseCloudMessaging\Client;
use veldor\PhpFirebaseCloudMessaging\Recipient\Topic;
use veldor\PhpFirebaseCloudMessaging\Message;

use GuzzleHttp;
use GuzzleHttp\Psr7\Response;

class ClientTest extends PhpFirebaseCloudMessagingTestCase
{
    private $fixture;

    protected function setUp()
    {
        parent::setUp();
        $this->fixture = new Client();
    }

    public function testSendConstruesValidJsonForNotificationWithTopic()
    {
        $apiKey = 'key';
        $headers = array(
            'Authorization' => sprintf('key=%s', $apiKey),
            'Content-Type' => 'application/json'
        );

        $guzzle = \Mockery::mock(\GuzzleHttp\Client::class);
        $guzzle->shouldReceive('post')
            ->once()
            ->with(Client::DEFAULT_API_URL, array('headers' => $headers, 'body' => '{"to":"\\/topics\\/test"}'))
            ->andReturn(\Mockery::mock(Response::class));

        $this->fixture->injectGuzzleHttpClient($guzzle);
        $this->fixture->setOauthKey($apiKey);

        $message = new Message();
        $message->addRecipient(new Topic('test'));

        $this->fixture->send($message);
    }
}