<?php
namespace veldor\PhpFirebaseCloudMessaging;

use Exception;
use GuzzleHttp;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * @author veldor
 */
class Client implements ClientInterface
{
    //const DEFAULT_API_URL = 'https://fcm.googleapis.com/fcm/send';

    private function getDefaultApiUrl()
    {
        return "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    const DEFAULT_TOPIC_ADD_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchAdd';
    const DEFAULT_TOPIC_REMOVE_SUBSCRIPTION_API_URL = 'https://iid.googleapis.com/iid/v1:batchRemove';

    private $oauthKey;
    private $proxyApiUrl;
    //private $guzzleClient;
    /**
     * @var mixed
     */
    private $projectId;

  /*  public function injectGuzzleHttpClient(GuzzleHttp\ClientInterface $client)
    {
        $this->guzzleClient = $client;
    }*/

    /**
     * add your server api key here
     * read how to obtain an api key here: https://firebase.google.com/docs/server/setup#prerequisites
     *
     * @param string $oauthKey
     *
     * @return Client
     */
    public function setOauthKey($oauthKey)
    {
        $this->oauthKey = $oauthKey;
        return $this;
    }

    public function setProjectId($projectId)
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
    public function setProxyApiUrl($url)
    {
        $this->proxyApiUrl = $url;
        return $this;
    }

    /**
     * sends your notification to the google servers and returns a guzzle repsonse object
     * containing their answer.
     *
     * @param Message $message
     *
     * @return ResponseInterface
     * @throws RequestException
     */
    public function send(Message $message)
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

        /*return $this->guzzleClient->post(
            $this->getApiUrl(),
            [
                'headers' => [
                    'Authorization: Bearer ' . $this->oauthKey,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($message)
            ]
        );*/
    }

    /**
     * @param integer $topic_id
     * @param array|string $recipients_tokens
     *
     * @return ResponseInterface
     */
    public function addTopicSubscription($topic_id, $recipients_tokens)
    {
        return $this->processTopicSubscription($topic_id, $recipients_tokens, self::DEFAULT_TOPIC_ADD_SUBSCRIPTION_API_URL);
    }


    /**
     * @param integer $topic_id
     * @param array|string $recipients_tokens
     *
     * @return ResponseInterface
     */
    public function removeTopicSubscription($topic_id, $recipients_tokens)
    {
        return $this->processTopicSubscription($topic_id, $recipients_tokens, self::DEFAULT_TOPIC_REMOVE_SUBSCRIPTION_API_URL);
    }


    /**
     * @param integer $topic_id
     * @param array|string $recipients_tokens
     * @param string $url
     *
     * @return ResponseInterface
     */
    protected function processTopicSubscription($topic_id, $recipients_tokens, $url)
    {
        if (!is_array($recipients_tokens))
            $recipients_tokens = [$recipients_tokens];
    }


    private function getApiUrl()
    {
        return isset($this->proxyApiUrl) ? $this->proxyApiUrl : $this->getDefaultApiUrl();
    }
}