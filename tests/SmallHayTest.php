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
   * Set up.
   */
  protected function setUp() {
    $this->smallhay = new API($this->client_id, $this->client_secret, TRUE);
  }

  /**
   * Tear down.
   */
  protected function tearDown() {
    $response = $this->smallhay->get_pages();

    $pages_to_delete = [];
    foreach ($response->pages as $page) {
      $pages_to_delete[] = $page->id;
    }
    $response = $this->smallhay->delete_pages(json_encode($pages_to_delete));
  }

  /**
   * Test 1 - object properties.
   */
  public function testProperties() {

    // Client credentials are set.
    $this->assertEquals($this->client_id, $this->smallhay->get_client_id());
    $this->assertEquals($this->client_secret, $this->smallhay->get_client_secret());

    // Bearer token is not set.
    $this->assertNull($this->smallhay->get_bearer_token());
    $this->assertEquals($this->smallhay->get_bearer_token_expires(), 0);

    // Curl values are default.
    $this->assertEquals($this->smallhay->get_curl_connect_timeout(), 2);
    $this->assertEquals($this->smallhay->get_curl_timeout(), 4);

    // Change the curl values and test again.
    $this->smallhay->set_curl_connect_timeout(4);
    $this->smallhay->set_curl_timeout(2);
    $this->assertEquals($this->smallhay->get_curl_connect_timeout(), 4);
    $this->assertEquals($this->smallhay->get_curl_timeout(), 2);

    // Confirm the test flag is set.
    $this->assertEquals($this->smallhay->get_test(), TRUE);
  }

  /**
   * Test 2 - auth.
   */
  public function testAuth() {

    // Create a new smallhay object with invalid credentials.
    $bad_smallhay = new API('bad_demo', 'bad_demo', TRUE);

    // Create two pages, this fails the auth call in send().
    $response = $bad_smallhay->get_auth();
    $this->assertFalse($response);

    // Valid auth call
    $response = $this->smallhay->get_auth();
    $this->assertEquals($this->smallhay->get_http_code(), 200);
    $this->assertNotNull($this->smallhay->get_bearer_token());
    $this->assertNotEquals($this->smallhay->get_bearer_token_expires(), 0);
  }

  /**
   * Test 3 - create pages errors.
   */
  public function testCreatePagesErrors() {

    // invalid JSON.
    $response = $this->smallhay->create_pages($this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing path data.
    $response = $this->smallhay->create_pages($this->_getEmptyArray());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->create_pages($this->_getTooManyPaths());
    $this->_assertError($response, 'SH-v1-010', 500);

    // invalid path - non-string.
    $response = $this->smallhay->create_pages($this->_getBooleanPaths());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - missing leading /.
    $response = $this->smallhay->create_pages($this->_getInvalidPaths());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 4 - modify pages errors.
   */
  public function testModifyPagesErrors() {

    // Create two pages.
    $response_create = $this->smallhay->create_pages($this->_getPaths());
    $this->_assertSuccess($response_create);
    $this->assertNotEquals($response_create[0]->id, 0);
    $this->assertNotEquals($response_create[1]->id, 0);

    // invalid JSON.
    $response = $this->smallhay->update_pages($this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing pages data.
    $response = $this->smallhay->update_pages($this->_getEmptyArray());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->update_pages($this->_getTooManyPages());
    $this->_assertError($response, 'SH-v1-010', 500);

    // non objects.
    $response = $this->smallhay->update_pages($this->_getInvalidPaths());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing id.
    $response = $this->smallhay->update_pages($this->_getPagesMissingId());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid id - non-numeric.
    $response = $this->smallhay->update_pages($this->_getPagesInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing path.
    $response = $this->smallhay->update_pages($this->_getPagesMissingPath());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - missing leading /.
    $response = $this->smallhay->update_pages($this->_getPagesInvalidPathFormat());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - non-string.
    $response = $this->smallhay->update_pages($this->_getPagesInvalidPathType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // duplicate via update.
    $first_path = $response_create[0]->path;
    $second_path = $response_create[1]->path;
    $response_create[0]->path = $second_path;
    $response_create[1]->path = $first_path;
    $response = $this->smallhay->update_pages(json_encode($response_create));
    $this->_assertError($response, 'SH-v1-013', 500);
  }

  /**
   * Test 5 - delete pages errors.
   */
  public function testDeletePagesErrors() {

    // invalid JSON.
    $response = $this->smallhay->delete_pages($this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing page id.
    $response = $this->smallhay->delete_pages($this->_getEmptyArray());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->delete_pages($this->_getTooManyIds());
    $this->_assertError($response, 'SH-v1-010', 500);

    // incorrect page ids.
    $response = $this->smallhay->delete_pages($this->_getIncorrectIds());
    $this->_assertError($response, 'SH-v1-012', 404);

    // invalid page ids.
    $response = $this->smallhay->delete_pages($this->_getInvalidIds());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 6 - pages.
   */
  public function testPagesCalls() {

    // Create two pages.
    $response_create = $this->smallhay->create_pages($this->_getPaths());
    $this->_assertSuccess($response_create);
    $this->assertIsArray($response_create);
    $this->assertCount(2, $response_create);
    foreach ($response_create as $index => $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
      $response_create[$index]->path .= '/' . md5(microtime(TRUE));
    }

    // Modify two pages.
    $response_modify = $this->smallhay->update_pages(json_encode($response_create));
    $this->_assertSuccess($response_modify);
    $this->assertIsArray($response_modify);
    $this->assertCount(2, $response_modify);
    $this->assertEquals($response_create, $response_modify);
    foreach ($response_modify as $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }

    // Retrieve pages.
    $response_list = $this->smallhay->get_pages();
    $this->_assertSuccess($response_list);
    $this->assertIsObject($response_list);
    $this->_assertAttributes($response_list, array('pages', 'links'));
    $this->assertGreaterThanOrEqual(2, count($response_list->pages));
    foreach ($response_list->pages as $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }

    // Build array of ids.
    $pages_to_delete = [];
    foreach ($response_modify as $page) {
      $pages_to_delete[] = $page->id;
    }

    // Delete both pages.
    $response_delete = $this->smallhay->delete_pages(json_encode($pages_to_delete));
    $this->_assertSuccess($response_delete);
    $this->assertIsArray($response_delete);
    $this->assertCount(2, $response_delete);
    $this->assertEquals($response_delete, $response_modify);
    foreach ($response_delete as $page) {
      $this->_assertAttributes($page, array('id', 'path', 'created'));
    }
  }

  /**
   * Test 7 - list page errors.
   */
  public function testListPageErrors() {

    // invalid ID.
    $response = $this->smallhay->get_page($this->_getInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);

    // incorrect ID.
    $response = $this->smallhay->get_page($this->_getIncorrectId());
    $this->_assertError($response, 'SH-v1-011', 404);
  }

  /**
   * Test 8 - modify page errors.
   */
  public function testModifyPageErrors() {

    // Create two pages.
    $response_create = $this->smallhay->create_pages($this->_getPaths());
    $this->_assertSuccess($response_create);
    $this->assertNotEquals($response_create[0]->id, 0);
    $this->assertNotEquals($response_create[1]->id, 0);

    // modify pages - incorrect id.
    $response = $this->smallhay->update_page($this->_getIncorrectId(), json_encode($response_create[1]->path));
    $this->_assertError($response, 'SH-v1-011', 404);

    // modify pages - invalid id.
    $response = $this->smallhay->update_page($this->_getInvalidId(), json_encode($response_create[1]->path));
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid JSON.
    $response = $this->smallhay->update_page($response_create[0]->id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing page.
    $response = $this->smallhay->update_page($response_create[0]->id, $this->_getEmptyObject());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - non-string.
    $response = $this->smallhay->update_page($response_create[0]->id, $this->_getPathInvalidType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid path - missing leading /.
    $response = $this->smallhay->update_page($response_create[0]->id, $this->_getPathInvalidFormat());
    $this->_assertError($response, 'SH-v1-009', 500);

    // duplicate via update.
    $response = $this->smallhay->update_page($response_create[0]->id, json_encode($response_create[1]->path));
    $this->_assertError($response, 'SH-v1-013', 500);
  }

  /**
   * Test 9 - delete page errors.
   */
  public function testDeletePageErrors() {

    // incorrect id.
    $response = $this->smallhay->delete_page($this->_getIncorrectId());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->delete_page($this->_getInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 10 - page.
   */
  public function testPageCalls() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // Modify the page.
    $response_create[0]->path .= '/' . md5(microtime(TRUE));
    $response_modify = $this->smallhay->update_page($created_id, json_encode($response_create[0]->path));
    $this->_assertSuccess($response_modify);
    $this->assertIsObject($response_modify);
    $this->assertEquals($response_create[0], $response_modify);
    $this->_assertAttributes($response_modify, array('id', 'path', 'created'));

    // Retrieve page.
    $response_list = $this->smallhay->get_page($created_id);
    $this->_assertSuccess($response_list);
    $this->assertIsObject($response_list);
    $this->assertEquals($response_create[0], $response_list);
    $this->_assertAttributes($response_list, array('id', 'path', 'created'));

    // Delete page.
    $response_delete = $this->smallhay->delete_page($created_id);
    $this->_assertSuccess($response_delete);
    $this->assertIsObject($response_delete);
    $this->assertEquals($response_create[0], $response_delete);
    $this->_assertAttributes($response_delete, array('id', 'path', 'created'));
  }

  /**
   * Test 15 - create page assets errors.
   */
  public function testCreatePageAssetsErrors() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // incorrect id.
    $response = $this->smallhay->create_page_assets($this->_getIncorrectId(), $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->create_page_assets($this->_getInvalidId(), $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid JSON.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing assets data.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getEmptyArray());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getTooManyPageAssets());
    $this->_assertError($response, 'SH-v1-010', 500);

    // missing data type.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsMissingDataType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid data type.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsInvalidDataType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing type.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsMissingType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid type.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsInvalidType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing input.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsMissingInput());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsInvalidInput());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->create_page_assets($created_id, $this->_getPageAssetsInvalidInputBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 16 - list page assets errors.
   */
  public function testListPageAssetsErrors() {

    // incorrect id.
    $response = $this->smallhay->get_page_assets($this->_getIncorrectId());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->get_page_assets($this->_getInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 17 - modify page assets errors.
   */
  public function testModifyPageAssetsErrors() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // Create page assets.
    $response_create = $this->smallhay->create_page_assets($created_id, $this->_getPageAssets());
    $this->_assertSuccess($response_create);
    $this->assertIsArray($response_create);
    $this->assertCount(2, $response_create);
    $this->assertNotEquals($response_create[0]->id, 0);
    $this->assertNotEquals($response_create[1]->id, 0);

    // incorrect id.
    $response = $this->smallhay->update_page_assets($this->_getIncorrectId(), $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->update_page_assets($this->_getIncorrectId(), $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid JSON.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing assets data.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getEmptyArray());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getTooManyPageAssets());
    $this->_assertError($response, 'SH-v1-010', 500);

    // missing type.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsMissingType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid type.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsInvalidType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsMissingInput());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsInvalidInput());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsInvalidInputBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // incorrect page asset ids
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsIncorrectId());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid page asset ids
    $response = $this->smallhay->update_page_assets($created_id, $this->_getPageAssetsInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);

    // duplicate via update.
    $first_input = $response_create[0]->input;
    $second_input = $response_create[1]->input;
    $response_create[0]->input = $second_input;
    $response_create[1]->input = $first_input;
    $response = $this->smallhay->update_page_assets($created_id, json_encode($response_create));
    $this->_assertError($response, 'SH-v1-013', 500);
  }

  /**
   * Test 18 - delete page assets errors.
   */
  public function testDeletePageAssetsErrors() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // incorrect ids.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getIncorrectIds());
    $this->_assertError($response, 'SH-v1-012', 404);

    // invalid id.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getInvalidIds());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid JSON.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing page asset id.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getEmptyArray());
    $this->_assertError($response, 'SH-v1-009', 500);

    // maximum items.
    $response = $this->smallhay->delete_page_assets($created_id, $this->_getTooManyIds());
    $this->_assertError($response, 'SH-v1-010', 500);
  }

  /**
   * Test 19 - page assets.
   */
  public function testPageAssetsCalls() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // create page assets.
    $response_create = $this->smallhay->create_page_assets($created_id, $this->_getPageAssets());
    $this->_assertSuccess($response_create);
    $this->assertIsArray($response_create);
    $this->assertCount(2, $response_create);
    foreach ($response_create as $asset) {
      $this->_assertAttributes($asset, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));
      $response_create[0]->input = base64_encode(base64_decode($asset->input) . 'new');
    }

    // modify page assets.
    $response_modify = $this->smallhay->update_page_assets($created_id, json_encode($response_create));
    $this->_assertSuccess($response_modify);
    $this->assertIsArray($response_modify);
    $this->assertCount(2, $response_modify);
    $this->assertEquals($response_create, $response_modify);
    foreach ($response_modify as $asset) {
      $this->_assertAttributes($asset, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }

    // list page assets.
    $response_list = $this->smallhay->get_page_assets($created_id);
    $this->_assertSuccess($response_list);
    $this->_assertAttributes($response_list, array('assets', 'links'));
    $this->assertCount(2, $response_list->assets);
    foreach ($response_list->assets as $asset) {
      $this->_assertAttributes($asset, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }

    // Build array of ids.
    $pages_to_delete = [];
    foreach ($response_modify as $page) {
      $pages_to_delete[] = $page->id;
    }

    // delete page assets.
    $response_delete = $this->smallhay->delete_page_assets($created_id, json_encode($pages_to_delete));
    $this->_assertSuccess($response_delete);
    $this->assertIsArray($response_delete);
    $this->assertCount(2, $response_delete);
    $this->assertEquals($response_delete, $response_modify);
    foreach ($response_modify as $asset) {
      $this->_assertAttributes($asset, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));
    }
  }

  /**
   * Test 11 - list page asset errors.
   */
  public function testListPageAssetErrors() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // incorrect id.
    $response = $this->smallhay->get_page_asset($created_id, $this->_getIncorrectId());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->get_page_asset($created_id, $this->_getInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 12 - modify page asset errors.
   */
  public function testModifyPageAssetErrors() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // Create page assets.
    $response_create = $this->smallhay->create_page_assets($created_id, $this->_getPageAssets());
    $this->_assertSuccess($response_create);
    $this->assertIsArray($response_create);
    $this->assertCount(2, $response_create);
    $this->assertNotEquals($response_create[0]->id, 0);
    $this->assertNotEquals($response_create[1]->id, 0);

    // incorrect page id.
    $response = $this->smallhay->update_page_asset($this->_getIncorrectId(), $response_create[0]->id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid page id.
    $response = $this->smallhay->update_page_asset($this->_getInvalidId(), $response_create[0]->id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-009', 500);
    
    // incorrect id.
    $response = $this->smallhay->update_page_asset($created_id, $this->_getIncorrectId(), $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->update_page_asset($created_id, $this->_getInvalidIds(), $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid JSON.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getEmptyString());
    $this->_assertError($response, 'SH-v1-008', 500);

    // missing asset.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getEmptyObject());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing type.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getPageAssetMissingType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid type.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getPageAssetInvalidType());
    $this->_assertError($response, 'SH-v1-009', 500);

    // missing input.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getPageAssetMissingInput());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input - non-string.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getPageAssetInvalidInputBoolean());
    $this->_assertError($response, 'SH-v1-009', 500);

    // invalid input.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, $this->_getPageAssetInvalidInput());
    $this->_assertError($response, 'SH-v1-009', 500);

    // duplicate via update.
    $response = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, json_encode($response_create[1]));
    $this->_assertError($response, 'SH-v1-013', 500);
  }

  /**
   * Test 13 - delete page asset errors.
   */
  public function testDeletePageAssetErrors() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // incorrect id.
    $response = $this->smallhay->delete_page_asset($created_id, $this->_getIncorrectId());
    $this->_assertError($response, 'SH-v1-011', 404);

    // invalid id.
    $response = $this->smallhay->delete_page_asset($created_id, $this->_getInvalidId());
    $this->_assertError($response, 'SH-v1-009', 500);
  }

  /**
   * Test 14 - page asset.
   */
  public function testPageAssetCalls() {
    list($created_id, $response_create) = $this->_createSinglePage();

    // create page assets.
    $response_create = $this->smallhay->create_page_assets($created_id, $this->_getPageAssets());
    $this->_assertSuccess($response_create);
    $this->assertIsArray($response_create);
    $this->assertCount(2, $response_create);
    foreach ($response_create as $index => $asset) {
      $this->_assertAttributes($asset, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));
      $response_create[$index]->input = base64_encode(base64_decode($asset->input) . 'new');
    }

    // modify page asset.
    $response_modify = $this->smallhay->update_page_asset($created_id, $response_create[0]->id, json_encode($response_create[0]));
    $this->_assertSuccess($response_modify);
    $this->assertEquals($response_create[0], $response_modify);
    $this->_assertAttributes($response_modify, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));

    // list page asset.
    $response_list = $this->smallhay->get_page_asset($created_id, $response_create[0]->id);
    $this->_assertSuccess($response_list);
    $this->assertEquals($response_list, $response_modify);
    $this->_assertAttributes($response_list, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));

    // delete page asset.
    $response_delete = $this->smallhay->delete_page_asset($created_id, $response_create[0]->id);
    $this->_assertSuccess($response_delete);
    $this->assertEquals($response_delete, $response_list);
    $this->_assertAttributes($response_delete, array('id', 'data_type', 'type', 'input', 'output', 'created', 'completed', 'status'));
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
    $this->assertIsObject($object);
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
    $this->assertIsObject($response);
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
    $response = $this->smallhay->create_pages($this->_getPath());
    $this->_assertSuccess($response);
    $this->assertIsArray($response);
    $this->assertCount(1, $response);
    $this->assertNotEquals($response[0]->id, 0);

    return array($response[0]->id, $response);
  }
  
  /**
   * Get Boolean Paths
   * 
   * @return array
   */
  private function _getBooleanPaths() {
    return json_encode(array(TRUE, FALSE));
  }

  /**
   * Get Empty Array.
   * 
   * @return string
   */
  private function _getEmptyArray() {
    return json_encode(array());
  }

  /**
   * Get Empty Object.
   * 
   * @return string
   */
  private function _getEmptyObject() {
    return json_encode(new stdClass());
  }

  /**
   * Get Empty String.
   * 
   * @return string
   */
  private function _getEmptyString() {
    return '';
  }

  /**
   * Get Incorrect Id.
   * 
   * @return string
   */
  private function _getIncorrectId() {
    return 0;
  }

  /**
   * Get Incorrect Ids.
   * 
   * @return string
   */
  private function _getIncorrectIds() {
    return json_encode(array(0, 0));
  }

  /**
   * Get Invalid Id.
   * 
   * @return string
   */
  private function _getInvalidId() {
    return 'invalid';
  }

  /**
   * Get Invalid Ids.
   * 
   * @return string
   */
  private function _getInvalidIds() {
    return json_encode(array('invalid', 'invalid'));
  }

  /**
   * Get Invalid Paths.
   * 
   * @return string
   */
  private function _getInvalidPaths() {
    return json_encode(array('invalid', 'invalid'));
  }

  /**
   * Get Page Asset with Invalid Input.
   * 
   * @return string
   */
  private function _getPageAssetInvalidInput() {
    $asset = new stdClass();
    $asset->type = 'file';
    $asset->input = 'https://api.smallhay.com/v1';

    return json_encode($asset);
  }

  /**
   * Get Page Asset with Invalid Input (Boolean).
   * 
   * @return string
   */
  private function _getPageAssetInvalidInputBoolean() {
    $asset = new stdClass();
    $asset->type = 'file';
    $asset->input = TRUE;

    return json_encode($asset);
  }

  /**
   * Get Page Asset with Invalid Type.
   * 
   * @return string
   */
  private function _getPageAssetInvalidType() {
    $asset = new stdClass();
    $asset->type = 'invalid';
    $asset->input = base64_encode('https://api.smallhay.com/v1');

    return json_encode($asset);
  }

  /**
   * Get Page Asset with Missing Input.
   * 
   * @return string
   */
  private function _getPageAssetMissingInput() {
    $asset = new stdClass();
    $asset->type = 'file';

    return json_encode($asset);
  }

  /**
   * Get Page Asset with Missing Type.
   * 
   * @return string
   */
  private function _getPageAssetMissingType() {
    $asset = new stdClass();
    $asset->input = base64_encode('https://api.smallhay.com/v1');

    return json_encode($asset);
  }

  /**
   * Get Page Assets.
   * 
   * @return string
   */
  private function _getPageAssets() {
    $inputs = ['<script type="javascript">alert("asset 1");</script>', '<script type="javascript">alert("asset 2");</script>'];
    $assets = [];
    foreach ($inputs as $input) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->type = 'raw';
      $asset->input = base64_encode($input);
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Incorrect Id.
   * 
   * @return string
   */
  private function _getPageAssetsIncorrectId() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->id = 0;
      $asset->type = 'file';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Invalid Data Type.
   * 
   * @return string
   */
  private function _getPageAssetsInvalidDataType() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'invalid';
      $asset->type = 'file';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Invalid Id.
   * 
   * @return string
   */
  private function _getPageAssetsInvalidId() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->id = 'invalid';
      $asset->type = 'file';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Invalid Input.
   * 
   * @return string
   */
  private function _getPageAssetsInvalidInput() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->type = 'file';
      $asset->input = 'https://api.smallhay.com/v1';
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Invalid Input (Boolean).
   * 
   * @return string
   */
  private function _getPageAssetsInvalidInputBoolean() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->type = 'file';
      $asset->input = TRUE;
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Invalid Type.
   * 
   * @return string
   */
  private function _getPageAssetsInvalidType() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->type = 'invalid';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Missing Data Type.
   * 
   * @return string
   */
  private function _getPageAssetsMissingDataType() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->type = 'file';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Missing Input.
   * 
   * @return string
   */
  private function _getPageAssetsMissingInput() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->type = 'file';
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Page Assets with Missing Type.
   * 
   * @return string
   */
  private function _getPageAssetsMissingType() {
    $assets = [];

    for ($i = 1; $i <= 2; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Pages with Invalid Id.
   * 
   * @return string
   */
  private function _getPagesInvalidId() {
    $pages = [];

    for ($i = 1; $i <= 2; $i++) {
      $page = new stdClass();
      $page->id = 'invalid';
      $page->path = '/test';
      $pages[] = $page;
    }

    return json_encode($pages);
  }

  /**
   * Get Pages with Invalid Path Format.
   * 
   * @return string
   */
  private function _getPagesInvalidPathFormat() {
    $pages = [];

    for ($i = 1; $i <= 2; $i++) {
      $page = new stdClass();
      $page->id = $i;
      $page->path = 'test';
      $pages[] = $page;
    }

    return json_encode($pages);
  }

  /**
   * Get Pages with Invalid Path Type.
   * 
   * @return string
   */
  private function _getPagesInvalidPathType() {
    $pages = [];

    for ($i = 1; $i <= 2; $i++) {
      $page = new stdClass();
      $page->id = $i;
      $page->path = TRUE;
      $pages[] = $page;
    }

    return json_encode($pages);
  }

  /**
   * Get Pages with Missing Id.
   * 
   * @return string
   */
  private function _getPagesMissingId() {
    $pages = [];

    for ($i = 1; $i <= 2; $i++) {
      $page = new stdClass();
      $page->path = '/test';
      $pages[] = $page;
    }

    return json_encode($pages);
  }

  /**
   * Get Pages with Missing Path.
   * 
   * @return string
   */
  private function _getPagesMissingPath() {
    $pages = [];

    for ($i = 1; $i <= 2; $i++) {
      $page = new stdClass();
      $page->id = $i;
      $pages[] = $page;
    }

    return json_encode($pages);
  }

  /**
   * Get Path (Invalid Format).
   * 
   * @return string
   */
  private function _getPathInvalidFormat() {
    return json_encode('test');
  }
  
  /**
   * Get Path (Invalid Type).
   * 
   * @return string
   */
  private function _getPathInvalidType() {
    return 1;
  }

  /**
   * Get Path.
   * 
   * @return string
   */
  private function _getPath() {
    return json_encode(array('/test'));
  }

  /**
   * Get Paths.
   * 
   * @return string
   */
  private function _getPaths() {
    return json_encode(array('/test', '/valid'));
  }

  /**
   * Get Too Many Ids.
   * 
   * @return string
   */
  private function _getTooManyIds() {
    $ids = [];

    for ($i = 1; $i <= 110; $i++) {
      $ids[] = $i;
    }

    return json_encode($ids);
  }

  /**
   * Get Too Many Page Assets.
   * 
   * @return string
   */
  private function _getTooManyPageAssets() {
    $assets = [];

    for ($i = 1; $i <= 110; $i++) {
      $asset = new stdClass();
      $asset->data_type = 'javascript';
      $asset->type = 'file';
      $asset->input = base64_encode('https://api.smallhay.com/v1');
      $assets[] = $asset;
    }

    return json_encode($assets);
  }

  /**
   * Get Too Many Pages.
   * 
   * @return string
   */
  private function _getTooManyPages() {
    $pages = [];

    for ($i = 1; $i <= 110; $i++) {
      $page = new stdClass();
      $page->id = $i;
      $page->path = '/test';
      $pages[] = $page;
    }

    return json_encode($pages);
  }

  /**
   * Get Too Many Paths.
   * 
   * @return string
   */
  private function _getTooManyPaths() {
    $paths = [];

    for ($i = 1; $i <= 110; $i++) {
      $paths[] = '/test';
    }

    return json_encode($paths);
  }

}
