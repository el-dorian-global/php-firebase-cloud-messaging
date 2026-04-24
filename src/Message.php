<?php
namespace veldor\PhpFirebaseCloudMessaging;

use veldor\PhpFirebaseCloudMessaging\Recipient\Recipient;
use veldor\PhpFirebaseCloudMessaging\Recipient\Topic;
use veldor\PhpFirebaseCloudMessaging\Recipient\Device;

/**
 * @author veldor
 */
class Message implements \JsonSerializable
{
    /**
     * Maximum topics and devices: https://firebase.google.com/docs/cloud-messaging/http-server-ref#send-downstream
     */
    const MAX_TOPICS = 3;
    const MAX_DEVICES = 1000;
    
    private $notification;
    private $collapseKey;    
    private $priority;
    private $data;
    private array $recipients = [];
    private $recipientType;    
    private $jsonData;
    private $condition;


    public function __construct() {
        $this->jsonData = [];
    }

    /**
     * where should the message go
     *
     * @param Recipient $recipient
     *
     * @return \veldor\PhpFirebaseCloudMessaging\Message
     */
    public function setRecepient(Recipient $recipient)
    {
        return $this->addRecipient($recipient);
    }

    public function addRecipient(Recipient $recipient)
    {
        $this->recipients[] = $recipient;

        if (!isset($this->recipientType)) {
            $this->recipientType = get_class($recipient);
        }
        if ($this->recipientType !== get_class($recipient)) {
            throw new \InvalidArgumentException('mixed recepient types are not supported by FCM');
        }

        return $this;
    }

    public function setNotification(Notification $notification)
    {
        $this->notification = $notification;
        return $this;
    }

    public function setCollapseKey($collapseKey)
    {
        $this->collapseKey = $collapseKey;
        return $this;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    public function setData(array $data)
    {
        $this->data = $data;
        return $this;
    }
            
    /**
     * Specify a condition pattern when sending to combinations of topics
     * https://firebase.google.com/docs/cloud-messaging/topic-messaging#sending_topic_messages_from_the_server
     *
     * Examples:
     * "%s && %s" > Send to devices subscribed to topic 1 and topic 2
     * "%s && (%s || %s)" > Send to devices subscribed to topic 1 and topic 2 or 3
     *
     * @param string $condition
     * @return $this
     */
    public function setCondition($condition) {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Set root message data via key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setJsonKey($key, $value) {
        $this->jsonData[$key] = $value;
        return $this;
    }

    /**
     * Unset root message data via key
     *
     * @param string $key
     * @return $this
     */
    public function unsetJsonKey($key) {
        unset($this->jsonData[$key]);
        return $this;
    }

    /**
     * Get root message data via key
     *
     * @param string $key
     * @return mixed
     */
    public function getJsonKey($key) {
        return $this->jsonData[$key];
    }

    /**
     * Get root message data
     *
     * @return array
     */
    public function getJsonData() {
        return $this->jsonData;
    }

    /**
     * Set root message data
     *
     * @param array $array
     * @return $this
     */
    public function setJsonData($array) {
        $this->jsonData = $array;
        return $this;
    }


    public function setDelayWhileIdle($value)
    {
        $this->setJsonKey('delay_while_idle', (bool)$value);
        return $this;
    }

    public function setTimeToLive($value)
    {
        $this->setJsonKey('time_to_live', (int)$value);
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        $jsonData = $this->jsonData;

        if (empty($this->recipients)) {
            throw new \UnexpectedValueException('Message must have at least one recipient');
        }
        
        $jsonData = array_merge($jsonData, $this->createTarget());

        if ($this->collapseKey) {
            $jsonData['android']['collapse_key'] = $this->collapseKey;
        }
        if ($this->data) {
            $jsonData['data'] = $this->normalizeData($this->data);
        }
        if ($this->priority) {
            $this->applyPriority($jsonData, $this->priority);
        }
        if ($this->notification) {
            $jsonData['notification'] = $this->notification;
        }

        return $jsonData;
    }

    private function createTarget()
    {
        $recipientCount = count($this->recipients);
        
        switch ($this->recipientType) {
                
            case Topic::class:
                
                if ($recipientCount == 1) {
                    return ['topic' => $this->normalizeTopicName(current($this->recipients)->getName())];

                } else if ($recipientCount > self::MAX_TOPICS) {
                    throw new \OutOfRangeException(sprintf('Message topic limit exceeded. Firebase supports a maximum of %u topics.', self::MAX_TOPICS));
                    
                } else if (!$this->condition) {
                    throw new \InvalidArgumentException('Missing message condition. You must specify a condition pattern when sending to combinations of topics.');
                    
                } else if ($recipientCount != substr_count($this->condition, '%s')) {                    
                    throw new \UnexpectedValueException('The number of message topics must match the number of occurrences of "%s" in the condition pattern.');
                    
                } else {
                    $names = [];
                    foreach ($this->recipients as $recipient) {
                        $names[] = vsprintf("'%s' in topics", $recipient->getName());
                    }
                    return ['condition' => vsprintf($this->condition, $names)];
                }
                break;

            case Device::class:
                
                if ($recipientCount == 1) { 
                    return ['token' => current($this->recipients)->getToken()];

                } else if ($recipientCount > self::MAX_DEVICES) {
                    throw new \OutOfRangeException(sprintf('Message device limit exceeded. Firebase supports a maximum of %u devices.', self::MAX_DEVICES));
                    
                } else {
                    throw new \UnexpectedValueException('FCM HTTP v1 send endpoint supports a single device token per request.');
                }
                break;
                
            default:
                break;
        }
        return [];
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $normalized[$key] = $value;
                continue;
            }

            if (is_bool($value)) {
                $normalized[$key] = $value ? 'true' : 'false';
                continue;
            }

            if ($value === null) {
                $normalized[$key] = '';
                continue;
            }

            if (is_scalar($value)) {
                $normalized[$key] = (string)$value;
                continue;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                throw new \InvalidArgumentException(sprintf('Message data value for key "%s" is not serializable.', $key));
            }

            $normalized[$key] = $encoded;
        }

        return $normalized;
    }

    private function applyPriority(array &$jsonData, $priority): void
    {
        $normalized = strtoupper((string)$priority);
        $jsonData['android']['priority'] = $normalized;
        $jsonData['apns']['headers']['apns-priority'] = $normalized === 'HIGH' ? '10' : '5';
    }

    private function normalizeTopicName(string $topic): string
    {
        return preg_replace('#^/topics/#', '', $topic);
    }
}