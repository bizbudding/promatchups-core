<?php
/**
 * AskNews API
 *
 * AskNews API
 *
 * The version of the OpenAPI document: 0.15.10
 * Generated by: https://openapi-generator.tech
 * Generator version: 7.5.0
 */


namespace AskNews\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use AskNews\AccessToken;
use AskNews\ApiException;
use AskNews\Configuration;
use AskNews\HeaderSelector;
use AskNews\ObjectSerializer;

/**
 * ForecastApi Class Doc Comment
 *
 * @category Class
 * @package  AskNews
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */
class ForecastApi
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var HeaderSelector
     */
    protected $headerSelector;

    /**
     * @var int Host index
     */
    protected $hostIndex;

    /** @var string[] $contentTypes **/
    public const contentTypes = [
        'getForecast' => [
            'application/json',
        ],
    ];

    /**
     * @param ClientInterface $client
     * @param Configuration   $config
     * @param HeaderSelector  $selector
     * @param int             $hostIndex (Optional) host index to select the list of hosts if defined in the OpenAPI spec
     */
    public function __construct(
        ClientInterface $client = null,
        Configuration $config = null,
        HeaderSelector $selector = null,
        $hostIndex = 0
    ) {
        $this->client = $client ?: new Client();
        $this->config = $config ?: new Configuration();
        $this->headerSelector = $selector ?: new HeaderSelector();
        $this->hostIndex = $hostIndex;
    }

    /**
     * Set the host index
     *
     * @param int $hostIndex Host index (required)
     */
    public function setHostIndex($hostIndex): void
    {
        $this->hostIndex = $hostIndex;
    }

    /**
     * Get the host index
     *
     * @return int Host index
     */
    public function getHostIndex()
    {
        return $this->hostIndex;
    }

    /**
     * @return Configuration
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Operation getForecast
     *
     * Make an expert forecast for a news event.
     *
     * @param  string $query The requested forecast. (required)
     * @param  int $lookback The number of days to look back for the forecast. (optional, default to 14)
     * @param  int $articles_to_use The total number of relevant articles to be extracted from the news archive and used for the forecast. (optional, default to 15)
     * @param  string $method Method to use for the context search Currently only &#39;kw&#39; is supported. (optional, default to 'kw')
     * @param  string $model The model to use for the forecast. (optional, default to 'claude-3-5-sonnet-latest')
     * @param  string $cutoff_date The cutoff date for the forecast. String format is &#39;YYYY-MM-DD-HH:MM&#39;. This is useful  for backtesting forecasts. (optional)
     * @param  bool $use_reddit Whether to use Reddit data for the forecast.enterprise customers only. (optional, default to false)
     * @param  string $additional_context Additional context to use for the forecast. (optional)
     * @param  bool $web_search Whether to run a live web search and include results in the forecast. enterprise customers only. (optional, default to false)
     * @param  string $expert The type of expert to use for the forecast. (optional, default to 'general')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['getForecast'] to see the possible values for this operation
     *
     * @throws \AskNews\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return \AskNews\Model\ForecastResponse|\AskNews\Model\HTTPValidationError
     */
    public function getForecast($query, $lookback = 14, $articles_to_use = 15, $method = 'kw', $model = 'claude-3-5-sonnet-latest', $cutoff_date = null, $use_reddit = false, $additional_context = null, $web_search = false, $expert = 'general', string $contentType = self::contentTypes['getForecast'][0])
    {
        list($response) = $this->getForecastWithHttpInfo($query, $lookback, $articles_to_use, $method, $model, $cutoff_date, $use_reddit, $additional_context, $web_search, $expert, $contentType);
        return $response;
    }

    /**
     * Operation getForecastWithHttpInfo
     *
     * Make an expert forecast for a news event.
     *
     * @param  string $query The requested forecast. (required)
     * @param  int $lookback The number of days to look back for the forecast. (optional, default to 14)
     * @param  int $articles_to_use The total number of relevant articles to be extracted from the news archive and used for the forecast. (optional, default to 15)
     * @param  string $method Method to use for the context search Currently only &#39;kw&#39; is supported. (optional, default to 'kw')
     * @param  string $model The model to use for the forecast. (optional, default to 'claude-3-5-sonnet-latest')
     * @param  string $cutoff_date The cutoff date for the forecast. String format is &#39;YYYY-MM-DD-HH:MM&#39;. This is useful  for backtesting forecasts. (optional)
     * @param  bool $use_reddit Whether to use Reddit data for the forecast.enterprise customers only. (optional, default to false)
     * @param  string $additional_context Additional context to use for the forecast. (optional)
     * @param  bool $web_search Whether to run a live web search and include results in the forecast. enterprise customers only. (optional, default to false)
     * @param  string $expert The type of expert to use for the forecast. (optional, default to 'general')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['getForecast'] to see the possible values for this operation
     *
     * @throws \AskNews\ApiException on non-2xx response or if the response body is not in the expected format
     * @throws \InvalidArgumentException
     * @return array of \AskNews\Model\ForecastResponse|\AskNews\Model\HTTPValidationError, HTTP status code, HTTP response headers (array of strings)
     */
    public function getForecastWithHttpInfo($query, $lookback = 14, $articles_to_use = 15, $method = 'kw', $model = 'claude-3-5-sonnet-latest', $cutoff_date = null, $use_reddit = false, $additional_context = null, $web_search = false, $expert = 'general', string $contentType = self::contentTypes['getForecast'][0])
    {
        $request = $this->getForecastRequest($query, $lookback, $articles_to_use, $method, $model, $cutoff_date, $use_reddit, $additional_context, $web_search, $expert, $contentType);

        try {
            $options = $this->createHttpClientOption();
            try {
                $response = $this->client->send($request, $options);
            } catch (RequestException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                    $e->getResponse() ? (string) $e->getResponse()->getBody() : null
                );
            } catch (ConnectException $e) {
                throw new ApiException(
                    "[{$e->getCode()}] {$e->getMessage()}",
                    (int) $e->getCode(),
                    null,
                    null
                );
            }

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    sprintf(
                        '[%d] Error connecting to the API (%s)',
                        $statusCode,
                        (string) $request->getUri()
                    ),
                    $statusCode,
                    $response->getHeaders(),
                    (string) $response->getBody()
                );
            }

            switch($statusCode) {
                case 200:
                    if ('\AskNews\Model\ForecastResponse' === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ('\AskNews\Model\ForecastResponse' !== 'string') {
                            try {
                                $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                            } catch (\JsonException $exception) {
                                throw new ApiException(
                                    sprintf(
                                        'Error JSON decoding server response (%s)',
                                        $request->getUri()
                                    ),
                                    $statusCode,
                                    $response->getHeaders(),
                                    $content
                                );
                            }
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, '\AskNews\Model\ForecastResponse', []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                case 422:
                    if ('\AskNews\Model\HTTPValidationError' === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ('\AskNews\Model\HTTPValidationError' !== 'string') {
                            try {
                                $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                            } catch (\JsonException $exception) {
                                throw new ApiException(
                                    sprintf(
                                        'Error JSON decoding server response (%s)',
                                        $request->getUri()
                                    ),
                                    $statusCode,
                                    $response->getHeaders(),
                                    $content
                                );
                            }
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, '\AskNews\Model\HTTPValidationError', []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
            }

            $returnType = '\AskNews\Model\ForecastResponse';
            if ($returnType === '\SplFileObject') {
                $content = $response->getBody(); //stream goes to serializer
            } else {
                $content = (string) $response->getBody();
                if ($returnType !== 'string') {
                    try {
                        $content = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException $exception) {
                        throw new ApiException(
                            sprintf(
                                'Error JSON decoding server response (%s)',
                                $request->getUri()
                            ),
                            $statusCode,
                            $response->getHeaders(),
                            $content
                        );
                    }
                }
            }

            return [
                ObjectSerializer::deserialize($content, $returnType, []),
                $response->getStatusCode(),
                $response->getHeaders()
            ];

        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\AskNews\Model\ForecastResponse',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
                case 422:
                    $data = ObjectSerializer::deserialize(
                        $e->getResponseBody(),
                        '\AskNews\Model\HTTPValidationError',
                        $e->getResponseHeaders()
                    );
                    $e->setResponseObject($data);
                    break;
            }
            throw $e;
        }
    }

    /**
     * Operation getForecastAsync
     *
     * Make an expert forecast for a news event.
     *
     * @param  string $query The requested forecast. (required)
     * @param  int $lookback The number of days to look back for the forecast. (optional, default to 14)
     * @param  int $articles_to_use The total number of relevant articles to be extracted from the news archive and used for the forecast. (optional, default to 15)
     * @param  string $method Method to use for the context search Currently only &#39;kw&#39; is supported. (optional, default to 'kw')
     * @param  string $model The model to use for the forecast. (optional, default to 'claude-3-5-sonnet-latest')
     * @param  string $cutoff_date The cutoff date for the forecast. String format is &#39;YYYY-MM-DD-HH:MM&#39;. This is useful  for backtesting forecasts. (optional)
     * @param  bool $use_reddit Whether to use Reddit data for the forecast.enterprise customers only. (optional, default to false)
     * @param  string $additional_context Additional context to use for the forecast. (optional)
     * @param  bool $web_search Whether to run a live web search and include results in the forecast. enterprise customers only. (optional, default to false)
     * @param  string $expert The type of expert to use for the forecast. (optional, default to 'general')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['getForecast'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getForecastAsync($query, $lookback = 14, $articles_to_use = 15, $method = 'kw', $model = 'claude-3-5-sonnet-latest', $cutoff_date = null, $use_reddit = false, $additional_context = null, $web_search = false, $expert = 'general', string $contentType = self::contentTypes['getForecast'][0])
    {
        return $this->getForecastAsyncWithHttpInfo($query, $lookback, $articles_to_use, $method, $model, $cutoff_date, $use_reddit, $additional_context, $web_search, $expert, $contentType)
            ->then(
                function ($response) {
                    return $response[0];
                }
            );
    }

    /**
     * Operation getForecastAsyncWithHttpInfo
     *
     * Make an expert forecast for a news event.
     *
     * @param  string $query The requested forecast. (required)
     * @param  int $lookback The number of days to look back for the forecast. (optional, default to 14)
     * @param  int $articles_to_use The total number of relevant articles to be extracted from the news archive and used for the forecast. (optional, default to 15)
     * @param  string $method Method to use for the context search Currently only &#39;kw&#39; is supported. (optional, default to 'kw')
     * @param  string $model The model to use for the forecast. (optional, default to 'claude-3-5-sonnet-latest')
     * @param  string $cutoff_date The cutoff date for the forecast. String format is &#39;YYYY-MM-DD-HH:MM&#39;. This is useful  for backtesting forecasts. (optional)
     * @param  bool $use_reddit Whether to use Reddit data for the forecast.enterprise customers only. (optional, default to false)
     * @param  string $additional_context Additional context to use for the forecast. (optional)
     * @param  bool $web_search Whether to run a live web search and include results in the forecast. enterprise customers only. (optional, default to false)
     * @param  string $expert The type of expert to use for the forecast. (optional, default to 'general')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['getForecast'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    public function getForecastAsyncWithHttpInfo($query, $lookback = 14, $articles_to_use = 15, $method = 'kw', $model = 'claude-3-5-sonnet-latest', $cutoff_date = null, $use_reddit = false, $additional_context = null, $web_search = false, $expert = 'general', string $contentType = self::contentTypes['getForecast'][0])
    {
        $returnType = '\AskNews\Model\ForecastResponse';
        $request = $this->getForecastRequest($query, $lookback, $articles_to_use, $method, $model, $cutoff_date, $use_reddit, $additional_context, $web_search, $expert, $contentType);

        return $this->client
            ->sendAsync($request, $this->createHttpClientOption())
            ->then(
                function ($response) use ($returnType) {
                    if ($returnType === '\SplFileObject') {
                        $content = $response->getBody(); //stream goes to serializer
                    } else {
                        $content = (string) $response->getBody();
                        if ($returnType !== 'string') {
                            $content = json_decode($content);
                        }
                    }

                    return [
                        ObjectSerializer::deserialize($content, $returnType, []),
                        $response->getStatusCode(),
                        $response->getHeaders()
                    ];
                },
                function ($exception) {
                    $response = $exception->getResponse();
                    $statusCode = $response->getStatusCode();
                    throw new ApiException(
                        sprintf(
                            '[%d] Error connecting to the API (%s)',
                            $statusCode,
                            $exception->getRequest()->getUri()
                        ),
                        $statusCode,
                        $response->getHeaders(),
                        (string) $response->getBody()
                    );
                }
            );
    }

    /**
     * Create request for operation 'getForecast'
     *
     * @param  string $query The requested forecast. (required)
     * @param  int $lookback The number of days to look back for the forecast. (optional, default to 14)
     * @param  int $articles_to_use The total number of relevant articles to be extracted from the news archive and used for the forecast. (optional, default to 15)
     * @param  string $method Method to use for the context search Currently only &#39;kw&#39; is supported. (optional, default to 'kw')
     * @param  string $model The model to use for the forecast. (optional, default to 'claude-3-5-sonnet-latest')
     * @param  string $cutoff_date The cutoff date for the forecast. String format is &#39;YYYY-MM-DD-HH:MM&#39;. This is useful  for backtesting forecasts. (optional)
     * @param  bool $use_reddit Whether to use Reddit data for the forecast.enterprise customers only. (optional, default to false)
     * @param  string $additional_context Additional context to use for the forecast. (optional)
     * @param  bool $web_search Whether to run a live web search and include results in the forecast. enterprise customers only. (optional, default to false)
     * @param  string $expert The type of expert to use for the forecast. (optional, default to 'general')
     * @param  string $contentType The value for the Content-Type header. Check self::contentTypes['getForecast'] to see the possible values for this operation
     *
     * @throws \InvalidArgumentException
     * @return \GuzzleHttp\Psr7\Request
     */
    public function getForecastRequest($query, $lookback = 14, $articles_to_use = 15, $method = 'kw', $model = 'claude-3-5-sonnet-latest', $cutoff_date = null, $use_reddit = false, $additional_context = null, $web_search = false, $expert = 'general', string $contentType = self::contentTypes['getForecast'][0])
    {

        // verify the required parameter 'query' is set
        if ($query === null || (is_array($query) && count($query) === 0)) {
            throw new \InvalidArgumentException(
                'Missing the required parameter $query when calling getForecast'
            );
        }











        $resourcePath = '/v1/chat/forecast';
        $formParams = [];
        $queryParams = [];
        $headerParams = [];
        $httpBody = '';
        $multipart = false;

        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $query,
            'query', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            true // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $lookback,
            'lookback', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $articles_to_use,
            'articles_to_use', // param base name
            'integer', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $method,
            'method', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $model,
            'model', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $cutoff_date,
            'cutoff_date', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $use_reddit,
            'use_reddit', // param base name
            'boolean', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $additional_context,
            'additional_context', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $web_search,
            'web_search', // param base name
            'boolean', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);
        // query params
        $queryParams = array_merge($queryParams, ObjectSerializer::toQueryValue(
            $expert,
            'expert', // param base name
            'string', // openApiType
            'form', // style
            true, // explode
            false // required
        ) ?? []);




        $headers = $this->headerSelector->selectHeaders(
            ['application/json', ],
            $contentType,
            $multipart
        );

        // for model (json/xml)
        if (count($formParams) > 0) {
            if ($multipart) {
                $multipartContents = [];
                foreach ($formParams as $formParamName => $formParamValue) {
                    $formParamValueItems = is_array($formParamValue) ? $formParamValue : [$formParamValue];
                    foreach ($formParamValueItems as $formParamValueItem) {
                        $multipartContents[] = [
                            'name' => $formParamName,
                            'contents' => $formParamValueItem
                        ];
                    }
                }
                // for HTTP post (form)
                $httpBody = new MultipartStream($multipartContents);

            } elseif (stripos($headers['Content-Type'], 'application/json') !== false) {
                # if Content-Type contains "application/json", json_encode the form parameters
                $httpBody = \GuzzleHttp\Utils::jsonEncode($formParams);
            } else {
                // for HTTP post (form)
                $httpBody = ObjectSerializer::buildQuery($formParams);
            }
        }

        if (!empty($this->config->getAccessToken())) {
            $now = time();
            $buffer = 10;
            if ($this->config->getAccessToken()->expires - $buffer < $now) {
                $this->config->setAccessToken(null);
            }
        }

        if (empty($this->config->getAccessToken()) && !empty($this->config->getClientId()) && !empty($this->config->getClientSecret()) && !empty($this->config->getScopes())) {
            $response = $this->client->send(new Request(
                'POST',
                $this->config->getAuthUrl(),
                [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'User-Agent' => $this->config->getUserAgent(),
                    'Authorization' => 'Basic ' . base64_encode($this->config->getClientId() . ':' . $this->config->getClientSecret())
                ],
                http_build_query([
                    'grant_type' => 'client_credentials',
                    'scope' => implode(' ', $this->config->getScopes()),
                ])
            ));

            $data = json_decode($response->getBody()->getContents());
            $this->config->setAccessToken(
                new AccessToken($data->token_type, $data->access_token, $data->expires_in + time(), $this->config->getScopes())
            );
        }

        if (!empty($this->config->getAccessToken())) {
            $token = $this->config->getAccessToken();
            $headers['Authorization'] = $token->tokenType . ' ' . $token->tokenValue;
        }

        $defaultHeaders = [];
        if ($this->config->getUserAgent()) {
            $defaultHeaders['User-Agent'] = $this->config->getUserAgent();
        }

        $headers = array_merge(
            $defaultHeaders,
            $headerParams,
            $headers
        );

        $operationHost = $this->config->getHost();
        $query = ObjectSerializer::buildQuery($queryParams);
        return new Request(
            'GET',
            $operationHost . $resourcePath . ($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    /**
     * Create http client option
     *
     * @throws \RuntimeException on file opening failure
     * @return array of http client options
     */
    protected function createHttpClientOption()
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: ' . $this->config->getDebugFile());
            }
        }

        return $options;
    }
}
