<?php 

namespace JumpTwentyFour;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Subscriber\Retry\RetrySubscriber;

class Client  {

  	protected $client;

  	private $mock = NULL;

  	private $error = NULL;

  	private $status_code = NULL;

  	private $called_url = NULL;

  	private $reason = NULL;

    private $body = NULL;

    protected $max_retries = 3;

    public function setupDefaultClientConfig(array $config)
    {
        $client_options = isset($config['http_client_options'])?$config['http_client_options']: array();

        $this->client = new Client($client_options);

        if (!is_null($this->mock)) {

            $this->client->getEmitter()->attach($this->mock);

        }

        $this->client->getEmitter()->attach($this->getRetryConfig($config));

    }

 	/**
 	 * sets up the mock data for the get requests to allow for testing
 	 * @param  GuzzleHttp\Subscriber\Mock $mock_data the Mock object to pass into the system for testing
 	 * @return [type]            [description]
 	 */
 	public function setupMockDataForRequest(\GuzzleHttp\Subscriber\Mock $mock_data)
 	{
 		$this->mock = $mock_data;
 	}

    public function get($url, $parameters, $dump_data = false)
    {
        return $this->performRequest('GET', $url, $parameters, $dump_data);
    }

    public function post($url, $post_parameters, $dump_data = false)
    {
        return $this->performRequest('POST', $url, $post_parameters, $dump_data);
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getReason()
    {
        return $this->reason;
    }

    public function getCalledUrl()
    {
        return $this->called_url;
    }

    public function getBody()
    {
        return $this->body;
    }

    protected function performRequest($method, $url, $query_parameters = array(), $dump_data = false)
    {
        try {

            $request = $this->client->createRequest($method,  $this->version . $url);

            if ($method == 'GET') {

                $query = $request->getQuery();

                if (!empty($query_parameters)) {

                    foreach ($query_parameters as $field => $value) {

                        $query->set($field, $value);

                    }

                }

            } else if ($method == 'POST') {

                $postBody = $request->getBody();

                if (!empty($query_parameters)) {

                    foreach ($query_parameters as $field => $value) {

                        $postBody->setField($field, $value);

                    }

                }

            }

            $response = $this->client->send($request);

            if ($dump_data) {

                $body = $response->getBody();

                echo $body;

                die();

            }

            $this->setResponseValues($response);

        } catch(ServerException $e) {

            $this->setErrorResponseValue($e->getResponse());

            $this->error = $e->getMessage();

        } catch(ClientException $e) {

            $this->error = $e->getMessage();

            $this->setErrorResponseValue($e->getResponse());

        } catch (RequestException $e) {

            $this->error = $e->getMessage();

            $this->setErrorResponseValue($e->getResponse());

        }

    }

    private function getMaxRetries(array $config)
    {
        if (array_key_exists('max_retries', $config)) {

            $this->max_retries = $config['max_retries'];

        } else

            $this->max_retries = $this->max_retries;
    }

    /**
 	 * sets up the error responses for the exceptions handled
 	 * @param GuzzleHttp\Message\Response $response [description]
 	 */
 	private function setResponseValues(\GuzzleHttp\Message\Response $response)
 	{

		$this->status_code = $response->getStatusCode();

		$this->reason = $response->getReasonPhrase();

		$this->called_url = $response->getEffectiveUrl();

        $this->body = $response->json();
 	
 	}

    /**
     * sets the error values for when there is an issue with the response
     */
    private function setErrorResponseValue($response)
    {

        $this->status_code = $response->getStatusCode();

        $this->reason = $response->getReasonPhrase();

        $this->called_url = $response->getEffectiveUrl();

        $this->body = $response->json();

    }

    private function getRetryConfig(array $config)
    {
        return  new RetrySubscriber([
            'max' => $this->getMaxRetries($config),
            'filter' => RetrySubscriber::createChainFilter([
                RetrySubscriber::createStatusFilter(),
                RetrySubscriber::createCurlFilter(),
            ]),
        ]);
    }

}