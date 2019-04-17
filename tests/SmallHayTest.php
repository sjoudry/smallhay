<?php

require_once __DIR__ . '/../SmallHay.php';

use PHPUnit\Framework\TestCase;
use SmallHay\API;

/**
 * Class SmallHayTest.
 */
class SmallHayTest extends TestCase {

  /**
   * @var SmallHay\API $smallhay
   *    contains the smallhay object used for all test calls (other than those
   *    used to test the auuth calls).
   */
  protected $smallhay;

  /**
   * @var string $client_id
   *    contains the demo client id.
   */
  protected $client_id = 'demo';

  /**
   * @var string $client_secret
   *    contains the demo client secret.
   */
  protected $client_secret = 'demo';

  /**
   * Set up
   */
  protected function setUp() {
    $this->smallhay = new API($this->client_id, $this->client_secret);
  }

  /**
   * Test 1 - object properties.
   */
  public function testProperties() {

    // Client credentials are set.
    $this->assertEquals($this->client_id, $this->smallhay->get_client_id());
    $this->assertEquals($this->client_secret, $this->smallhay->get_client_secret());

    // Access token is not set.
    $this->assertNull($this->smallhay->get_access_token());
    $this->assertEquals($this->smallhay->get_access_token_expires(), 0);

    // Curl values are default.
    $this->assertEquals($this->smallhay->get_curl_connect_timeout(), 2);
    $this->assertEquals($this->smallhay->get_curl_timeout(), 4);

    // Change the curl values and test again.
    $this->smallhay->set_curl_connect_timeout(4);
    $this->smallhay->set_curl_timeout(2);
    $this->assertEquals($this->smallhay->get_curl_connect_timeout(), 4);
    $this->assertEquals($this->smallhay->get_curl_timeout(), 2);
  }

  /**
   * Test 2 - auth.
   */
  public function testAuth() {

    // Create a new smallhay object with invalid credentials.
    $bad_smallhay = new API('bad_demo', 'bad_demo');

    // Create two pages, this fails the auth call in send().
    $response = $bad_smallhay->create_pages($this->_getJSONArrayPaths());
    $this->assertFalse($response);

    // Valid auth call
    $this->smallhay->create_pages($this->_getJSONArrayPaths());
    $this->assertEquals($this->smallhay->get_http_code(), 200);
    $this->assertNotNull($this->smallhay->get_access_token());
    $this->assertNotEquals($this->smallhay->get_access_token_expires(), 0);
  }

  /**
   * Test 3 - create pages errors.
   */
  public function testCreatePagesErrors() {

    // invalid JSON.
    $response = $this->smallhay->create_pages($this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing path data.
    $response = $this->smallhay->create_pages($this->_getJSONArrayEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->create_pages($this->_getJSONArrayPathsTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // invalid path - non-string.
    $response = $this->smallhay->create_pages($this->_getJSONArrayPathsBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - missing leading /.
    $response = $this->smallhay->create_pages($this->_getJSONArrayPathsString());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 4 - modify pages errors.
   */
  public function testModifyPagesErrors() {

    // invalid JSON.
    $response = $this->smallhay->update_pages($this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing pages data.
    $response = $this->smallhay->update_pages($this->_getJSONObjectEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->update_pages($this->_getJSONObjectPagesTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // non-numeric ids.
    $response = $this->smallhay->update_pages($this->_getJSONObjectPagesIDMissing());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing path.
    $response = $this->smallhay->update_pages($this->_getJSONObjectPagesPathMissing());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - missing leading /.
    $response = $this->smallhay->update_pages($this->_getJSONObjectPagesPathString());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - non-string.
    $response = $this->smallhay->update_pages($this->_getJSONObjectPagesPathBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 5 - delete pages errors.
   */
  public function testDeletePagesErrors() {

    // invalid JSON.
    $response = $this->smallhay->delete_pages($this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing page id.
    $response = $this->smallhay->delete_pages($this->_getJSONArrayEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->delete_pages($this->_getJSONArrayPathsTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // invalid page ids.
    $response = $this->smallhay->delete_pages($this->_getJSONArrayPathsString());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 6 - pages.
   */
  public function testPagesCalls() {

    // Create two pages.
    $response_create = $this->smallhay->create_pages($this->_getJSONArrayPaths());
    $this->_assertSuccess($response_create);
    $this->_assertAttributes($response_create, array('pages'), array('links'));
    $this->assertEquals(count(get_object_vars($response_create->pages)), 1);
    foreach ($response_create->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
      $response_create->pages->{$page_id}->path .= '/new';
    }

    // Modify two pages.
    $response_modify = $this->smallhay->update_pages(json_encode($response_create));
    $this->_assertSuccess($response_modify);
    $this->_assertAttributes($response_modify, array('pages'), array('links'));
    $this->assertEquals(count(get_object_vars($response_modify->pages)), 1);
    $this->assertEquals($response_create, $response_modify);
    foreach ($response_modify->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }

    // Retrieve pages.
    $response_list = $this->smallhay->get_pages();
    $this->_assertSuccess($response_list);
    $this->_assertAttributes($response_list, array('pages', 'links'));
    $this->assertGreaterThanOrEqual(2, count(get_object_vars($response_list->pages)));
    foreach ($response_list->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }

    // Delete both pages.
    $response_delete = $this->smallhay->delete_pages(json_encode(array_keys(get_object_vars($response_modify->pages))));
    $this->_assertSuccess($response_delete);
    $this->_assertAttributes($response_delete, array('pages'), array('links'));
    $this->assertEquals(count(get_object_vars($response_delete->pages)), 1);
    $this->assertEquals($response_delete, $response_modify);
    foreach ($response_delete->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }
  }

  /**
   * Test 7 - list page errors.
   */
  public function testListPageErrors() {

    // invalid id.
    $response = $this->smallhay->get_page(0);
    $this->_assertError($response, 'SH-v1-011', 404);
  }

  /**
   * Test 8 - modify page errors.
   */
  public function testModifyPageErrors() {

    // modify pages - invalid id.
    $response = $this->smallhay->update_page(0, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);

    // Create a page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid JSON.
    $response = $this->smallhay->update_page($created_id, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing path.
    $response = $this->smallhay->update_page($created_id, $this->_getJSONObjectEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - non-string.
    $response = $this->smallhay->update_page($created_id, $this->_getJSONObjectPagePathBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - missing leading /.
    $response = $this->smallhay->update_page($created_id, $this->_getJSONObjectPagePathString());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 9 - delete page errors.
   */
  public function testDeletePageErrors() {

    // invalid id.
    $response = $this->smallhay->delete_page(0);
    $this->_assertError($response, 'SH-v1-011', 404);
  }

  /**
   * Test 10 - page.
   */
  public function testPageCalls() {

    // Create a page.
    list($created_id, $response_create) = $this->_createSinglePage();

    // Modify the page.
    $response_create->pages->{$created_id}->path .= '/new';
    $response_modify = $this->smallhay->update_page($created_id, json_encode($response_create->pages->{$created_id}));
    $this->_assertSuccess($response_modify);
    $this->_assertAttributes($response_modify, array('pages'), array('links'));
    $this->assertEquals(count(get_object_vars($response_modify->pages)), 1);
    $this->assertEquals($response_create, $response_modify);
    foreach ($response_modify->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }

    // Retrieve page.
    $response_list = $this->smallhay->get_page($created_id);
    $this->_assertSuccess($response_list);
    $this->_assertAttributes($response_list, array('pages'), array('links'));
    $this->assertEquals(count(get_object_vars($response_list->pages)), 1);
    foreach ($response_list->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }

    // Delete both pages.
    $response_delete = $this->smallhay->delete_page($created_id);
    $this->_assertSuccess($response_delete);
    $this->_assertAttributes($response_delete, array('pages'), array('links'));
    $this->assertEquals(count(get_object_vars($response_delete->pages)), 1);
    $this->assertEquals($response_delete, $response_modify);
    foreach ($response_delete->pages as $page_id => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }
  }

  /**
   * Test 11 - list page asset errors.
   */
  public function testListPageAssetErrors() {

    // invalid page id.
    $response = $this->smallhay->get_page_asset(0, 0);
    $this->_assertError($response, 'SH-v1-011', 404);

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid asset id.
    $response = $this->smallhay->get_page_asset($created_id, 0);
    $this->_assertError($response, 'SH-v1-011', 404);
}

  /**
   * Test 12 - modify page asset errors.
   */
  public function testModifyPageAssetErrors() {

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid id.
    $response = $this->smallhay->update_page_assets(0, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid JSON.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing assets data.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // missing input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectInputMissing());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input - non-string.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectInputBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectInputString());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid page asset ids
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsIdInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);
  }

  /**
   * Test 13 - delete page asset errors.
   */
  public function testDeletePageAssetErrors() {

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid id.
    $response = $this->smallhay->delete_page_asset($created_id, 0);
    $this->_assertError($response, 'SH-v1-011', 404);
  }

  /**
   * Test 14 - page asset.
   */
  public function testPageAssetCalls() {

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // create page assets.
    $response_create = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAsset());
    $this->_assertSuccess($response_create);
    $this->_assertAttributes($response_create, array('assets', 'page'), array('links'));
    $this->assertEquals(count(get_object_vars($response_create->assets)), 1);
    $created_asset_id = 0;
    foreach ($response_create->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
      $response_create->assets->{$asset_id}->input = base64_encode(base64_decode($asset->input) . 'new');
      $created_asset_id = $asset_id;
    }

    // modify page asset.
    $response_modify = $this->smallhay->update_page_asset($created_id, $created_asset_id, json_encode($response_create->assets->{$created_asset_id}));
    $this->_assertSuccess($response_modify);
    $this->_assertAttributes($response_modify, array('assets', 'page'), array('links'));
    $this->assertEquals(count(get_object_vars($response_modify->assets)), 1);
    $this->assertEquals($response_create->assets->{$created_asset_id}, $response_modify->assets->{$created_asset_id});
    foreach ($response_modify->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }

    // list page asset.
    $response_list = $this->smallhay->get_page_asset($created_id, $created_asset_id);
    $this->_assertSuccess($response_list);
    $this->_assertAttributes($response_list, array('assets', 'page'), array('links'));
    $this->assertEquals(1, count(get_object_vars($response_list->assets)));
    foreach ($response_list->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }

    // delete page asset.
    $response_delete = $this->smallhay->delete_page_asset($created_id, $created_asset_id);
    $this->_assertSuccess($response_delete);
    $this->_assertAttributes($response_delete, array('assets', 'page'), array('links'));
    $this->assertEquals(count(get_object_vars($response_delete->assets)), 1);
    $this->assertEquals($response_delete, $response_list);
    foreach ($response_delete->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }
  }

  /**
   * Test 15 - create page assets errors.
   */
  public function testCreatePageAssetsErrors() {

    // invalid id.
    $response = $this->smallhay->create_page_assets(0, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid JSON.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing assets data.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssetsArrayTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // invalid type.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssetsTypeInvalid());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssetsInputString());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssetsInputBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing type.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssetsTypeMissing());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing input.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssetsInputMissing());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 16 - list page assets errors.
   */
  public function testListPageAssetsErrors() {

    // invalid id.
    $response = $this->smallhay->get_page_assets(0);
    $this->_assertError($response, 'SH-v1-011', 404);
}

  /**
   * Test 17 - modify page assets errors.
   */
  public function testModifyPageAssetsErrors() {

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid id.
    $response = $this->smallhay->update_page_assets(0, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid JSON.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing assets data.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // missing input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectInputMissing());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input - non-string.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectInputBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsObjectInputString());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid page asset ids
    $response = $this->smallhay->update_page_assets($created_id, $this->_getJSONObjectPageAssetsIdInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);
  }

  /**
   * Test 18 - delete page assets errors.
   */
  public function testDeletePageAssetsErrors() {

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // invalid id.
    $response = $this->smallhay->delete_page_assets(0, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid JSON.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getJSONInvalid());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing page asset id.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getJSONArrayEmpty());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getJSONArrayPathsTooMany());
    $this->_assertError($response, 'SH-v1-010', 500);

    // invalid page ids.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getJSONArrayPathsString());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 19 - page assets.
   */
  public function testPageAssetsCalls() {

    // Create a single page.
    $created = $this->_createSinglePage();
    $created_id = array_shift($created);

    // create page assets.
    $response_create = $this->smallhay->create_page_assets($created_id, $this->_getJSONObjectPageAssets());
    $this->_assertSuccess($response_create);
    $this->_assertAttributes($response_create, array('assets', 'page'), array('links'));
    $this->assertEquals(count(get_object_vars($response_create->assets)), 2);
    foreach ($response_create->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
      $response_create->assets->{$asset_id}->input = base64_encode(base64_decode($asset->input) . 'new');
    }

    // modify page assets.
    $response_modify = $this->smallhay->update_page_assets($created_id, json_encode($response_create));
    $this->_assertSuccess($response_modify);
    $this->_assertAttributes($response_modify, array('assets', 'page'), array('links'));
    $this->assertEquals(count(get_object_vars($response_modify->assets)), 2);
    $this->assertEquals($response_create, $response_modify);
    foreach ($response_modify->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }

    // list page assets.
    $response_list = $this->smallhay->get_page_assets($created_id);
    $this->_assertSuccess($response_list);
    $this->_assertAttributes($response_list, array('assets', 'page', 'links'));
    $this->assertGreaterThanOrEqual(2, count(get_object_vars($response_list->assets)));
    foreach ($response_modify->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }

    // delete page assets.
    $response_delete = $this->smallhay->delete_page_assets($created_id, json_encode(array_keys(get_object_vars($response_modify->assets))));
    $this->_assertSuccess($response_delete);
    $this->_assertAttributes($response_delete, array('assets', 'page'), array('links'));
    $this->assertEquals(count(get_object_vars($response_delete->assets)), 2);
    $this->assertEquals($response_delete, $response_modify);
    foreach ($response_modify->assets as $asset_id => $asset) {
      $this->_assertAttributes($asset, array('id', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }
  }

  /**
   * Asset Attributes
   *
   * Checks to make sure the object passed in contains the 'has' properties and
   * does not have the 'has_not' properties.
   *
   * @param stdClass $object
   * @param array $has
   * @param array $has_not
   */
  private function _assertAttributes($object, $has = array(), $has_not = array()) {
    foreach ($has as $attribute) {
      $this->assertObjectHasAttribute($attribute, $object);
    }
    foreach ($has_not as $attribute) {
      $this->assertObjectNotHasAttribute($attribute, $object);
    }
    $this->assertEquals(count(get_object_vars($object)), count($has));
  }

  /**
   * Asset Error
   *
   * Checks the response object for all error traits.
   *
   * @param stdClass $response
   * @param $error_code
   * @param $http_code
   */
  private function _assertError($response, $error_code, $http_code) {
    $this->assertNotFalse($response);
    $this->assertObjectHasAttribute('error_code', $response);
    $this->assertObjectHasAttribute('error_title', $response);
    $this->assertObjectHasAttribute('error_message', $response);
    $this->assertObjectHasAttribute('error_ref', $response);
    $this->assertObjectHasAttribute('documentation', $response);
    $this->assertEquals($response->error_code, $error_code);
    $this->assertEquals($this->smallhay->get_http_code(), $http_code);
  }

  /**
   * Asset Success
   *
   * Checks the response object for all success traits.
   *
   * @param stdClass $response
   */
  private function _assertSuccess($response) {
    $this->assertNotFalse($response);
    $this->assertEquals($this->smallhay->get_http_code(), 200);
  }

  /**
   * Create Single Page
   *
   * @return array
   */
  private function _createSinglePage() {
    $response = $this->smallhay->create_pages($this->_getJSONArrayPaths());
    $this->_assertSuccess($response);
    $created_id = 0;
    foreach ($response->pages as $page_id => $page) {
      $created_id = $page_id;
    }
    $this->assertNotEquals($created_id, 0);

    return array($created_id, $response);
  }

  /**
   * Get JSON Array - Empty
   *
   * @return false|string
   */
  private function _getJSONArrayEmpty() {
    return json_encode(array());
  }

  /**
   * Get JSON Array - Path (Valid)
   *
   * @return false|string
   */
  private function _getJSONArrayPaths() {
    return json_encode(array('/test'));
  }

  /**
   * Get JSON Array - Path Boolean
   *
   * @return false|string
   */
  private function _getJSONArrayPathsBoolean() {
    return json_encode(array(TRUE));
  }

  /**
   * Get JSON Array - Path String
   *
   * @return false|string
   */
  private function _getJSONArrayPathsString() {
    return json_encode(array('invalid'));
  }

  /**
   * Get JSON Array - Too Many Paths
   *
   * @return false|string
   */
  private function _getJSONArrayPathsTooMany() {
    return json_encode(array_values(array_fill(1, 110, '/test')));
  }

  /**
   * Get JSON Invalid
   *
   * @return false|string
   */
  private function _getJSONInvalid() {
    return '';
  }

  /**
   * Get JSON Object - Empty
   *
   * @return false|string
   */
  private function _getJSONObjectEmpty() {
    return json_encode(new stdClass());
  }

  /**
   * Get JSON Object - Page Path Boolean
   *
   * @return false|string
   */
  private function _getJSONObjectPagePathBoolean() {
    $page = new stdClass();
    $page->path = TRUE;

    return json_encode($page);
  }

  /**
   * Get JSON Object - Page Path String
   *
   * @return false|string
   */
  private function _getJSONObjectPagePathString() {
    $page = new stdClass();
    $page->path = 'invalid';

    return json_encode($page);
  }

  /**
   * Get JSON Object - Page Asset
   *
   * @return false|string
   */
  private function _getJSONObjectPageAsset() {

    // create json object with valid asset.
    $asset = new stdClass();
    $asset->type = 'javascript';
    $asset->input = base64_encode('<script type="javascript">alert("asset 1");</script>');

    $page_assets = new stdClass();
    $page_assets->assets[] = $asset;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssets() {

    // create json object with valid asset.
    $asset1 = new stdClass();
    $asset1->type = 'javascript';
    $asset1->input = base64_encode('<script type="javascript">alert("asset 1");</script>');

    // create json object with valid asset.
    $asset2 = new stdClass();
    $asset2->type = 'javascript';
    $asset2->input = base64_encode('<script type="javascript">alert("asset 2");</script>');

    $page_assets = new stdClass();
    $page_assets->assets[] = $asset1;
    $page_assets->assets[] = $asset2;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - Too Many Assets
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsArrayTooMany() {

    // create json object with invalid json data.
    $asset = new stdClass();
    $asset->type = 'invalid';
    $asset->input = '<script type="javascript">alert("test");</script>';

    // create json object with too many items.
    $assets = array();
    for ($i = 0; $i < 110; $i++) {
      $assets[] = $asset;
    }

    $page_assets = new stdClass();
    $page_assets->assets = $assets;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - ID Invalid
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsIdInvalid() {

    // create json object with missing input.
    $page_assets = new stdClass();
    $page_assets->assets = new stdClass();
    $page_assets->assets->{0} = new stdClass();
    $page_assets->assets->{0}->input = base64_encode('<script type="javascript">alert("test");</script>');

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - Input Boolean
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsInputBoolean() {

    // create json object with invalid json data.
    $asset = new stdClass();
    $asset->type = 'javascript';
    $asset->input = TRUE;

    // create json object with invalid input.
    $page_assets = new stdClass();
    $page_assets->assets[] = $asset;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - Input Missing
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsInputMissing() {

    // create json object with missing input.
    $asset = new stdClass();
    $asset->type = 'javascript';

    // create json object with invalid asset.
    $page_assets = new stdClass();
    $page_assets->assets[] = $asset;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - Input String
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsInputString() {

    // create json object with invalid json data.
    $asset = new stdClass();
    $asset->type = 'javascript';
    $asset->input = '<script type="javascript">alert("test");</script>';

    // create json object with invalid input.
    $page_assets = new stdClass();
    $page_assets->assets[] = $asset;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets Object - Input Boolean
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsObjectInputBoolean() {

    // create json object with missing input.
    $page_assets = new stdClass();
    $page_assets->assets = new stdClass();
    $page_assets->assets->{1} = new stdClass();
    $page_assets->assets->{1}->input = TRUE;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets Object - Input Missing
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsObjectInputMissing() {

    // create json object with missing input.
    $page_assets = new stdClass();
    $page_assets->assets = new stdClass();
    $page_assets->assets->{1} = new stdClass();
    $page_assets->assets->{1}->right = 'wrong';

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets Object - Input String
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsObjectInputString() {

    // create json object with missing input.
    $page_assets = new stdClass();
    $page_assets->assets = new stdClass();
    $page_assets->assets->{1} = new stdClass();
    $page_assets->assets->{1}->input = '<script type="javascript">alert("test");</script>';

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets Object - Too Many Assets
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsObjectTooMany() {

    // create json object with invalid json data.
    $asset = new stdClass();
    $asset->input = '<script type="javascript">alert("test");</script>';

    // create json object with too many items.
    $page_assets = new stdClass();
    $page_assets->assets = new stdClass();
    for ($i = 1; $i <= 110; $i++) {
      $page_assets->assets->{$i} = $asset;
    }

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - Type Invalid
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsTypeInvalid() {

    // create json object with invalid json data.
    $asset = new stdClass();
    $asset->type = 'invalid';
    $asset->input = '<script type="javascript">alert("test");</script>';

    // create json object with a single asset.
    $page_assets = new stdClass();
    $page_assets->assets[] = $asset;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - Type Missing
   *
   * @return false|string
   */
  private function _getJSONObjectPageAssetsTypeMissing() {

    // create json object with missing type.
    $asset = new stdClass();
    $asset->right = 'wrong';

    // create json object with invalid asset.
    $page_assets = new stdClass();
    $page_assets->assets[] = $asset;

    return json_encode($page_assets);
  }

  /**
   * Get JSON Object - Page Assets - ID Missing
   *
   * @return false|string
   */
  private function _getJSONObjectPagesIDMissing() {
    $pages = new stdClass();
    $pages->pages = new stdClass();
    $pages->pages->right = 'wrong';

    return json_encode($pages);
  }

  /**
   * Get JSON Object - Page Object - Path Boolean
   *
   * @return false|string
   */
  private function _getJSONObjectPagesPathBoolean() {
    $pages = new stdClass();
    $pages->pages = new stdClass();
    $pages->pages->{1} = new stdClass();
    $pages->pages->{1}->path = TRUE;

    return json_encode($pages);
  }

  /**
   * Get JSON Object - Page Object - Path String
   *
   * @return false|string
   */
  private function _getJSONObjectPagesPathString() {
    $pages = new stdClass();
    $pages->pages = new stdClass();
    $pages->pages->{1} = new stdClass();
    $pages->pages->{1}->path = 'invalid';

    return json_encode($pages);
  }

  /**
   * Get JSON Object - Page Object - Path Missing
   *
   * @return false|string
   */
  private function _getJSONObjectPagesPathMissing() {
    $pages = new stdClass();
    $pages->pages = new stdClass();
    $pages->pages->{1} = new stdClass();
    $pages->pages->{1}->right = 'wrong';

    return json_encode($pages);
  }

  /**
   * Get JSON Object - Page Object - Too Many Paths
   *
   * @return false|string
   */
  private function _getJSONObjectPagesTooMany() {
    $pages = new stdClass();
    $pages->pages = new stdClass();
    for ($i = 1; $i <= 110; $i++) {
      $pages->pages->{$i} = new stdClass();
      $pages->pages->{$i}->path = '/test';
    }

    return json_encode($pages);
  }
}
