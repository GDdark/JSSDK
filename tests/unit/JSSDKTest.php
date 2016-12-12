<?php

class JSSDKTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $client;

    public function testGetSignPackageSuccess()
    {
        $accessTokenResponse =  [
            'access_token' => '6bHpf69kt_t5R6Zmq_enjMnQgAjcRxIv9_WKoUJjEKeVfIWM_XPvrCHck3-3nQzoM5HqVUS978UnKWHpO8mLJBR_MP-QCb8pBfX5d3r4xlkLVPcAFAPZT',
            'expires_in' => 7200,
        ];
        $ticketResponse = [
            'ticket' => 'sM4AOVdWfPE4DxkXGEs8VMlzBaHFCdSO7_mLL-FwihfAY7gkoX2TBRX0VZXZL-ODtokb_Gapb49kMDe_76uw2w',
            'expires_in' => 7200,
        ];

        $mockClient = new \Http\Mock\Client();
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($accessTokenResponse)));
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($ticketResponse)));

        $url = 'http://baidu.com';
        $jssdk = new \JSSDK\JSSDK("your_id", "your_id_secret", $mockClient, new Http\Message\MessageFactory\GuzzleMessageFactory(), $url);

        $signPackage = $jssdk->getSignPackage();

        $this->tester->assertEquals('your_id', $signPackage['appId']);
        $this->tester->assertArrayHasKey('nonceStr', $signPackage);
        $this->tester->assertArrayHasKey('timestamp', $signPackage);
        $this->tester->assertEquals($url, $signPackage['url']);
        $this->tester->assertArrayHasKey('signature', $signPackage);
        $this->tester->assertArrayHasKey('rawString', $signPackage);
        $this->tester->assertEquals(sprintf('jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s', $ticketResponse['ticket'], $signPackage['nonceStr'], $signPackage['timestamp'], $url), $signPackage['rawString']);
        $this->tester->assertEquals(sha1($signPackage['rawString']), $signPackage['signature']);
    }

    public function testGetAccessTokenError()
    {
        $accessTokenResponse =  [
            'errmsg' => 'error get access token',
        ];
        $mockClient = new \Http\Mock\Client();
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($accessTokenResponse)));

        $url = 'http://baidu.com';
        $jssdk = new \JSSDK\JSSDK("your_id", "your_id_secret", $mockClient, new Http\Message\MessageFactory\GuzzleMessageFactory(), $url);

        $this->tester->expectException(new \JSSDK\Exception($accessTokenResponse['errmsg']), function () use ($jssdk) {
            $jssdk->getSignPackage();
        });
    }

    public function testGetJsApiTicketError()
    {
        $accessTokenResponse =  [
            'access_token' => '6bHpf69kt_t5R6Zmq_enjMnQgAjcRxIv9_WKoUJjEKeVfIWM_XPvrCHck3-3nQzoM5HqVUS978UnKWHpO8mLJBR_MP-QCb8pBfX5d3r4xlkLVPcAFAPZT',
            'expires_in' => 7200,
        ];
        $jsApiTicketResponse =  [
            'errmsg' => 'error get js api ticket',
        ];
        $mockClient = new \Http\Mock\Client();
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($accessTokenResponse)));
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($jsApiTicketResponse)));

        $url = 'http://baidu.com';
        $jssdk = new \JSSDK\JSSDK("your_id", "your_id_secret", $mockClient, new Http\Message\MessageFactory\GuzzleMessageFactory(), $url);

        $this->tester->expectException(new \JSSDK\Exception($jsApiTicketResponse['errmsg']), function () use ($jssdk) {
            $jssdk->getSignPackage();
        });
    }
}
