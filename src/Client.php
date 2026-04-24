<?php
namespace veldor\PhpFirebaseCloudMessaging;

use Exception;

/**
 * @author veldor
 */
class Client implements ClientInterface
{
    //const DEFAULT_API_URL = 'https://fcm.googleapis.com/fcm/send';

    private function getDefaultApiUrl(): string
    {
        return "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    const string DEFAULT_TOPIC_ADD_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchAdd';
    const string DEFAULT_TOPIC_REMOVE_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchRemove';

    private $oauthKey;
    private $proxyApiUrl;
    //private $guzzleClient;
    /**
     * @var mixed
     */
    private string $projectId;

  /*  public function injectGuzzleHttpClient(GuzzleHttp\ClientInterface $client)
    {
        $this->guzzleClient = $client;
    }*/

    /**
     * add your server api key here
     * read how to obtain an api key here: https://firebase.google.com/docs/server/setup#prerequisites
     *
     * @param string $apiKey
     *
     * @return Client
     */
    public function setOauthKey($apiKey): static
    {
        $this->oauthKey = $apiKey;
        return $this;
    }

    public function setProjectId($projectId): static
    {
        $this->projectId = $projectId;
        return $this;
    }

    /**
     * people can overwrite the api url with a proxy server url of their own
     *
     * @param string $url
     *
     * @return Client
     */
    public function setProxyApiUrl($url): static
    {
        $this->proxyApiUrl = $url;
        return $this;
    }

    /**
     * @param Message $message
     * @return array|null
     * @throws Exception
     */
    public function send(Message $message): ?array
    {
        $url = 'https://fcm.googleapis.com/v1/projects/' . $this->projectId . '/messages:send';
        $headers = [
            'Authorization: Bearer ' . $this->oauthKey,
            'Content-Type: application/json',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['message' => $message]));
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * @param string $topic_id
     * @param array|string $recipients_tokens
     * @return array|null
     * @throws Exception
     */
    public function addTopicSubscription(string $topic_id, array|string $recipients_tokens): ?array
    {
        return $this->processTopicSubscription($topic_id, $recipients_tokens, self::DEFAULT_TOPIC_ADD_SUBSCRIPTION_API_URL);
    }

    /**
     * @param string $topic_id
     * @param array|string $recipients_tokens
     * @return array|null
     * @throws Exception
     */
    public function removeTopicSubscription(string $topic_id, array|string $recipients_tokens): ?array
    {
        return $this->processTopicSubscription($topic_id, $recipients_tokens, self::DEFAULT_TOPIC_REMOVE_SUBSCRIPTION_API_URL);
    }

    /**
     * @param string $topic_id
     * @param array|string $recipients_tokens
     * @param string $url
     * @return array|null
     * @throws Exception
     */
    protected function processTopicSubscription(string $topic_id, array|string $recipients_tokens, string $url): ?array
    {
        if (!is_array($recipients_tokens)) {
            $recipients_tokens = [$recipients_tokens];
        }
        $headers = [
            'Authorization: Bearer ' . $this->oauthKey,
            'Content-Type: application/json',
            'access_token_auth: true',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'to' => '/topics/' . $topic_id,
            'registration_tokens' => $recipients_tokens,
        ]));
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('Curl error: ' . curl_error($ch));
        }
        curl_close($ch);
        return json_decode($response, true);
    }


    private function getApiUrl()
    {
        return isset($this->proxyApiUrl) ? $this->proxyApiUrl : $this->getDefaultApiUrl();
    }
}