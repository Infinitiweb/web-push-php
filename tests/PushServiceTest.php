<?php

declare(strict_types=1);

/*
 * This file is part of the WebPush library.
 *
 * (c) Louis Lagrange <lagrange.louis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

final class PushServiceTest extends PHPUnit\Framework\TestCase
{
    private static $timeout = 30;
    private static $portNumber = 9012;
    private static $testSuiteId;
    private static $testServiceUrl;
    private static $gcmSenderId = '759071690750';
    private static $gcmApiKey = 'AIzaSyBAU0VfXoskxUSg81K5VgLgwblHbZWe6tA';
    private static $vapidKeys = [
        'subject' => 'http://test.com',
        'publicKey' => 'BA6jvk34k6YjElHQ6S0oZwmrsqHdCNajxcod6KJnI77Dagikfb--O_kYXcR2eflRz6l3PcI2r8fPCH3BElLQHDk',
        'privateKey' => '-3CdhFOqjzixgAbUSa0Zv9zi-dwDVmWO7672aBxSFPQ',
    ];

    /** @var WebPush WebPush with correct api keys */
    private $webPush;

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        self::$testServiceUrl = 'http://localhost:'.self::$portNumber;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!(getenv('TRAVIS') || getenv('CI'))) {
            $this->markTestSkipped('This test does not run on Travis.');
        }

        $startApiCurl = curl_init(self::$testServiceUrl.'/api/start-test-suite/');
        curl_setopt_array($startApiCurl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::$timeout,
        ]);

        $parsedResp = $this->getResponse($startApiCurl);
        self::$testSuiteId = $parsedResp->{'data'}->{'testSuiteId'};
    }

    public function browserProvider()
    {
        return [
            // Web Push
            ['firefox', 'stable', []],
            ['firefox', 'beta', []],

            // Web Push + GCM
            ['chrome', 'stable', ['GCM' => self::$gcmApiKey]],
            ['chrome', 'beta', ['GCM' => self::$gcmApiKey]],

            ['firefox', 'stable', ['GCM' => self::$gcmApiKey]],
            ['firefox', 'beta', ['GCM' => self::$gcmApiKey]],

            // Web Push + VAPID
            ['chrome', 'stable', ['VAPID' => self::$vapidKeys]],
            ['chrome', 'beta', ['VAPID' => self::$vapidKeys]],

            ['firefox', 'stable', ['VAPID' => self::$vapidKeys]],
            ['firefox', 'beta', ['VAPID' => self::$vapidKeys]],

            // Web Push + GCM + VAPID
            ['chrome', 'stable', ['GCM' => self::$gcmApiKey, 'VAPID' => self::$vapidKeys]],
            ['chrome', 'beta', ['GCM' => self::$gcmApiKey, 'VAPID' => self::$vapidKeys]],

            ['firefox', 'stable', ['GCM' => self::$gcmApiKey, 'VAPID' => self::$vapidKeys]],
            ['firefox', 'beta', ['GCM' => self::$gcmApiKey, 'VAPID' => self::$vapidKeys]],
        ];
    }

    /**
     * Selenium tests are flakey so add retries.
     */
    public function retryTest($retryCount, $test)
    {
        // just like above without checking the annotation
        for ($i = 0; $i < $retryCount; $i++) {
            try {
                $test();

                return;
            } catch (Exception $e) {
                // last one thrown below
            }
        }
        if (isset($e)) {
            throw $e;
        }
    }

    /**
     * @dataProvider browserProvider
     * Run integration tests with browsers
     */
    public function testBrowsers($browserId, $browserVersion, $options)
    {
        $this->retryTest(2, $this->createClosureTest($browserId, $browserVersion, $options));
    }

    protected function createClosureTest($browserId, $browserVersion, $options)
    {
        return function () use ($browserId, $browserVersion, $options) {
            $this->webPush = new WebPush($options);
            $this->webPush->setAutomaticPadding(false);

            $subscriptionParameters = [
                'testSuiteId' => self::$testSuiteId,
                'browserName' => $browserId,
                'browserVersion' => $browserVersion,
            ];

            if (array_key_exists('GCM', $options)) {
                $subscriptionParameters['gcmSenderId'] = self::$gcmSenderId;
            }

            if (array_key_exists('VAPID', $options)) {
                $subscriptionParameters['vapidPublicKey'] = self::$vapidKeys['publicKey'];
            }

            $subscriptionParameters = json_encode($subscriptionParameters);

            $getSubscriptionCurl = curl_init(self::$testServiceUrl.'/api/get-subscription/');
            curl_setopt_array($getSubscriptionCurl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $subscriptionParameters,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($subscriptionParameters),
                ],
                CURLOPT_TIMEOUT => self::$timeout,
            ]);

            $parsedResp = $this->getResponse($getSubscriptionCurl);
            $testId = $parsedResp->{'data'}->{'testId'};
            $subscription = $parsedResp->{'data'}->{'subscription'};

            $supportedContentEncodings = property_exists($subscription, 'supportedContentEncodings') ?
                $subscription->{'supportedContentEncodings'} :
                ["aesgcm"];

            $endpoint = $subscription->{'endpoint'};
            $keys = $subscription->{'keys'};
            $auth = $keys->{'auth'};
            $p256dh = $keys->{'p256dh'};
            $payload = 'hello';

            foreach ($supportedContentEncodings as $contentEncoding) {
                if (!in_array($contentEncoding, ['aesgcm', 'aes128gcm'])) {
                    $this->expectException('ErrorException');
                    $this->expectExceptionMessage('This content encoding ('.$contentEncoding.') is not supported.');
                    $this->markTestIncomplete('Unsupported content encoding: '.$contentEncoding);
                }

                $subscription = new Subscription($endpoint, $p256dh, $auth, $contentEncoding);

                try {
                    $sendResp = $this->webPush->sendNotification($subscription, $payload, true);
                    $this->assertInstanceOf(\Generator::class, $sendResp);

                    /** @var \Minishlink\WebPush\reports\MessageSentReport $report */
	                foreach ($sendResp as $report) {
                    	$this->assertTrue($report->isSuccess());
                    }

                    $dataString = json_encode([
                        'testSuiteId' => self::$testSuiteId,
                        'testId' => $testId,
                    ]);

                    $getNotificationCurl = curl_init(self::$testServiceUrl.'/api/get-notification-status/');
                    curl_setopt_array($getNotificationCurl, [
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => $dataString,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => [
                            'Content-Type: application/json',
                            'Content-Length: '.strlen($dataString),
                        ],
                        CURLOPT_TIMEOUT => self::$timeout,
                    ]);

                    $parsedResp = $this->getResponse($getNotificationCurl);

                    if (!property_exists($parsedResp->{'data'}, 'messages')) {
                        throw new Exception('web-push-testing-service error, no messages: '.json_encode($parsedResp));
                    }

                    $messages = $parsedResp->{'data'}->{'messages'};
                    $this->assertEquals(1, count($messages));
                    $this->assertEquals($payload, $messages[0]);
                } catch (Exception $e) {
                    if (strpos($endpoint, 'https://android.googleapis.com/gcm/send') === 0
                        && !array_key_exists('GCM', $options)) {
                        if ($e->getMessage() !== 'No GCM API Key specified.') {
                            echo $e;
                        }
                        $this->assertEquals($e->getMessage(), 'No GCM API Key specified.');
                    } else {
                        throw $e;
                    }
                }
            }
        };
    }

    protected function tearDown()
    {
        $dataString = '{ "testSuiteId": '.self::$testSuiteId.' }';
        $curl = curl_init(self::$testServiceUrl.'/api/end-test-suite/');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $dataString,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($dataString),
            ],
            CURLOPT_TIMEOUT => self::$timeout,
        ]);
        $this->getResponse($curl);
        self::$testSuiteId = null;
    }

    public static function tearDownAfterClass()
    {
        exec('web-push-testing-service stop phpunit');
    }

    private function getResponse($ch)
    {
        $resp = curl_exec($ch);

        if (!$resp) {
            $error = 'Curl error: n'.curl_errno($ch).' - '.curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        }

        $parsedResp = json_decode($resp);

        if (!property_exists($parsedResp, 'data')) {
            throw new Exception('web-push-testing-service error: '.$resp);
        }

        // Close request to clear up some resources
        curl_close($ch);

        return $parsedResp;
    }
}
