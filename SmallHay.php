<?php
/**
 * The SmallHay API SDK for PHP.
 *
 * @author Scott Joudry <sj@smallhay.com>
 * @version 1.0
 * @see https://api.smallhay.com/v1
 */

namespace SmallHay;

/**
 * @var string SmallHay SDK Version.
 */
define('SMALLHAY_SDK_VERSION', '1.0');

/**
 * The API class.
 *
 * This class encapsulates all API endpoints in a stand-alone class.
 * Authorization is handled automatically so the class can be instantiated and
 * the proper API call made to create, retrieve, update or delete data.
 */
class API {

  /**
   * @var string API_RESOURCE
   *    contains the resource URI for all API endpoints.
   */
  const API_RESOURCE = 'https://api.smallhay.com/v1';

  /**
   * @var string API_TEST_RESOURCE
   *    contains the resource URI for all API endpoints.
   */
  const API_TEST_RESOURCE = 'https://test-api.smallhay.com/v1';

  /**
   * @var string|null $bearer_token
   *    contains the bearer token returned from the auth call.
   */
  private $bearer_token;

  /**
   * @var int $bearer_token_expires
   *    contains the expiry time of the bearer token returned from the auth
   *    call.
   */
  private $bearer_token_expires = 0;

  /**
   * @var string $client_id
   *    contains the client id of the Small Hay account.
   */
  private $client_id = '';

  /**
   * @var string $client_secret
   *    contains the client secret of the Small Hay account.
   */
  private $client_secret = '';

  /**
   * @var int $curl_connect_timeout
   *    contains the curl connect timeout value in seconds. The default is 2
   *    seconds but this can be be modified.
   *
   * @see set_curl_connect_timeout().
   */
  private $curl_connect_timeout = 2;

  /**
   * @var int $curl_timeout
   *    contains curl timeout value in seconds. The default is 4 seconds but
   *    this can be be modified.
   *
   * @see set_curl_timeout().
   */
  private $curl_timeout = 4;

  /**
   * @var bool $test
   *    contains the flag that controls whether calls are made on the test
   *    resource of the prod resource.
   */
  private $test = FALSE;

  /**
   * @var int $http_code
   *    contains the http status code.
   */
  private $http_code;

  /**
   * API constructor.
   *
   * @param string $_client_id
   *     contains the client id for authorization.
   * @param string $_client_secret
   *     contains the client secret for authorization.
   */
  public function __construct($_client_id, $_client_secret, $_test = FALSE) {
    $this->client_id = $_client_id;
    $this->client_secret = $_client_secret;
    $this->test = $_test;
  }

  /**
   * Create one or more page assets.
   *
   * @param int $page_id
   *    contains the page id.
   * @param string $payload
   *    contains the payload.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/CreateAssets
   */
  public function create_page_assets($page_id, $payload) {
    return $this->post('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Create one or more pages.
   *
   * @param string $payload
   *    contains the payload.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/CreatePages
   */
  public function create_pages($payload) {
    return $this->post('pages', $payload);
  }

  /**
   * Delete a page.
   *
   * @param int $page_id
   *    contains the page id.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/DeletePage
   */
  public function delete_page($page_id) {
    return $this->delete('pages/' . $page_id);
  }

  /**
   * Delete page asset.
   *
   * @param int $page_id
   *    contains the page id.
   * @param int $asset_id
   *    contains the asset id.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/DeleteAsset
   */
  public function delete_page_asset($page_id, $asset_id) {
    return $this->delete('pages/' . $page_id . '/assets/' . $asset_id);
  }

  /**
   * Delete page assets.
   *
   * @param int $page_id
   *    contains the page id.
   * @param string $payload
   *    contains the payload.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/DeleteAssets
   */
  public function delete_page_assets($page_id, $payload) {
    return $this->delete('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Delete one or more pages.
   *
   * @param string $payload
   *    contains the payload.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/DeletePages
   */
  public function delete_pages($payload) {
    return $this->delete('pages', $payload);
  }

  /**
   * Get bearer token.
   *
   * @return string|null
   *    contains the bearer token value.
   */
  public function get_bearer_token() {
    return $this->bearer_token;
  }

  /**
   * Get bearer token expires.
   *
   * @return int
   *    contains the bearer token expires value.
   */
  public function get_bearer_token_expires() {
    return $this->bearer_token_expires;
  }

  /**
   * Get auth.
   *
   * Handles the authorization for all calls. If the access token is missing or
   *    expired, retrieve a new one. The authorization endpoint is uses http
   *    authentication via a bearer token.
   *
   * @return bool
   *    contains the authorization status:
   *      TRUE  = authorized
   *      FALSE = unauthorized
   * 
   * @see https://api.smallhay.com/v1/#operation/Auth
   */
  public function get_auth() {
    $bearer_token_retrieved = FALSE;

    if (!isset($this->bearer_token) || ($this->bearer_token_expires > 0 && $this->bearer_token_expires <= time())) {
      $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
        'Content-Length: 0',
      );

      $ch = curl_init();
      if ($this->test) {
        curl_setopt($ch, CURLOPT_URL, self::API_TEST_RESOURCE . '/auth');
      }
      else {
        curl_setopt($ch, CURLOPT_URL, self::API_RESOURCE . '/auth');
      }
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      $response = curl_exec($ch);
      $info = curl_getinfo($ch);

      // Handle the response.
      $this->http_code = $info['http_code'];
      if ($info['http_code'] == 200) {
        $json = @json_decode($response);
        if (json_last_error() == JSON_ERROR_NONE) {
          $this->bearer_token = $json->bearer_token;
          $this->bearer_token_expires = time() + ($json->expires - $json->created);
        }
      }

      curl_close($ch);
    }

    if (isset($this->bearer_token) && $this->bearer_token_expires > time()) {
      $bearer_token_retrieved = TRUE;
    }

    return $bearer_token_retrieved;
  }

  /**
   * Get client ID.
   *
   * @return string
   *    contains the client id value.
   */
  public function get_client_id() {
    return $this->client_id;
  }

  /**
   * Get client secret.
   *
   * @return string
   *    contains the client secret value.
   */
  public function get_client_secret() {
    return $this->client_secret;
  }

  /**
   * Get curl connect timeout.
   *
   * @return int
   *    contains the timeout value in seconds.
   */
  public function get_curl_connect_timeout() {
    return $this->curl_connect_timeout;
  }

  /**
   * Get curl timeout.
   *
   * @return int
   *    contains the timeout value in seconds.
   */
  public function get_curl_timeout() {
    return $this->curl_timeout;
  }

  /**
   * Get http code.
   *
   * @return mixed
   *    contains the http code value.
   */
  public function get_http_code() {
    return $this->http_code;
  }

  /**
   * Get a page.
   *
   * @param int $page_id
   *    contains the page id.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ListPage
   */
  public function get_page($page_id) {
    return $this->get('pages/' . $page_id);
  }

  /**
   * Get page asset.
   *
   * @param int $page_id
   *     contains the page id.
   * @param int $asset_id
   *     contains the asset id.
   *
   * @return object
   *     contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ListAsset
   */
  public function get_page_asset($page_id, $asset_id) {
    return $this->get('pages/' . $page_id . '/assets/' . $asset_id);
  }

  /**
   * Get page assets.
   * 
   * @param int $page_id
   *     contains the page id.
   * @param string $asset_type
   *     contains the asset type.
   * @param int $offset
   *     contains the offset for pagination.
   * @param int $limit
   *     contains the limit for pagination.
   *
   * @return object
   *     contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ListAssets
   */
  public function get_page_assets($page_id, $asset_type = 'all', $offset = 0, $limit = 100) {
    return $this->get('pages/' . $page_id . '/assets?type=' . $asset_type . '&offset=' . $offset . '&limit=' . $limit);
  }

  /**
   * Get pages.
   *
   * @param int $offset
   *    contains the offset for pagination.
   * @param int $limit
   *    contains the limit for pagination.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ListPages
   */
  public function get_pages($offset = 0, $limit = 100) {
    return $this->get('pages?offset=' . $offset . '&limit=' . $limit);
  }

  /**
   * Get test.
   *
   * @return bool
   *    contains the test value.
   */
  public function get_test() {
    return $this->test;
  }

  /**
   * Set curl connect timeout value.
   *
   * @param int $_curl_connect_timeout
   *     contains the curl connect timeout value in seconds.
   */
  public function set_curl_connect_timeout($_curl_connect_timeout) {
    $this->curl_connect_timeout = $_curl_connect_timeout;
  }

  /**
   * Set curl timeout value.
   *
   * @param int $_curl_timeout
   *     contains the curl timeout value in seconds.
   */
  public function set_curl_timeout($_curl_timeout) {
    $this->curl_timeout = $_curl_timeout;
  }

  /**
   * Update page.
   *
   * @param int $page_id
   *    contains the page id.
   * @param string $payload
   *    contains the payload.
   *
   * @return object
   *    contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ModifyPage
   */
  public function update_page($page_id, $payload) {
    return $this->put('pages/' . $page_id, $payload);
  }

  /**
   * Update page asset.
   *
   * @param int $page_id
   *     contains the page id.
   * @param int $asset_id
   *     contains the asset id.
   * @param string $payload
   *     contains the payload.
   *
   * @return object
   *     contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ModifyAsset
   */
  public function update_page_asset($page_id, $asset_id, $payload) {
    return $this->put('pages/' . $page_id . '/assets/' . $asset_id, $payload);
  }

  /**
   * Update page assets.
   *
   * @param int $page_id
   *     contains the page id.
   * @param string $payload
   *     contains the payload.
   *
   * @return object
   *     contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ModifyAssets
   */
  public function update_page_assets($page_id, $payload) {
    return $this->put('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Update one or more pages.
   *
   * @param string $payload
   *      contains the payload.
   *
   * @return object
   *      contains the response object.
   * 
   * @see https://api.smallhay.com/v1/#operation/ModifyPages
   */
  public function update_pages($payload) {
    return $this->put('pages', $payload);
  }

  /**
   * Delete.
   *
   * Sends a DELETE curl.
   *
   * @param string $endpoint
   *     contains the endpoint to call.
   * @param string|null $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   */
  private function delete($endpoint, $payload = NULL) {
    return $this->send('delete', $endpoint, $payload);
  }

  /**
   * Get.
   *
   * Sends a GET curl.
   *
   * @param string $endpoint
   *     contains the endpoint to call.
   *
   * @return object|bool
   *     contains the response object.
   */
  private function get($endpoint) {
    return $this->send('get', $endpoint);
  }

  /**
   * Post.
   *
   * Sends a POST curl.
   *
   * @param string $endpoint
   *     contains the endpoint to call.
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object|bool
   *     contains the response object.
   */
  private function post($endpoint, $payload) {
    return $this->send('post', $endpoint, $payload);
  }

  /**
   * Put.
   *
   * Sends a PUT curl.
   *
   * @param string $endpoint
   *     contains the endpoint to call.
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object|bool
   *     contains the response object.
   */
  private function put($endpoint, $payload) {
    return $this->send('put', $endpoint, $payload);
  }

  /**
   * Send.
   *
   * Performs the curl.
   *
   * @param string $type
   *     contains the type of curl - DELETE, GET, POST, PUT
   * @param string $endpoint
   *     contains the endpoint to call.
   * @param string|null $payload
   *     contains the json encoded payload.
   *
   * @return object|bool
   *     contains the response object.
   */
  private function send($type, $endpoint, $payload = NULL) {
    $return = FALSE;
    if ($this->get_auth()) {
      $headers = array('Authorization: Bearer ' . $this->bearer_token);

      $ch = curl_init();
      if ($this->test) {
        curl_setopt($ch, CURLOPT_URL, self::API_TEST_RESOURCE . '/' . $endpoint);
      }
      else {
        curl_setopt($ch, CURLOPT_URL, self::API_RESOURCE . '/' . $endpoint);
      }
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->curl_connect_timeout);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
      switch ($type) {
        case 'delete':
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
          break;

        case 'get':
          curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
          break;

        case 'post':
          curl_setopt($ch, CURLOPT_POST, TRUE);
          break;

        case 'put':
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
          break;
      }

      if (isset($payload)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        $headers[] = 'Content-Type: application/json';
      }
      else {
        $headers[] = 'Content-Length: 0';
      }
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      $response = curl_exec($ch);
      $info = curl_getinfo($ch);

      // Decode the response.
      $this->http_code = $info['http_code'];
      $response = @json_decode($response);

      curl_close($ch);

      $return = $response;
    }

    return $return;
  }
}
