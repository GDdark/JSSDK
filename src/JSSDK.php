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
    private $appSecret;

    public function __construct($appId, $appSecret, HttpClient $httpClient, MessageFactory $messageFactory, $url, Redis $redis = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->httpClient = $httpClient;
        $this->messageFactory = $messageFactory;
        $this->url = $url;
        $this->redis = $redis;
    }

    public function getSignPackage()
    {
        $nonceStr = $this->createNonceStr();

        $queryData = [
            'jsapi_ticket' => $this->getJsApiTicket(),
            'noncestr' => $nonceStr,
            'timestamp' => time(),
            'url' => $this->url,
        ];
        $queryString = http_build_query($queryData);

        return [
            'appId' => $this->appId,
            'nonceStr' => $nonceStr,
            'timestamp' => time(),
            'url' => $this->url,
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

    private function getJsApiTicket()
    {
        $ticket = is_null($this->redis) ? null : ($this->redis->get($this->getJsApiTicketKey()) ?: null);

        if (is_null($ticket)) {
            $request = $this->messageFactory->createRequest(
                'GET',
                'https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=' . $this->getAccessToken()
            );

            $ticketData = json_decode($this->httpClient->sendRequest($request)->getBody(), true);

            if (is_array($ticketData) && isset($ticketData['ticket'])) {
                !is_null($this->redis) && $this->redis->set($this->getJsApiTicketKey(), $ticketData['ticket'], $ticketData['expires_in'] - 200);
            } else {
                throw new Exception($ticketData['errmsg']);
            }

            return $ticketData['ticket'];
        } else {
            return $ticket;
        }
    }

    private function getJsApiTicketKey()
    {
        return 'wx:jsapi_ticket';
    }

    private function getAccessToken()
    {
        $accessToken = is_null($this->redis) ? null : ($this->redis->get($this->getAccessTokenKey()) ?: null);

        if (is_null($accessToken)) {
            $request = $this->messageFactory->createRequest(
                'GET',
                "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret"
            );

            $accessTokenData = json_decode($this->httpClient->sendRequest($request)->getBody(), true);

            if (is_array($accessTokenData) && isset($accessTokenData['access_token'])) {
                !is_null($this->redis) && $this->redis->set($this->getAccessTokenKey(), $accessTokenData['access_token'], $accessTokenData['expires_in'] - 200);
            } else {
                throw new Exception($accessTokenData['errmsg']);
            }

            return $accessTokenData['access_token'];
        } else {
            return $accessToken;
        }
    }

    private function getAccessTokenKey()
    {
        return 'wx:access_token';
    }
}
