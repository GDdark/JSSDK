<?php
namespace JSSDK;

use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Redis;

class JSSDK
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var Redis
     */
    private $redis;

    private $appId;
    private $appIdSecret;
    private $protocol;

    public function __construct($id, $idSecret, HttpClient $httpClient, MessageFactory $messageFactory, Redis $redis = null, $protocol = null)
    {
        $this->appId = $id;
        $this->appIdSecret = $idSecret;
        $this->httpClient = $httpClient;
        $this->messageFactory = $messageFactory;
        $this->redis = $redis;
        $this->protocol = is_null($protocol) ?
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://'
            : $protocol;
    }

    public function getSignPackage()
    {
        $url = $this->protocol . $_SERVER['HTTP_HOST'] ?: '' . $_SERVER['REQUEST_URI'] ?: '';
        $nonceStr = $this->createNonceStr();

        $queryData = [
            'jsapi_ticket' => $this->getJsApiTicket(),
            'noncestr' => $nonceStr,
            'timestamp' => time(),
            'url' => $url,
        ];

        $queryString = http_build_query($queryData);

        return [
            'appId' => $this->appId,
            'nonceStr' => $nonceStr,
            'timestamp' => time(),
            'url' => $url,
            'signature' => sha1($queryString),
            'rawString' => $queryString,
        ];
    }

    private function createNonceStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; ++$i) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }

        return $str;
    }

    public function getJsApiTicket()
    {
        $ticketData = is_null($this->redis) ? null : json_decode($this->redis->get($this->getJsApiTicketKey()), true);

        if (is_null($ticketData)) {
            $request = $this->messageFactory->createRequest(
                'GET',
                'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=' . $this->getAccessToken()
            );

            $ticket = json_decode((string) $this->httpClient->sendRequest($request)->getBody(), true)['ticket'];

            if ($ticket) {
                !is_null($this->redis) && $this->redis->set($this->getJsApiTicketKey(), json_encode(['jsapi_ticket' => $ticket]), 6500);
            }

            return $ticket;
        } else {
            return $ticketData['jsapi_ticket'];
        }
    }

    private function getJsApiTicketKey()
    {
        return 'wx:jsapi_ticket';
    }

    public function getAccessToken()
    {
        $accessTokenData = is_null($this->redis) ? null : json_decode($this->redis->get($this->getAccessTokenKey()), true);

        if (is_null($accessTokenData)) {
            $request = $this->messageFactory->createRequest(
                'GET',
                "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appIdSecret"
            );

            $accessTokenData = json_decode((string) $this->httpClient->sendRequest($request)->getBody(), true);

            if ($accessTokenData['access_token']) {
                !is_null($this->redis) && $this->redis->set($this->getAccessTokenKey(), json_encode(['access_token' => $accessTokenData['access_token']]), 6500);
            } else {
                throw new \Exception($accessTokenData['errmsg']);
            }

            return $accessTokenData['access_token'];
        } else {
            return $accessTokenData['access_token'];
        }
    }

    private function getAccessTokenKey()
    {
        return 'wx:access_token';
    }
}