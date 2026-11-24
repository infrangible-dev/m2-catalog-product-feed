# Changelog

## 2.0.0

### Breaking changes

- Requires Magento 2.4.6 or newer and PHP 8.2 or newer. Older versions of Magento and PHP 7.1 to 8.1 are no longer
  supported; use version 1.x for these.
- Requires `infrangible/m2-core` 5.0, `infrangible/m2-task` 3.0, `infrangible/m2-catalog-indexer-flat` 2.0,
  `infrangible/m2-backend-widget` 4.0, `infrangible/m2-simple-mail` 2.0, `infrangible/m2-foundation` 3.0 and
  `fewedev/base` 2.4.3.
- All classes have native types and use constructor property promotion. Changed constructors: the tasks take the file
  driver of `infrangible/m2-task` 3.0, an `ExportValidationFactory` instead of the `UniversalFactory` and the change log
  helper in `Task\Base` (`Task\Full` and `Task\Delta` have no own constructor); `Helper\Custom`, `Helper\ChangeLog` and
  `ProductUrlWithKey` no longer take the helpers they did not need.
- `AbstractCustom::process()` returns `mixed`, `Model\Attribute` has typed getters (`getEavAttributeId(): ?int`,
  `isCharacterData()`, `isStripTags()`, ...).
- `Helper\ChangeLog`: `collectProductIds()` takes the product instead of its ID, `fetchEntityIds()` and
  `removeEntityIds()` take the date before which entries changed, and `$priceAttributes` is the constant
  `PRICE_ATTRIBUTES`.
- `Helper\Custom::getImageUrl()` has no size argument anymore: the URLs of resized images were the ones of Magento 1.
- The command `task:catalog_product_feed_delta` has no option `--force`: the delta export never used schedules.
- The tables are defined in `etc/db_schema.xml` instead of `Setup/InstallSchema.php`; existing tables and their data
  are kept.

### Fixed

- Integrations were started again for every store; they start and finish once per run.
- With the admin store, the default of the commands and the cron job, the tasks exported only the admin store; they
  export all stores.
- The delta export removed the change log before the export, so changes were lost when the export failed, and changes
  saved while the export ran were removed without export. Entries are removed after the export, and only the ones that
  changed before it started.
- The full export removed the change log also when it exported only some integrations, and stored its run time also
  after failed runs.
- The flat product index is enabled again also after errors of the export.
- The custom field `product_category_urls` failed: it expected the URL rewrites of Magento 1. It returns the URLs by
  category ID.
- The custom field `product_url_with_key` added the URL suffix of categories of the current store instead of the URL
  suffix of products of the exported store.
- Prices with currency showed the current currency of the store, but the prices of the price index are in the base
  currency.
- Configurable products without own price had the price 0.00: the price fields use the minimal price of configurable
  products, like of bundle and grouped products.
- Images without file (`no_selection`) have no URL.
- Changes of yes/no attributes and of attributes that were empty before go into the change log (`fewedev/base`
  2.4.3).
- Values that did not fit the value format of their attribute, and stripping tags from numbers, failed on PHP 8.
- The labels of the group "Export" and the field "Limit active Categories to Store" of the configuration were swapped;
  the field defaults to yes.
- Configured integration classes that are no integrations fail with a clear message.
- Tasks without exported integration count as empty runs.

### Added

- Translations in 24 languages.
- Unit tests.
- README with the exports, integrations, feed attributes and custom fields.

### Changed

- Scheduled integrations are due if the next run time of their cron expression after the last run has passed; invalid
  expressions are logged.
- `ExportValidation::getResult()` returns a boolean, also if an observer set the result with `setData()`.
- The module is loaded after the modules it uses.
- Checks with PHPStan level 9, the Magento coding standard and unit tests on every push.
