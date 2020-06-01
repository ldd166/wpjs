jsdelivr-wordpress
==================

The official WordPress plugin for jsDelivr Free Public CDN

## How this works

1. We register a hook which looks for any JS/CSS files registered via WordPress API on every request.
A list of all found files is stored in a database.

2. The list of files in DB is checked periodically and local files are paired with jsDelivr CDN URLs.

3. Just before rendering the page, we use `wp_register`/`wp_deregister` functions to replace all assets which exists on the CDN with their CDN versions.

### How matching works

1. We use [jsDelivr lookup API](https://github.com/jsdelivr/data.jsdelivr.com/issues/9) to check if a file exists on the CDN.
2. If no, we check if the file is a plugin file and use jsDelivr plugin proxy endpoint.
3. If no, we check if the file is a theme file and use jsDelivr theme proxy endpoint.

## Development

You'll need php and node.js. Use `npm install` and `npm run composer:install` to install code style checkers.

Run `npm test` before committing. See `package.json` for a list of all available scripts.

Recommended: configure eslint, stylelint, and PHP Code Sniffer integrations in your IDE.
