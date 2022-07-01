A rudimentary local document search engine based on TNTSearch. This was written for personal use, so it's rather messy.

To install dependencies run: `composer install`

To add HTML files to the index use add-to-index.php from the project root.

For example:

`php add-to-index document/some_document.html div.main`

Or adding the entirety of the Godot engine documentation:

`find documents/godot-docs-html-stable -name '*.html' -exec php add-to-index {} .document \;`

PHP Extensions required:

* mbstring
* pdo
* pdo-sqlite