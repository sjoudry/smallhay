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
  private $access_token;

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
  public function __construct($_client_id, $_client_secret) {
    $this->client_id = $_client_id;
    $this->client_secret = $_client_secret;
  }

  /**
   * Create one or more page assets.
   *
   * Corresponds to the following API endpoint:
   *    POST /pages/<PAGEID>/assets
   *
   * @param int $page_id
   *    contains the page id.
   * @param string $payload
   *    contains the json encoded payload. This is a JSON object with a list
   *    of page assets. The "input" value is a base64 encoded string.
   *    For example:
   *      {
   *        "assets": [
   *          {
   *            "type": "javascript",
   *            "input": "TG9uZyBiYXNlIDY0IGVuY29kZWQgc3RyaW5n"
   *          },
   *          {
   *            "type": "javascript",
   *            "input": "QW5vdGhlciBMb25nIGJhc2UgNjQgZW5jb2RlZCBzdHJpbmc="
   *          }
   *        ]
   *      }
   *
   * @return object
   *    contains the response object.
   *    For example:
   *      {
   *        "page": "10",
   *        "assets": {
   *          "224": {
   *            "id": "224",
   *            "type": "javascript",
   *            "input": "TG9uZyBiYXNlIDY0IGVuY29kZWQgc3RyaW5n",
   *            "output": null,
   *            "created": "1551028825",
   *            "completed": null,
   *            "status": "0"
   *          },
   *          "225": {
   *            "id": "225",
   *            "type": "javascript",
   *            "input": "QW5vdGhlciBMb25nIGJhc2UgNjQgZW5jb2RlZCBzdHJpbmc=",
   *            "output": null,
   *            "created": "1551028825",
   *            "completed": null,
   *            "status": "0"
   *          }
   *        }
   *      }
   */
  public function create_page_assets($page_id, $payload) {
    return $this->post('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Create one or more pages.
   *
   * Corresponds to the following API endpoint:
   *    POST /pages
   *
   * @param string $payload
   *    contains the json encoded payload. This will be a JSON array of paths.
   *    For example:
   *      ["/", "/contact"]
   *
   * @return object
   *    contains the response object. This will be a JSON object with a list
   *    of the pages that were created.
   *    For example:
   *      {
   *        "pages": {
   *          "4": {
   *            "id": "4",
   *            "path": "/",
   *            "created": "1549308922"
   *          },
   *          "5": {
   *            "id": "5",
   *            "path": "/contact",
   *            "created": "1549308922"
   *          }
   *        }
   *      }
   */
  public function create_pages($payload) {
    return $this->post('pages', $payload);
  }

  /**
   * Delete a page.
   *
   * Corresponds to the following API endpoint:
   *    DELETE /pages/<PAGEID>
   *
   * @param int $page_id
   *    contains the page id.
   *
   * @return object
   *    contains the response object. This will be a JSON object with a list
   *    of the pages that were deleted.
   *    For example:
   *      {
   *        "pages": {
   *          "4": {
   *            "id": "4",
   *            "path": "/",
   *            "created": "1549308922"
   *          }
   *        }
   *      }
   */
  public function delete_page($page_id) {
    return $this->delete('pages/' . $page_id);
  }

  /**
   * Delete page assets.
   *
   * Corresponds to the following API endpoint:
   *    DELETE /pages/<PAGEID>/assets?type=<TYPE>
   *
   * @param int $page_id
   *    contains the page id.
   * @param string $payload
   *    contains the json encoded payload.
   *
   * @return object
   *    contains the response object.
   *    For example:
   *      {
   *        "page": "10",
   *        "assets": {
   *          "224": {
   *            "id": "224",
   *            "type": "javascript",
   *            "input": "TG9uZyBiYXNlIDY0IGVuY29kZWQgc3RyaW5n",
   *            "output": null,
   *            "created": "1551028825",
   *            "completed": null,
   *            "status": "0"
   *          },
   *          "225": {
   *            "id": "225",
   *            "type": "javascript",
   *            "input": "QW5vdGhlciBMb25nIGJhc2UgNjQgZW5jb2RlZCBzdHJpbmc=",
   *            "output": null,
   *            "created": "1551028825",
   *            "completed": null,
   *            "status": "0"
   *          }
   *        }
   *      }
   */
  public function delete_page_assets($page_id, $payload) {
    return $this->delete('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Delete one or more pages.
   *
   * Corresponds to the following API endpoint:
   *    DELETE /pages
   *
   * @param string $payload
   *    contains the json encoded payload.
   *    For example:
   *      [15,16]
   *
   * @return object
   *    contains the response object. This will be a JSON object with a list
   *    of the pages that were deleted.
   *    For example:
   *      {
   *        "pages": {
   *          "4": {
   *            "id": "4",
   *            "path": "/",
   *            "created": "1549308922"
   *          }
   *        }
   *      }
   */
  public function delete_pages($payload) {
    return $this->delete('pages', $payload);
  }

  /**
   * Get access token.
   *
   * @return string|null
   *    contains the access token value.
   */
  public function get_access_token() {
    return $this->access_token;
  }

  /**
   * Get access token expires.
   *
   * @return int
   *    contains the access token expires value.
   */
  public function get_access_token_expires() {
    return $this->access_token_expires;
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
   * Corresponds to the following API endpoint:
   *    GET /pages/<PAGEID>
   *
   * @param int $page_id
   *    contains the page id.
   *
   * @return object
   *    contains the response object. This will be a JSON object with a list
   *    of the pages that was requested.
   *    For example:
   *      {
   *        "pages": {
   *          "4": {
   *            "id": "4",
   *            "path": "/",
   *            "created": "1549308922"
   *          }
   *        }
   *      }
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
   *      For example:
   *        {
   *          "page": "10",
   *          "assets": {
   *            "1": {
   *              "id": "1",
   *              "type": "javascript",
   *              "input": "Long base 64 encoded string (modified)",
   *              "output": null,
   *              "created": "1549311047",
   *              "completed": null,
   *              "status": "0"
   *            },
   *            "2": {
   *              "id": "2",
   *              "type": "javascript",
   *              "input": "Another Long base 64 encoded string (modified)",
   *              "output": null,
   *              "created": "1549311047",
   *              "completed": null,
   *              "status": "0"
   *            }
   *          },
   *          "links": {
   *            "previous": "https://api.smallhay.com/v1/pages/10/assets?type=all&offset=0&limit=100"
   *            "current": "https://api.smallhay.com/v1/pages/10/assets?type=all&offset=100&limit=100"
   *            "next": "https://api.smallhay.com/v1/pages/10/assets?type=all&offset=200&limit=100"
   *          }
   *        }
   */
  public function get_page_assets($page_id, $asset_type = 'all', $offset = 0, $limit = 100) {
    return $this->get('pages/' . $page_id . '/assets?type=' . $asset_type . '&offset=' . $offset . '&limit=' . $limit);
  }

  /**
   * Get pages.
   *
   * Corresponds to the following API endpoint:
   *    GET /pages?offset=<OFFSET>&limit=<LIMIT>
   *
   * @param int $offset
   *    contains the offset for pagination.
   * @param int $limit
   *    contains the limit for pagination.
   *
   * @return object
   *    contains the response object. This will be a JSON object with a list
   *    of the pages. This list will be limited by the MAXIMUM_LIMIT and
   *    pagination can be achieved with the limit and offset query params. Also
   *    a list of pagination links will be included in the response.
   *    For example:
   *      {
   *        "pages": {
   *          "4": {
   *            "id": "4",
   *            "path": "/",
   *            "created": "1549308922"
   *          }
   *        },
   *        "links": {
   *          "previous": "PREVIOUS_LINK",
   *          "current": "CURRENT_LINK",
   *          "next": "NEXT_LINK"
   *        }
   *      }
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
   *    PUT /pages
   *
   * @param int $page_id
   *    contains the page id.
   * @param string $payload
   *    contains the json encoded payload. This will be a JSON object with the
   *    path as a single property.
   *    For example:
   *      {
   *        "path": "/contact/old"
   *      }
   *
   * @return object
   *    contains the response object. This will be a JSON object with a list
   *    of the pages that were updated.
   *    For example:
   *      {
   *        "pages": {
   *          "4": {
   *            "id": "4",
   *            "path": "/contact/old",
   *            "created": "1549308922"
   *          }
   *        }
   *      }
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
   *      For example:
   *        {
   *          "assets": {
   *            "1": {
   *              "input": "TG9uZyBiYXNlIDY0IGVuY29kZWQgc3RyaW5n"
   *            },
   *            "2": {
   *              "input": "QW5vdGhlciBMb25nIGJhc2UgNjQgZW5jb2RlZCBzdHJpbmc="
   *            }
   *          }
   *        }
   *
   * @return object
   *     contains the response object.
   *     For example:
   *      {
   *        "page": "10",
   *        "assets": {
   *          "8": {
   *            "id": "8",
   *            "type": "javascript",
   *            "input": "TG9uZyBiYXNlIDY0IGVuY29kZWQgc3RyaW5n",
   *            "output": null,
   *            "created": "1549311967",
   *            "completed": null,
   *            "status": "0"
   *          },
   *          "9": {
   *            "id": "9",
   *            "type": "javascript",
   *            "input": "TG9uZyBiYXNlIDY0IGVuY29kZWQgc3RyaW5n",
   *            "output": null,
   *            "created": "1549313126",
   *            "completed": null,
   *            "status": "0"
   *          }
   *        }
   *      }
   */
  public function update_page_assets($page_id, $payload) {
    return $this->put('pages/' . $page_id . '/assets', $payload);
  }

  /**
   * Update one or more pages.
   *
   * Corresponds to the following API endpoint:
   *      PUT /pages
   *
   * @param string $payload
   *      contains the json encoded payload.
   *      For example:
   *        {
   *          "pages": {
   *            "20": {
   *              "path": "/new"
   *            },
   *            "21": {
   *              "path": "/newer"
   *            }
   *          }
   *        }
   *
   * @return object
   *      contains the response object.
   *      For example:
   *        {
   *          "pages": {
   *            "20": {
   *              "id": "20",
   *              "path": "/new",
   *              "created": "1549400458"
   *            },
   *            "21": {
   *              "id": "21",
   *              "path": "/newer",
   *              "created": "1549400458"
   *            }
   *          }
   *        }
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
   * Get auth.
   *
   * Handles the authorization for all calls. If the access token is missing or
   *    expired, retrieve a new one. The authorization endpoint is essentially
   *    an oAuth authorization using the client_credentials grant type.
   *    Corresponds to the following API endpoint:
   *      POST /auth
   *
   * @return bool
   *    contains the authorization status:
   *      TRUE  = authorized
   *      FALSE = unauthorized
   */
  private function get_auth() {
    $access_token_retrieved = FALSE;

    if (!isset($this->access_token) || ($this->access_token_expires > 0 && $this->access_token_expires <= time())) {
      $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
        'Content-Length: 0',
      );

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, self::API_RESOURCE . '/auth');
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
          $this->access_token = $json->access_token;
          $this->access_token_expires = time() + ($json->expires - $json->created);
        }
      }

      curl_close($ch);
    }

    if (isset($this->access_token) && $this->access_token_expires > time()) {
      $access_token_retrieved = TRUE;
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
