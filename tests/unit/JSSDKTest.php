<?php

class JSSDKTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $client;

    public function testJSSDK()
    {
        $accessTokenResponse =  [
            'access_token' => '6bHpf69kt_t5R6Zmq_enjMnQgAjcRxIv9_WKoUJjEKeVfIWM_XPvrCHck3-3nQzoM5HqVUS978UnKWHpO8mLJBR_MP-QCb8pBfX5d3r4xlkLVPcAFAPZT',
        ];
        $ticketResponse = [
            'ticket' => 'sM4AOVdWfPE4DxkXGEs8VMlzBaHFCdSO7_mLL-FwihfAY7gkoX2TBRX0VZXZL-ODtokb_Gapb49kMDe_76uw2w',
        ];
        $mockClient = new \Http\Mock\Client();
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($accessTokenResponse)));
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($accessTokenResponse)));
        $mockClient->addResponse(new \GuzzleHttp\Psr7\Response(200, [], json_encode($ticketResponse)));

        $jssdk = new \JSSDK\JSSDK("your_id", "your_id_secret", $mockClient, new Http\Message\MessageFactory\GuzzleMessageFactory(), null, 'http://');
        $accessTokenData = $jssdk->getAccessToken();

        $this->tester->assertEquals($accessTokenResponse['access_token'], $accessTokenData);

        $ticket = $jssdk->getJsApiTicket();
        $this->tester->assertEquals($ticketResponse['ticket'], $ticket);
    }
}