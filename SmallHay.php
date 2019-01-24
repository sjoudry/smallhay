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
  * @var string|null $access_token
  *    contains the access token returned from the auth call.
  */
  private $access_token = NULL;

  /**
  * @var int $access_token_expires
  *    contains the expiry time of the access token returned from the auth
  *    call.
  */
  private $access_token_expires = 0;

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
   * API constructor.
   *
   * @param string $_client_id
   *     contains the client id for authorization.
   * @param string $_client_secret
   *     contains the client secret for authorization.
   */
  public function __construct($_client_id, $_client_secret) {
    $this->client_id = $_client_id;
    $this->client_secret = $_client_secret;
  }

  /**
   * Create one or more page assets.
   *
   * Corresponds to the following API endpoint:
   *     POST /pages/<PAGEID>/assets
   *
   * @param int $page_id
   *     contains the page id.
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
   */
  public function create_page_assets($page_id, $payload) {
    return $this->post('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Create one or more pages.
   *
   * Corresponds to the following API endpoint:
   *     POST /pages
   *
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
   */
  public function create_pages($payload) {
    return $this->post('pages', $payload);
  }

  /**
   * Delete a page.
   *
   * Corresponds to the following API endpoint:
   *     DELETE /pages/<PAGEID>
   *
   * @param int $page_id
   *     contains the page id.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the return object.
   */
  public function delete_page($page_id) {
    return $this->delete('pages/' . $page_id);
  }

  /**
   * Delete page assets.
   *
   * Corresponds to the following API endpoint:
   *     DELETE /pages/<PAGEID>/assets?type=<TYPE>
   *
   * @param int $page_id
   *     contains the page id.
   * @param string $asset_type
   *     contains the asset type. Possible values:
   *       - all (default)
   *       - javascript
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the return object.
   */
  public function delete_page_assets($page_id, $asset_type = 'all') {
    return $this->delete('pages/' . $page_id . '/assets?type=' . $asset_type);
  }

  /**
   * Delete one or more pages.
   *
   * Corresponds to the following API endpoint:
   *     DELETE /pages
   *
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
   */
  public function delete_pages($payload) {
    return $this->delete('pages', $payload);
  }

  /**
   * Get a page.
   *
   * Corresponds to the following API endpoint:
   *     GET /pages/<PAGEID>
   *
   * @param int $page_id
   *     contains the page id.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the return object.
   */
  public function get_page($page_id) {
    return $this->get('pages/' . $page_id);
  }

  /**
   * Get page assets.
   *
   * Corresponds to the following API endpoint:
   *     GET /pages/<PAGEID>/assets?type=<TYPE>&offset=<OFFSET>&limit=<LIMIT>
   *
   * @param int $page_id
   *     contains the page id.
   * @param string $asset_type
   *     contains the asset type. Possible values:
   *       - all (default)
   *       - javascript
   * @param int $offset
   *     contains the offset for pagination.
   * @param int $limit
   *     contains the limit for pagination.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the return object.
   */
  public function get_page_assets($page_id, $asset_type = 'all', $offset = 0, $limit = 100) {
    return $this->get('pages/' . $page_id . '/assets?type=' . $asset_type . '&offset=' . $offset . '&limit=' . $limit);
  }

  /**
   * Get pages.
   *
   * Corresponds to the following API endpoint:
   *     GET /pages?offset=<OFFSET>&limit=<LIMIT>
   *
   * @param int $offset
   *     contains the offset for pagination.
   * @param int $limit
   *     contains the limit for pagination.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the return object.
   */
  public function get_pages($offset = 0, $limit = 100) {
    return $this->get('pages?offset=' . $offset . '&limit=' . $limit);
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
   * Update one or more pages.
   *
   * Corresponds to the following API endpoint:
   *     PUT /pages
   *
   * @param int $page_id
   *     contains the page id.
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
   */
  public function update_page($page_id, $payload) {
    return $this->put('pages/' . $page_id, $payload);
  }

  /**
   * Update page assets.
   *
   * Corresponds to the following API endpoint:
   *     PUT /pages/<PAGEID>/assets
   *
   * @param int $page_id
   *     contains the page id.
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
   */
  public function update_page_assets($page_id, $payload) {
    return $this->put('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Update one or more pages.
   *
   * Corresponds to the following API endpoint:
   *     PUT /pages
   *
   * @param string $payload
   *     contains the json encoded payload.
   *
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
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
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
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
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the return object.
   */
  private function get($endpoint) {
    return $this->send('get', $endpoint);
  }

  /**
   * Get access token.
   *
   * Handles the authorization for all calls. If the access token is missing or
   *     expired, retrieve a new one. The authorization endpoint is essentially
   *     an oAuth authorization using the client_credentials grant type.
   *     Corresponds to the following API endpoint:
   *         POST /auth
   *
   * @return bool
   *     contains the authorization status:
   *         TRUE = authorized
   *         FALSE = unauthorized
   *
   * @todo handle response and set errors for later use.
   */
  private function get_access_token() {
    $access_token_retrieved = FALSE;

    if (!isset($this->access_token) || $this->access_token_expires >= time()) {
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, self::API_RESOURCE . '/auth');
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('client_id' => $this->client_id, 'client_secret' => $this->client_secret)));
      curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//      $response = curl_exec($ch);
//      $info = curl_getinfo($ch);
      curl_close($ch);

      // handle errors and response.
    }

    return $access_token_retrieved;
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
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
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
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
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
   * @return object
   *     contains the response object.
   *
   * @todo Document the options in the payload object.
   * @todo Document the options in the return object.
   * @todo handle response and set errors for later use.
   */
  private function send($type, $endpoint, $payload = NULL) {
    if ($this->get_access_token()) {
      $headers = array('Authorization: Bearer ' . $this->access_token);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, self::API_RESOURCE . '/' . $endpoint);
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
//      $response = curl_exec($ch);
//      $info = curl_getinfo($ch);
      curl_close($ch);

      // handle errors and response.
    }
  }
}
