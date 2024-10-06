<?php
namespace veldor\PhpFirebaseCloudMessaging;

use GuzzleHttp;

/**
 *
 * @author veldor
 *
 */
interface ClientInterface
{

    /**
     * add your server api key here
     * read how to obtain an api key here: https://firebase.google.com/docs/server/setup#prerequisites
     *
     * @param string $apiKey
     *
     * @return \veldor\PhpFirebaseCloudMessaging\Client
     */
    function setOauthKey($apiKey);
    

    /**
     * people can overwrite the api url with a proxy server url of their own
     *
     * @param string $url
     *
     * @return \veldor\PhpFirebaseCloudMessaging\Client
     */
    function setProxyApiUrl($url);

    /**
     * sends your notification to the google servers and returns a guzzle repsonse object
     * containing their answer.
     *
     * @param Message $message
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\RequestException
     */
    function send(Message $message);
    
}
   