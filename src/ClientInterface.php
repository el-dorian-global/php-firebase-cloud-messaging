<?php
namespace veldor\PhpFirebaseCloudMessaging;

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
     * @return Client
     */
    function setOauthKey($apiKey);
    

    /**
     * people can overwrite the api url with a proxy server url of their own
     *
     * @param string $url
     *
     * @return Client
     */
    function setProxyApiUrl($url);

    /**
     * @param Message $message
     * @return array|null
     * @throws \Exception
     */
    function send(Message $message);
    
}
   