<?php

namespace ApiVideo\Client\Api;

use ApiVideo\Client\Model\Live;
use Buzz\Exception\RequestException;
use Buzz\Message\Form\FormUpload;
use UnexpectedValueException;

class Lives extends BaseApi
{
    /**
     * @param string $liveStreamId
     * @return Live|null
     */
    public function get($liveStreamId)
    {
        $response = $this->browser->get("/live-streams/$liveStreamId");
        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $this->unmarshal($response);
    }

    /**
     * Incrementally iterate over a collection of elements.
     * By default the elements are returned in an array, unless you pass a
     * $callback which will be called for each instance of Video.
     * Available parameters:
     *   - currentPage (int)   current pagination page
     *   - pageSize    (int)   number of elements per page
     *   - liveIds    (array) liveIds to limit the search to
     * If currentPage and pageSize are not given, the method iterates over all
     * pages of results and return an array containing all the results.
     *
     * @param array $parameters
     * @param callable $callback
     * @return Live[]|null
     */
    public function search(array $parameters = array(), $callback = null)
    {
        $params             = $parameters;
        $currentPage        = isset($parameters['currentPage']) ? $parameters['currentPage'] : 1;
        $params['pageSize'] = isset($parameters['pageSize']) ? $parameters['pageSize'] : 100;
        $allLives           = array();

        do {
            $params['currentPage'] = $currentPage;
            $response              = $this->browser->get('/live-streams?'.http_build_query($parameters));

            if (!$response->isSuccessful()) {
                $this->registerLastError($response);

                return null;
            }

            $json  = json_decode($response->getContent(), true);
            $lives = $json['data'];

            $allLives[] = $this->castAll($lives);
            if (null !== $callback) {
                foreach (current($allLives) as $live) {
                    call_user_func($callback, $live);
                }
            }

            if (isset($parameters['currentPage'])) {
                break;
            }

            $pagination = $json['pagination'];
            $pagination['currentPage']++;
        } while ($pagination['pagesTotal'] > $pagination['currentPage']);
        $allLives = call_user_func_array('array_merge', $allLives);

        if (null === $callback) {
            return $allLives;
        }

        return null;
    }

    /**
     * @param string $name
     * @param array $properties
     * @return Live|null
     */
    public function create($name, array $properties = array())
    {
        $response = $this->browser->post(
            '/live-streams',
            array(),
            json_encode(
                array_merge(
                    $properties,
                    array('name' => $name)
                )
            )
        );
        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $this->unmarshal($response);
    }

    /**
     * @param string $source Path to the file to upload
     * @param string $liveStreamId
     * @return Live|null
     * @throws RequestException
     * @throws UnexpectedValueException
     */
    public function uploadThumbnail($source, $liveStreamId)
    {
        if (!is_readable($source)) {
            throw new UnexpectedValueException("'$source' must be a readable source file.");
        }

        $resource = fopen($source, 'rb');

        $stats  = fstat($resource);
        $length = $stats['size'];
        if (0 >= $length) {
            throw new UnexpectedValueException("'$source' is empty.");
        }

        $response = $this->browser->submit(
            "/live-streams/$liveStreamId/thumbnail",
            array('file' => new FormUpload($source))
        );

        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $this->unmarshal($response);
    }

    /**
     * @param string $liveStreamId
     * @param array $properties
     * @return Live|null
     */
    public function update($liveStreamId, array $properties)
    {
        $response = $this->browser->patch(
            "/live-streams/$liveStreamId",
            array(),
            json_encode($properties)
        );

        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $this->unmarshal($response);
    }

    /**
     * @param string $liveStreamId
     * @return int|null
     */
    public function delete($liveStreamId)
    {
        $response = $this->browser->delete("/live-streams/$liveStreamId");

        if (!$response->isSuccessful()) {
            $this->registerLastError($response);

            return null;
        }

        return $response->getStatusCode();
    }

    /**
     * @param array $data
     * @return Live
     */
    protected function cast(array $data)
    {
        $live               = new Live();
        $live->liveStreamId = $data['liveStreamId'];
        $live->name         = $data['name'];
        $live->streamKey    = $data['streamKey'];
        $live->record       = $data['record'];
        $live->broadcasting = $data['broadcasting'];
        $live->assets       = $data['assets'];

        return $live;
    }
}
