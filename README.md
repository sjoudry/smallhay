# Smallhay API SDK

* This SDK requires an account with Small Hay - https://www.smallhay.com/.
* Up to date documentation can be found on the API site - https://api.smallhay.com/v1/.

# Functions

## Authentication

* get_auth() - https://api.smallhay.com/v1/#operation/Auth

## Pages

* create_pages() - https://api.smallhay.com/v1/#operation/CreatePages
* delete_pages() - https://api.smallhay.com/v1/#operation/DeletePages
* get_pages() - https://api.smallhay.com/v1/#operation/ListPages
* update_pages() - https://api.smallhay.com/v1/#operation/ModifyPages

## Page

* delete_page() - https://api.smallhay.com/v1/#operation/DeletePage
* get_page() - https://api.smallhay.com/v1/#operation/ListPage
* modify_page() - https://api.smallhay.com/v1/#operation/ModifyPage

## Page Assets

* create_page_assets() - https://api.smallhay.com/v1/#operation/CreateAssets
* delete_page_assets() - https://api.smallhay.com/v1/#operation/DeleteAssets
* get_page_assets() - https://api.smallhay.com/v1/#operation/ListAssets
* update_page_assets() - https://api.smallhay.com/v1/#operation/ModifyAssets

## Page Asset

* delete_page_asset() - https://api.smallhay.com/v1/#operation/DeleteAsset
* get_page_asset() - https://api.smallhay.com/v1/#operation/ListAsset
* modify_page_asset() - https://api.smallhay.com/v1/#operation/ModifyAsset

# Sample Code

There is no need to authenticate manually as each call to the API will check to see if there is a valid bearer token before sending the payload.

```php
// Instantiate the SDK. The final arg is boolean flag to specify which
// environment to make API calls against. If it is set to TRUE, the API calls
// will hit the test environment, otherwise it will hit the prod environment.
$smallhay = new \SmallHay\API(CLIENT_ID, CLIENT_SECRET, TEST);
```

Pages will need to be created. Pages are arbitrary identifiers and loosely represent paths.

```php
// Create pages via the API.
$response = $smallhay->create_pages(json_encode(['path1', 'path2']));
```

Additional information can be added to the path if the path itself is not enough to uniquely identify a page. For example, if the same path can display multiple languages, construct the paths like so.

```php
// Create pages via the API. Note, the language codes.
$response = $smallhay->create_pages(json_encode(['en:path1', 'fr:path1']));
```

Create a page asset via the API. Currently the API only supports Javascript.

```php
// Create a page asset. Input must always be base 64 encoded as the API expects
// that format and will error if the string is not base 64 encoded.
$asset = new stdClass();
$asset->data_type = 'javascript';
$asset->type = 'file';
$asset->input = base64_encode('https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js');

// Create page assets via the API. The first arg is the page id.
$response = $smallhay->create_page_assets(1, json_encode(array($asset)));
```

