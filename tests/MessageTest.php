<?php
namespace veldor\PhpFirebaseCloudMessaging\Tests;

use veldor\PhpFirebaseCloudMessaging\Recipient\Recipient;
use veldor\PhpFirebaseCloudMessaging\Message;
use veldor\PhpFirebaseCloudMessaging\Recipient\Topic;
use veldor\PhpFirebaseCloudMessaging\Notification;
use veldor\PhpFirebaseCloudMessaging\Recipient\Device;

class MessageTest extends PhpFirebaseCloudMessagingTestCase
{
    private $fixture;

    protected function setUp()
    {
        parent::setUp();
        $this->fixture = new Message();
    }

    public function testThrowsExceptionWhenDifferentRecepientTypesAreRegistered()
    {
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->fixture->addRecipient(new Topic('breaking-news'))
            ->addRecipient(new Recipient());
    }

    public function testThrowsExceptionWhenNoRecepientWasAdded()
    {
        $this->setExpectedException(\UnexpectedValueException::class);
        $this->fixture->jsonSerialize();
    }

    public function testThrowsExceptionWhenMultipleTopicsWereGiven()
    {
        $this->setExpectedException(\UnexpectedValueException::class);
        $this->fixture->addRecipient(new Topic('breaking-news'))
            ->addRecipient(new Topic('another topic'));

        $this->fixture->jsonSerialize();
    }

    public function testJsonEncodeWorksOnTopicRecipients()
    {
        $body = '{"topic":"breaking-news","notification":{"title":"test","body":"a nice testing notification"}}';

        $notification = new Notification('test', 'a nice testing notification');
        $message = new Message();
        $message->setNotification($notification);

        $message->addRecipient(new Topic('breaking-news'));
        $this->assertSame(
            $body,
            json_encode($message)
        );
    }

    public function testJsonEncodeWorksOnDeviceRecipients()
    {
        $body = '{"token":"deviceId","notification":{"title":"test","body":"a nice testing notification"}}';

        $notification = new Notification('test', 'a nice testing notification');
        $message = new Message();
        $message->setNotification($notification);

        $message->addRecipient(new Device('deviceId'));
        $this->assertSame(
            $body,
            json_encode($message)
        );
    }

    public function testDataValuesAreSerializedAsStringsForV1()
    {
        $message = new Message();
        $message->addRecipient(new Device('deviceId'));
        $message->setData([
            'bill_id' => 12816,
            'is_paid' => true,
            'amount' => 1739.95,
        ]);

        $payload = $message->jsonSerialize();

        $this->assertSame('12816', $payload['data']['bill_id']);
        $this->assertSame('true', $payload['data']['is_paid']);
        $this->assertSame('1739.95', $payload['data']['amount']);
    }

    public function testPriorityIsMappedToPlatformConfigForV1()
    {
        $message = new Message();
        $message->addRecipient(new Device('deviceId'));
        $message->setPriority('high');

        $payload = $message->jsonSerialize();

        $this->assertSame('HIGH', $payload['android']['priority']);
        $this->assertSame('10', $payload['apns']['headers']['apns-priority']);
    }
}