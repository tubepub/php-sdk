<?php


use ApiVideo\Client\Api\AnalyticsVideo;
use Buzz\Message\Response;
use PHPUnit\Framework\TestCase;

class AnalyticsVideoTest extends TestCase
{
    /**
     * @test
     * @throws ReflectionException
     */
    public function getSucceed()
    {

        $analyticReturn = $this->getVideoAnalytic();

        $response = new Response();

        $responseReflected = new ReflectionClass('Buzz\Message\Response');
        $statusCode        = $responseReflected->getProperty('statusCode');
        $statusCode->setAccessible(true);
        $statusCode->setValue($response, 200);
        $setContent = $responseReflected->getMethod('setContent');
        $setContent->invokeArgs($response, array($analyticReturn));


        $oAuthBrowser = $this->getMockedOAuthBrowser();
        $oAuthBrowser->method('get')->willReturn($response);

        $AnalyticsVideo = new AnalyticsVideo($oAuthBrowser);
        $analytic  = $AnalyticsVideo->get('vi55mglWKqgywdX8Yu8WgDZ0', '2018-07-31');

        $analyticExpected = json_decode($analyticReturn, true);
        $this->assertInstanceOf('ApiVideo\Client\Model\Analytic\AnalyticVideo', $analytic);
        $this->assertSame($analyticExpected['video']['video_id'], $analytic->videoId);
        $this->assertSame($analyticExpected['video']['title'], $analytic->videoTitle);
        $this->assertSame($analyticExpected['period'], $analytic->period);
        $this->assertNotEmpty($analytic->data);
        $this->assertCount(3, $analytic->data);

    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function getFailed()
    {
        $returned = '{
            "status": 400,
            "type": "https://docs.api.video/problems/ressource.not_found",
            "title": "The requested resource was not found.",
            "name": "videoId"
        }';

        $response = new Response();

        $responseReflected = new ReflectionClass('Buzz\Message\Response');
        $statusCode        = $responseReflected->getProperty('statusCode');
        $statusCode->setAccessible(true);
        $statusCode->setValue($response, 400);


        $setContent = $responseReflected->getMethod('setContent');
        $setContent->invokeArgs($response, array($returned));


        $oAuthBrowser = $this->getMockedOAuthBrowser();
        $oAuthBrowser->method('get')->willReturn($response);

        $AnalyticsVideo = new AnalyticsVideo($oAuthBrowser);
        $analytic  = $AnalyticsVideo->get('viWKqgywdX55mgl8Yu8WgDZ0');

        $this->assertNull($analytic);
        $error = $AnalyticsVideo->getLastError();

        $this->assertSame(400, $error['status']);
        $this->assertSame(json_decode($returned, true), $error['message']);

    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function searchSucceed()
    {
        $returned = $this->getCollectionAnalyticsVideo();
        $response = new Response();

        $responseReflected = new ReflectionClass('Buzz\Message\Response');
        $statusCode        = $responseReflected->getProperty('statusCode');
        $statusCode->setAccessible(true);
        $statusCode->setValue($response, 200);
        $setContent = $responseReflected->getMethod('setContent');
        $setContent->invokeArgs($response, array($returned));

        $oAuthBrowser = $this->getMockedOAuthBrowser();

        $oAuthBrowser->method('get')->willReturn($response);

        $AnalyticsVideo = new AnalyticsVideo($oAuthBrowser);
        $results = $AnalyticsVideo->search();

        $videosReflected = new ReflectionClass('ApiVideo\Client\Api\Videos');
        $castAll         = $videosReflected->getMethod('castAll');
        $castAll->setAccessible(true);

        $AnalyticsVideoReturn = json_decode($returned, true);
        unset($AnalyticsVideoReturn['period']);
        $this->assertEquals(array_merge(array(), $castAll->invokeArgs($AnalyticsVideo, $AnalyticsVideoReturn)), $results);


    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function searchWithPaginationSucceed()
    {
        $returned = $this->getCollectionAnalyticsVideo();
        $response = new Response();

        $responseReflected = new ReflectionClass('Buzz\Message\Response');
        $statusCode        = $responseReflected->getProperty('statusCode');
        $statusCode->setAccessible(true);
        $statusCode->setValue($response, 200);
        $setContent = $responseReflected->getMethod('setContent');
        $setContent->invokeArgs($response, array($returned));

        $oAuthBrowser = $this->getMockedOAuthBrowser();

        $oAuthBrowser->method('get')->willReturn($response);

        $AnalyticsVideo = new AnalyticsVideo($oAuthBrowser);
        $results = $AnalyticsVideo->search(array('currentPage' => 1));

        $videosReflected = new ReflectionClass('ApiVideo\Client\Api\Videos');
        $castAll         = $videosReflected->getMethod('castAll');
        $castAll->setAccessible(true);

        $AnalyticsVideoReturn = json_decode($returned, true);
        unset($AnalyticsVideoReturn['period']);
        $this->assertEquals(array_merge(array(), $castAll->invokeArgs($AnalyticsVideo, $AnalyticsVideoReturn)), $results);


    }

    /**
     * @test
     * @throws ReflectionException
     */
    public function searchWithBadParametersShouldFailed()
    {
        $return = '{
            "status": 400,
            "type": "https://docs.api.video/problems/invalid.pagination",
            "title": "Invalid page. Must be at least equal to 1",
            "name": "page"
        }';

        $response = new Response();

        $responseReflected = new ReflectionClass('Buzz\Message\Response');
        $statusCode        = $responseReflected->getProperty('statusCode');
        $statusCode->setAccessible(true);
        $statusCode->setValue($response, 400);
        $setContent = $responseReflected->getMethod('setContent');
        $setContent->invokeArgs($response, array($return));

        $oAuthBrowser = $this->getMockedOAuthBrowser();

        $oAuthBrowser->method('get')->willReturn($response);

        $AnalyticsVideo = new AnalyticsVideo($oAuthBrowser);
        $results = $AnalyticsVideo->search(
            array(
                'currentPage' => 0,
                'pageSize'    => 25,
            )
        );
        $this->assertNull($results);
        $error = $AnalyticsVideo->getLastError();

        $this->assertSame(400, $error['status']);
        $return = json_decode($return, true);
        $this->assertSame($return, $error['message']);
    }

    private function getMockedOAuthBrowser()
    {
        return $this->getMockBuilder('ApiVideo\Client\Buzz\OAuthBrowser')
                    ->setMethods(array('get', 'submit', 'post', 'patch', 'delete'))
                    ->getMock();
    }

    private function getVideoAnalytic()
    {
        return '
        {
            "video": {
                "video_id": "vi55mglWKqgywdX8Yu8WgDZ0",
                "title": "Test"
            },
            "period": "2018-07-31",
            "data": [
                {
                    "session": {
                        "session_id": "psJd8U77m2BddeNwM5A1jrG0",
                        "loaded_at": "2018-07-31 15:17:49.822+02",
                        "ended_at": "2018-07-31T15:17:49.822000+02:00"
                    },
                    "location": {
                        "country": "France",
                        "city": "Paris"
                    },
                    "referrer": {
                        "url": "unknown",
                        "medium": "unknown",
                        "source": "unknown",
                        "search_term": "unknown"
                    },
                    "device": {
                        "type": "desktop",
                        "vendor": "unknown",
                        "model": "unknown"
                    },
                    "os": {
                        "name": "unknown",
                        "shortname": "unknown",
                        "version": "unknown"
                    },
                    "client": {
                        "type": "browser",
                        "name": "Firefox",
                        "version": "61.0"
                    },
                    "events": [
                        {
                            "type": "player_session_video.loaded",
                            "emitted_at": "2018-07-31T15:17:49.822000+02:00"
                        }
                    ]
                },
                {
                    "session": {
                        "session_id": "ps4CPJ1MTXUBAzZExi9JQXpx",
                        "loaded_at": "2018-07-31 15:17:49.822+02",
                        "ended_at": "2018-07-31T15:17:49.822000+02:00"
                    },
                    "location": {
                        "country": "France",
                        "city": "Paris"
                    },
                    "referrer": {
                        "url": "unknown",
                        "medium": "unknown",
                        "source": "unknown",
                        "search_term": "unknown"
                    },
                    "device": {
                        "type": "desktop",
                        "vendor": "unknown",
                        "model": "unknown"
                    },
                    "os": {
                        "name": "unknown",
                        "shortname": "unknown",
                        "version": "unknown"
                    },
                    "client": {
                        "type": "browser",
                        "name": "Firefox",
                        "version": "61.0"
                    },
                    "events": [
                        {
                            "type": "player_session_video.loaded",
                            "emitted_at": "2018-07-31T15:17:49.822000+02:00"
                        }
                    ]
                },
                {
                    "session": {
                        "session_id": "psp02UdkjoXu5JzO4mc6sOj",
                        "loaded_at": "2018-07-31 15:17:49.822+02",
                        "ended_at": "2018-07-31T15:17:49.822000+02:00"
                    },
                    "location": {
                        "country": "France",
                        "city": "Paris"
                    },
                    "referrer": {
                        "url": "unknown",
                        "medium": "unknown",
                        "source": "unknown",
                        "search_term": "unknown"
                    },
                    "device": {
                        "type": "desktop",
                        "vendor": "unknown",
                        "model": "unknown"
                    },
                    "os": {
                        "name": "unknown",
                        "shortname": "unknown",
                        "version": "unknown"
                    },
                    "client": {
                        "type": "browser",
                        "name": "Firefox",
                        "version": "61.0"
                    },
                    "events": [
                        {
                            "type": "player_session_video.loaded",
                            "emitted_at": "2018-07-31T15:17:49.822000+02:00"
                        }
                    ]
                }
            ]
        }';
    }

    private function getCollectionAnalyticsVideo()
    {
        return '{
            "period": "2018-08-02",
            "data": [
            {
                "video": {
                    "video_id": "vi55mglWKqgywdX8Yu8WgDZ0",
                    "title": "Test"
                },
                "period": "2018-07-31",
                "data": [
                    {
                        "session": {
                            "session_id": "psJd8U77m2BddeNwM5A1jrG0",
                            "loaded_at": "2018-07-31 15:17:49.822+02",
                            "ended_at": "2018-07-31T15:17:49.822000+02:00"
                        },
                        "location": {
                            "country": "France",
                            "city": "Paris"
                        },
                        "referrer": {
                            "url": "unknown",
                            "medium": "unknown",
                            "source": "unknown",
                            "search_term": "unknown"
                        },
                        "device": {
                            "type": "desktop",
                            "vendor": "unknown",
                            "model": "unknown"
                        },
                        "os": {
                            "name": "unknown",
                            "shortname": "unknown",
                            "version": "unknown"
                        },
                        "client": {
                            "type": "browser",
                            "name": "Firefox",
                            "version": "61.0"
                        },
                        "events": [
                            {
                                "type": "player_session_video.loaded",
                                "emitted_at": "2018-07-31T15:17:49.822000+02:00"
                            }
                        ]
                    },
                    {
                        "session": {
                            "session_id": "ps4CPJ1MTXUBAzZExi9JQXpx",
                            "loaded_at": "2018-07-31 15:17:49.822+02",
                            "ended_at": "2018-07-31T15:17:49.822000+02:00"
                        },
                        "location": {
                            "country": "France",
                            "city": "Paris"
                        },
                        "referrer": {
                            "url": "unknown",
                            "medium": "unknown",
                            "source": "unknown",
                            "search_term": "unknown"
                        },
                        "device": {
                            "type": "desktop",
                            "vendor": "unknown",
                            "model": "unknown"
                        },
                        "os": {
                            "name": "unknown",
                            "shortname": "unknown",
                            "version": "unknown"
                        },
                        "client": {
                            "type": "browser",
                            "name": "Firefox",
                            "version": "61.0"
                        },
                        "events": [
                            {
                                "type": "player_session_video.loaded",
                                "emitted_at": "2018-07-31T15:17:49.822000+02:00"
                            }
                        ]
                    },
                    {
                        "session": {
                            "session_id": "psp02UdkjoXu5JzO4mc6sOj",
                            "loaded_at": "2018-07-31 15:17:49.822+02",
                            "ended_at": "2018-07-31T15:17:49.822000+02:00"
                        },
                        "location": {
                            "country": "France",
                            "city": "Paris"
                        },
                        "referrer": {
                            "url": "unknown",
                            "medium": "unknown",
                            "source": "unknown",
                            "search_term": "unknown"
                        },
                        "device": {
                            "type": "desktop",
                            "vendor": "unknown",
                            "model": "unknown"
                        },
                        "os": {
                            "name": "unknown",
                            "shortname": "unknown",
                            "version": "unknown"
                        },
                        "client": {
                            "type": "browser",
                            "name": "Firefox",
                            "version": "61.0"
                        },
                        "events": [
                            {
                                "type": "player_session_video.loaded",
                                "emitted_at": "2018-07-31T15:17:49.822000+02:00"
                            }
                        ]
                    }
                ]
            }],
            "pagination": {
                "currentPage": 1,
                "pageSize": 25,
                "pagesTotal": 1,
                "itemsTotal": 1,
                "currentPageItems": 1,
                "links": [
                    {
                        "rel": "self",
                        "uri": "http://ws.api.video/AnalyticsVideo?currentPage=1"
                    },
                    {
                        "rel": "first",
                        "uri": "http://ws.api.video/AnalyticsVideo?currentPage=1"
                    },
                    {
                        "rel": "last",
                        "uri": "http://ws.api.video/AnalyticsVideo?currentPage=1"
                    }
                ]
            }
        }';
    }
}
