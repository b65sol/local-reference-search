A rudimentary local document search engine based on TNTSearch. This was written for personal use, so it's rather messy.

To install dependencies run: `composer install`

To add HTML files to the index use add-to-index.php from the project root.

For example:

`php add-to-index document/some_document.html div.main`

Or adding the entirety of the Godot engine documentation:

`find documents/godot-docs-html-stable -name '*.html' -exec php add-to-index {} .document \;`

Launch the search engine:

Any HTTP server will work, the laziest way is to use the PHP development server.

For example: `php -S 127.0.0.1:8002` and then access it via your browser.

PHP Extensions required:

* mbstring
* pdo
* pdo-sqlite