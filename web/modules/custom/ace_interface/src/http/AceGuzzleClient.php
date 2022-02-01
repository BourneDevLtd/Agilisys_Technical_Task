<?php

namespace Drupal\ace_interface\http;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * AceGuzzleClient.
 *
 * Utilizes the Guzzle HTTP Client.
 */
class AceGuzzleClient {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * GuzzleHttpClient $client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * GuzzleHttp $lastRequest.
   *
   * @var \GuzzleHttp\Psr7\Request
   */
  protected $lastRequest;

  /**
   * Last Body $lastBody.
   *
   * @var string
   */
  protected $lastBody;

  /**
   * Last Response $lastResponse.
   *
   * @var string
   */
  protected $lastResponse;

  /**
   * Last Code $lastCode.
   *
   * @var string
   */
  protected $lastCode;

  /**
   * Time Start $timeStart.
   *
   * @var string
   */
  protected $timeStart;

  /**
   * Time End $timeEnd.
   *
   * @var string
   */
  protected $timeEnd;

  /**
   * Time $time.
   *
   * @var string
   */
  protected $time;

  /**
   * Last Error Message $lastErrorMessage.
   *
   * @var string
   */
  protected $lastErrorMessage;

  /**
   * Last User Friendly Error $lastUserFriendlyError.
   *
   * @var string
   */
  protected $lastUserFriendlyError;

  /**
   * Config $config.
   *
   * @var string
   */
  protected $config;

  /**
   * Configuration Factory $configFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AceGuzzleClient.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger, RendererInterface $renderer, MessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->renderer = $renderer;
    $this->messenger = $messenger;
    $this->lastRequest = NULL;
    $this->lastBody = NULL;
    $this->config = $config_factory->get('ace_interface.settings');
  }

  /**
   * Add Comments.
   */
  public function base($base_uri) {
    $this->client = new Client(['base_uri' => $base_uri]);
  }

  /**
   * Add Comments.
   */
  public function setConfigFromContainer($config_container) {
    $this->config = $this->configFactory->get($config_container);
  }

  /**
   * Add Comments.
   */
  private function initialise() {
    $this->lastBody = NULL;
    $this->lastRequest = NULL;
    $this->lastResponse = NULL;
    $this->lastCode = NULL;
  }

  /**
   * Add Comments.
   */
  public function get($uri, $options, $debug = FALSE, $catch_not_found = TRUE) {

    return $this->request('GET', $uri, $options, $debug, $catch_not_found);

  }

  /**
   * Add Comments.
   */
  public function post($uri, $options, $body, $debug = FALSE, $catch = TRUE) {

    $options['body'] = $body;
    return $this->request('POST', $uri, $options, $debug, $catch);

  }

  /**
   * Add Comments.
   */
  public function patch($uri, $options, $body, $debug = FALSE) {

    $options['body'] = $body;
    return $this->request('PATCH', $uri, $options, $debug);

  }

  /**
   * Add Comments.
   */
  public function put($uri, $options, $body, $debug = FALSE, $catch = TRUE) {

    $options['body'] = $body;
    return $this->request('PUT', $uri, $options, $debug, $catch);

  }

  /**
   * Add Comments.
   */
  public function getLastRequest() {
    $request = $this->lastRequest;
    $this->lastRequest = NULL;
    return $request;
  }

  /**
   * Add Comments.
   */
  public function getLastCode() {
    $code = $this->lastCode;
    $this->lastCode = NULL;
    return $code;
  }

  /**
   * Add Comments.
   */
  public function getLastBody() {
    $body = $this->lastBody;
    $this->lastBody = NULL;
    return $body;
  }

  /**
   * Add Comments.
   */
  public function getLastResponse() {
    $response = $this->lastResponse;
    $this->lastResponse = NULL;
    return $response;
  }

  /**
   * Add Comments.
   */
  public function getLastErrorMessage() {
    $message = $this->lastErrorMessage;
    $this->lastErrorMessage = NULL;

    return $message;
  }

  /**
   * Add Comments.
   */
  public function getLastUserFriendlyMessage() {
    $message = $this->lastUserFriendlyError;
    $this->lastUserFriendlyError = NULL;

    if (!$message) {
      $message = 'An unknown error has occurred';
    }

    return $message;
  }

  /**
   * Add Comments.
   */
  public function buildBody($params) {
    $body = '';
    $counter = 1;
    $count = count($params);
    foreach ($params as $key => $value) {
      $body .= $key . '=' . $value;
      if ($counter < $count) {
        $body .= '&';
      }
      $counter++;
    }
    return $body;
  }

  /**
   * Add Comments.
   */
  private function go() {
    $this->timeStart = microtime(TRUE);

  }

  /**
   * Add Comments.
   */
  private function stop() {
    $this->timeEnd = microtime(TRUE);
    // Difference.
    $this->time = $this->timeEnd * 1000 - $this->timeStart * 1000;
    // Milliseconds.
    $this->time = round($this->time, 2);

  }

  /**
   * Add Comments.
   */
  public function request($method, $uri, $options, $debug = FALSE, $catch = TRUE) {

    // Clear out old data.
    $this->initialise();

    if (isset($options['body'])) {
      $this->lastBody = $options['body'];
    }

    try {

      // Add full on debugging useful do not delete, just uncomment when needed.
      $guzzle_debug = $this->configFactory->get('ace_interface.settings')
        ->get('debug');
      if ($guzzle_debug) {
        $options['debug'] = TRUE;
      }

      // Start timer.
      $this->go();

      // Call the request and get the response.
      $this->lastResponse = $this->client->request($method, $uri, $options);

      $this->lastCode = $this->lastResponse->getStatusCode();

      // Stop timer.
      $this->stop();

      $this->log($method, $uri, $options, $this->lastCode, $debug, $type = 'info');

      return $this->lastResponse;

    }
    catch (\Throwable $e) {

      // Stop timer.
      $this->stop();

      $this->lastCode = $e->getCode();

      $real = TRUE;

      $type = 'error';

      // If we are not catching 404s as errors then check this.
      if (!$catch && $this->lastCode == '404') {
        $real = FALSE;
        $type = 'info';
      }

      if (!$catch && $this->lastCode == '0') {
        $real = FALSE;
        $type = 'info';
      }

      if (!$catch && $this->lastCode == '400') {
        $real = FALSE;
        $type = 'info';
      }

      if (!$catch && $this->lastCode == '401') {
        $real = FALSE;
        $type = 'info';
      }

      if (!$catch && $this->lastCode == '403') {
        $real = FALSE;
        $type = 'info';
      }

      if (!$catch && $this->lastCode == '500') {
        $real = FALSE;
        $type = 'info';
      }

      if ($real) {
        $this->logger->get('Guzzle')->error($e->getMessage());

        $this->messenger->addError($this->t('An system error has occurred.  Do not continue and please contact support quoting error reference DD-1421.'));
      }

      $options['error']['message'][] = $e->getMessage();

      $this->log($method, $uri, $options, $this->lastCode, $debug, $type);

      $this->lastResponse = $e->getResponse();

      $error_body = ($this->lastResponse) ? json_decode($this->lastResponse->getBody()) : NULL;

      $this->lastErrorMessage = $e->getMessage();

      if ($error_body) {
        if (property_exists($error_body, 'errors') && isset($error_body->errors[0]) && property_exists($error_body->errors[0], 'code')) {

          $code = $error_body->errors[0]->code;

          $config = $this->configFactory()
            ->get('ace_interface_error_library.settings');

          if ($config) {
            $message = $config->get($code);

            if (!$message) {
              $message = 'An unknown error occurred.';
            }
            $this->lastErrorMessage = $message;
            $this->lastUserFriendlyError = $message;
          }
        }
        else {
          // Workaround until the remaining API specific error codes are set.
          if (isset($error_body->reason)) {
            $this->lastErrorMessage = $error_body->reason;
          }
          else {
            if (isset($error_body->errors[0])) {
              $this->lastErrorMessage = $error_body->errors[0];
            }
            else {
              if (is_string($error_body)) {
                $this->lastErrorMessage = $error_body;
              }
            }
          }
        }
      }
      return NULL;
    }
  }

  /**
   * Add Comments.
   */
  private function log($method, $uri, $options, $code, $debug, $type = 'info') {
    // Is the trace enabled.
    $trace = $this->configFactory->get('ace_interface.settings')->get('trace');

    // Is debug enabled.
    if ($debug) {
      $trace = TRUE;
    }

    // Trace errors.
    if ($type == 'error') {
      $trace = TRUE;
    }

    // If trace then call the trace function.
    if ($trace) {
      $this->trace($method, $uri, $options, $code);
    }
  }

  /**
   * Add Comments.
   */
  private function trace($method, $uri, $options, $code) {
    // Remove curl.
    if (isset($options['curl'])) {
      unset($options['curl']);
    }

    // Move body down the presentation order.
    if (isset($options['body'])) {
      $body = $options['body'];
      unset($options['body']);
    }
    else {
      $body = NULL;
    }

    // Move header down the presentation order.
    if (isset($options['headers'])) {
      $headers = $options['headers'];
      unset($options['headers']);
    }
    else {
      $headers = NULL;
    }

    // Track time taken, URI called and the headers sent
    // Title.
    $title = $method . ' ' . $code . ' took ' . $this->time . 'ms for ' . $uri;
    $build = [
      '#type' => 'details',
      '#title' => $title,
    ];
    $build['response_header']['start'] = '';

    // Add request information.
    $options['method'] = $method;
    $options['code'] = $code;
    $options['time'] = $this->time . 'ms';
    $options['uri'] = $uri;

    // Reattached body.
    if ($body) {
      $options['body'] = $body;
    }

    /*
     * Remove a bunch of HTTP headers that are standard and not needed
     */
    foreach ($headers as $key => $value) {
      switch ($key) {
        case 'Accept':
        case 'If-None_Match':
        case 'OData-MaxVersion':
        case 'OData-Version':
        case 'cache-control':
        case 'Connection':
          unset($headers[$key]);
          break;

        case 'Authorization':

          $build['authorization'] = [
            '#type' => 'details',
            '#title' => 'Bearer Token',
          ];
          $build['authorization']['Base64'] = [
            '#title' => 'Base64 encoded bearer token',
            '#type' => 'textarea',
            '#rows' => 10,
            '#cols' => 100,
            '#value' => $value,
          ];

          unset($headers[$key]);
          break;
      }
    }

    $options['headers'] = $headers;

    if (isset($options['body'])) {
      try {

        $build['body'] = [
          '#type' => 'details',
          '#title' => 'HTTP Body',
        ];

        $build['body']['raw'] = [
          '#title' => 'Body',
          '#type' => 'textarea',
          '#rows' => 5,
          '#cols' => 100,
          '#value' => $options["body"],
        ];

        $base64 = base64_encode($options["body"]);

        $build['body']['base64'] = [
          '#title' => 'Body encoded for safe analysis via /base.php converter',
          '#type' => 'textarea',
          '#rows' => 5,
          '#cols' => 100,
          '#value' => $base64,
        ];

        $options['body'] = json_decode($options['body'], TRUE);

      }
      catch (exception $e) {

        $options['error'][] = $e->getMessage();

      }
    }

    if (isset($options["body"]["cxm_submissiondata"])) {
      try {

        $build['body']['json'] = [
          '#title' => 'Submission data',
          '#type' => 'textarea',
          '#rows' => 5,
          '#cols' => 100,
          '#value' => $options["body"]['cxm_submissiondata'],
        ];

        $submission_base64 = base64_encode($options["body"]['cxm_submissiondata']);

        $build['body']['cxm_submissiondata'] = [
          '#title' => 'Submission data encoded for safe analysis via /base.php converter',
          '#type' => 'textarea',
          '#rows' => 5,
          '#cols' => 100,
          '#value' => $submission_base64,
        ];

        $options["body"]['cxm_submissiondata'] = json_decode($options["body"]['cxm_submissiondata'], TRUE);
        $yaml = Yaml::encode($options["body"]['cxm_submissiondata']);

        $build['body']['beautify'] = [
          '#title' => 'Submission data beautified for easy browsing',
          '#type' => 'textarea',
          '#rows' => 20,
          '#cols' => 100,
          '#value' => $yaml,
        ];

        $options['body']['cxm_submissiondata'] = 'visualised';

      }
      catch (exception $e) {

        $options['error'][] = $e->getMessage();

      }
    }

    if (isset($options["body"]["cxm_tempstore"])) {
      try {
        $build['train'] = [
          '#type' => 'details',
          '#title' => 'XML Train',
        ];
        $build['train']['Base64'] = [
          '#title' => 'BASE64 encoded for safe transport',
          '#type' => 'textarea',
          '#rows' => 5,
          '#cols' => 100,
          '#value' => $options["body"]["cxm_tempstore"],
        ];

        // Decode the temp store.
        $decoded = base64_decode($options["body"]["cxm_tempstore"]);

        $build['train']['XML'] = [
          '#title' => 'Decoded XML Train showing full collection of XML + XSL = Output',
          '#type' => 'textarea',
          '#rows' => 20,
          '#cols' => 100,
          '#value' => $decoded,
        ];

        // Break up the temp store.
        $options['body']['cxm_tempstore'] = 'visualised';

      }
      catch (exception $e) {

        $options['error'][] = $e->getMessage();

      }
    }

    $build['response_header'] = [
      '#type' => 'item',
      '#wrapper_attributes' => ['style' => 'margin: 0'],
      'data' => [
        '#markup' => htmlspecialchars(Yaml::encode($options)),
        '#prefix' => '<pre>',
        '#suffix' => '</pre>',
      ],
    ];

    $this->logger->get('Guzzle')->debug($this->renderer->renderPlain($build));

  }

}
