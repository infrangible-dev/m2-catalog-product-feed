# Infrangible Catalog Product Feed

The base of modules that export products to external services (feeds of price comparison sites, marketing platforms,
search services). It collects the data of the products of every store and passes it to the integrations of these
modules, as full export of all products or as delta export of the changed products. Without an integration, the module
exports nothing.

## Exports

| Task                                       | Exports                                                                                       |
|--------------------------------------------|-----------------------------------------------------------------------------------------------|
| `catalog_product_feed_full`                | all exportable products with the integrations whose schedule was due since the last run      |
| `catalog_product_feed_delta`               | the products of the change log with the integrations that are used for the delta export      |

- Exportable products are the visible, enabled products of the store, in stock unless out of stock products are shown.
  Configurable, bundle and grouped products are exported with their children, bundled and grouped products.
- With the admin store (the default of the commands and the cron job), the tasks export the products of all stores.
- The change log collects saved products whose price, stock status or an attribute of a delta export changed, with
  their parents. The delta export removes the exported entries afterwards; in test mode (`-t`) it keeps them.
- The full export stores the start of its last successful run in `var/task/catalog_product_feed_full.run`. With
  `--force`, it ignores the schedules of the integrations.
- The flat product index is disabled while a store is exported, since it holds only some attributes.

```bash
bin/magento task:catalog_product_feed_full --force --integration=google
bin/magento task:catalog_product_feed_delta --store_code=default
```

Both commands have the options of [Infrangible Task](https://www.infrangible.dev/modules/task) and `--integration`
(names separated by comma, all if empty). The cron job `task_product_feed_delta` runs every minute; Stores >
Configuration > Tasks > Product Feed (Changes) > Execution changes the cron expression.

## Integrations

Modules register their integrations in their `config.xml` as `infrangible_catalogproductfeed/integration/<name>`, a
class that implements `Infrangible\CatalogProductFeed\Model\IIntegration`. The export calls per run `start()`, per
store `startStore()` with the feed attributes of the integration, per block of products `startBlock()`, the export
methods of the products and their variants, `finishBlock()`, `finishStore()`, and `finish()` at the end.

`Infrangible\CatalogProductFeed\Model\Integration\Simple` collects the feed attributes and calculates their values; its
subclasses write the values in `exportProduct()`, `exportChild()`, `exportBundled()` and `exportGrouped()`.

The admin screens of the feed attributes are abstract classes that integrations extend with their ACL resource and menu:
the controllers in `Controller\Backend\ProductFeed\Attribute`, the form and the grid in `Block\Adminhtml\Attribute`.

## Feed attributes

Every feed attribute belongs to an integration and is exported under its code, for products, for variants or both:

- **Source:** a product attribute, a custom field or a fixed value.
- **Value format:** a `sprintf()` format, for example `%s EUR`; values that do not fit the format are kept.
- **Strip tags:** removes HTML tags and entities.
- **Delta-Index:** the attribute is exported in the delta export, and changes of the product attribute go into the
  change log.

Custom fields of this module:

| Custom field                                  | Value                                                                    |
|-----------------------------------------------|--------------------------------------------------------------------------|
| `category_names`, `category_urls`             | names and URLs of the categories by category ID                          |
| `final_price`, `final_price_currency`         | final price of the price index; for configurable, bundle and grouped products the minimal price |
| `price`, `price_currency`                     | price; with currency for configurable, bundle and grouped products the minimal price |
| `special_price`, `special_price_currency`     | special price                                                            |
| `image_url`, `small_image_url`, `thumbnail_url` | URL of the image                                                       |
| `product_url`, `product_url_with_key`         | URL of the product, from its URL rewrite or from its URL key             |
| `product_category_urls`                       | URLs of the product by category ID (the root category for the URL without category) |
| `parent_sku`, `product_id`, `stock_qty`       | SKU of the parent of variants, product ID, stock quantity                |

Prices have two decimals; with currency, they are followed by the base currency of the store, the currency of the
price index. Other modules add custom fields in their `etc/catalog_product_feed_attribute.xml` with a class that
extends `Infrangible\CatalogProductFeed\Model\Attribute\Custom\AbstractCustom`.

Events:

| Event                                                              | Data                                                    |
|--------------------------------------------------------------------|---------------------------------------------------------|
| `infrangible_catalogproductfeed_export_validation`                 | `export_validation`: `setResult(false)` skips a product |
| `infrangible_catalogproductfeed_attribute_edit_form_prepare_form`  | `form`, `attribute`                                     |

## Configuration

Stores > Configuration > Infrangible Solutions > Product Feed > Export > Limit active Categories to Store: exports only
the categories below the root category of the store (default: yes).

## Requirements

- Magento Open Source or Adobe Commerce 2.4.6 or newer
- PHP 8.2 or newer

## Installation

```bash
composer require infrangible/m2-catalog-product-feed
bin/magento module:enable Infrangible_CatalogProductFeed
bin/magento setup:upgrade
```

In production mode, also run `bin/magento setup:di:compile`.

## License

Infrangible Catalog Product Feed is licensed under the MIT License - see the LICENSE file for details.
