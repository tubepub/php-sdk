<?php


namespace ApiVideo\Client\Api;

use ApiVideo\Client\Model\Analytic\AnalyticData;
use ApiVideo\Client\Model\Analytic\AnalyticEvent;
use ApiVideo\Client\Model\Analytic\AnalyticLive;

class AnalyticsLive extends BaseApi
{
    /**
     * @param $liveStreamId
     * @param string|null $period
     * @return AnalyticLive|null
     */
    public function get($liveStreamId, $period = null)
    {
        $parameters = '';
        if (null !== $period) {
            $parameters = "?period=$period";
        }

        $response = $this->browser->get("/analytics/live-streams/$liveStreamId".$parameters);
        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $this->unmarshal($response);
    }

    /**
     * @param array $parameters
     * @return AnalyticLive[]|null
     */
    public function search(array $parameters = array())
    {
        $params             = $parameters;
        $currentPage        = isset($parameters['currentPage']) ? $parameters['currentPage'] : 1;
        $params['pageSize'] = isset($parameters['pageSize']) ? $parameters['pageSize'] : 100;
        $allAnalytics       = array();

        do {
            $params['currentPage'] = $currentPage;
            $response              = $this->browser->get('/analytics/live-streams?'.http_build_query($parameters));

            if (!$response->isSuccessful()) {
                $this->registerLastError($response);

                return null;
            }

            $json           = json_decode($response->getContent(), true);
            $analytics      = $json['data'];
            $allAnalytics[] = $this->castAll($analytics);

            if (isset($parameters['currentPage'])) {
                break;
            }

            $pagination = $json['pagination'];
            $pagination['currentPage']++;
        } while ($pagination['pagesTotal'] > $pagination['currentPage']);

        $allAnalytics = call_user_func_array('array_merge', $allAnalytics);

        return $allAnalytics;
    }

    /**
     * @param array $data
     * @return AnalyticLive
     */
    protected function cast(array $data)
    {
        $analytic             = new AnalyticLive();
        $analytic->liveStreamId    = $data['live']['live_stream_id'];
        $analytic->liveName = $data['live']['name'];
        $analytic->period     = $data['period'];
        // Build Analytic Data
        foreach ($data['data'] as $playerSession) {
            $analyticData = new AnalyticData();

            // Build Analytic Session
            $analyticData->session->sessionId = $playerSession['session']['session_id'];
            $analyticData->session->loadedAt  = new \DateTime($playerSession['session']['loaded_at']);
            $analyticData->session->endedAt   = new \DateTime($playerSession['session']['ended_at']);

            // Build Analytic Location
            $analyticData->location->country = $playerSession['location']['country'];
            $analyticData->location->city    = $playerSession['location']['city'];

            // Build Analytic Referrer
            $analyticData->referrer->url         = $playerSession['referrer']['url'];
            $analyticData->referrer->medium      = $playerSession['referrer']['medium'];
            $analyticData->referrer->source      = $playerSession['referrer']['source'];
            $analyticData->referrer->search_term = $playerSession['referrer']['search_term'];

            // Build Analytic Device
            $analyticData->device->type   = $playerSession['device']['type'];
            $analyticData->device->vendor = $playerSession['device']['vendor'];
            $analyticData->device->model  = $playerSession['device']['model'];

            // Build Analytic Os
            $analyticData->os->name      = $playerSession['os']['name'];
            $analyticData->os->shortname = $playerSession['os']['shortname'];
            $analyticData->os->version   = $playerSession['os']['version'];

            // Build Analytic Client
            $analyticData->client->type    = $playerSession['client']['type'];
            $analyticData->client->name    = $playerSession['client']['name'];
            $analyticData->client->version = $playerSession['client']['version'];

            // Build Analytic Events
            $analyticData->events = $this->buildAnalyticEventsData($playerSession['events']);

            $analytic->data[] = $analyticData;
        }

        return $analytic;
    }

    private function buildAnalyticEventsData(array $events)
    {
        $eventsBuilded = array();

        foreach ($events as $event) {
            $analyticEvent            = new AnalyticEvent();
            $analyticEvent->type      = $event['type'];
            $analyticEvent->emittedAt = new \DateTime($event['emitted_at']);
            $analyticEvent->at        = isset($event['at']) ? $event['at'] : null;
            $analyticEvent->from      = isset($event['from']) ? $event['from'] : null;
            $analyticEvent->to        = isset($event['to']) ? $event['to'] : null;

            $eventsBuilded[] = $analyticEvent;
        }

        return $eventsBuilded;
    }

}
