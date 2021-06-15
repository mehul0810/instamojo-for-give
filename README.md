<h1><p align="center">Instamojo for GiveWP :green_heart:</p></h1>

<p align="center">Accept donations using GiveWP with Instamojo payment gateway.</p>

---

👉 Not a developer? Running WordPress? [Download Instamojo for Give](https://wordpress.org/plugins/mg-instamojo-for-give/) on WordPress.org.

![WordPress version](https://img.shields.io/wordpress/plugin/v/mg-instamojo-for-give.svg) ![WordPress Rating](https://img.shields.io/wordpress/plugin/r/mg-instamojo-for-give.svg) ![WordPress Downloads](https://img.shields.io/wordpress/plugin/dt/mg-instamojo-for-give.svg) [![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](https://github.com/mehul0810/instamojo-for-give/blob/master/license.txt)

Welcome to the GitHub repository for "Instamojo for Give". This is the source code and the center of active development. Here you can browse the source, look at open issues, and contribute to the project.

## Getting Started

If you're looking to contribute or actively develop, then skip ahead to the [Local Development](https://github.com/mehul0810/instamojo-for-give/#local-development) section below. The following is if you're looking to actively use the plugin on your WordPress site.

### Minimum Requirements

  * WordPress 4.8 or greater
  * PHP version 5.6 or greater
  * MySQL version 5.6 or greater

### Automatic installation

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of "Instamojo for Give", log in to your WordPress dashboard, navigate to the Plugins menu and click "Add New".

In the search field type "Instamojo for Give" and click Search Plugins. Once you have found the plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking "Install Now".

### Manual installation

The manual installation method involves downloading our donation plugin and uploading it to your server via your favorite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

### Support
This repository is not suitable for support. Please don't use GitHub issues for support requests. To get support please use the following channels:

* [WP.org Support Forums](https://wordpress.org/support/plugin/mg-instamojo-for-give) - for all users

## Local Development

To get started developing on the plugin, you will need to go through the following steps:

1. Create a new WordPress site with `<any-name>.test` as the URL
2. `cd` into your local plugins directory: `/path/to/wp-content/plugins/`
3. Clone this repository from GitHub into your plugins directory: `https://github.com/mehul0810/instamojo-for-give.git`
4. Run composer to set up dependancies: `composer install`
5. Run npm install to get the necessary npm packages: `npm install`
6. Activate the plugin in WordPress

That's it. You're now ready to start development.

### NPM Commands

We rely on the following npm commands:

* `npm run pot` - This will help to generate POT language files for production release.

### Development Notes

* Ensure that you have `SCRIPT_DEBUG` enabled within your wp-config.php file. Here's a good example of wp-config.php for debugging:
    ```
		// Enable WP_DEBUG mode
		define( 'WP_DEBUG', true );

		// Enable Debug logging to the /wp-content/debug.log file
		define( 'WP_DEBUG_LOG', true );

		// Loads unminified core files
		define( 'SCRIPT_DEBUG', true );
    ```
* Commit the `package.lock` file. Read more about why [here](https://docs.npmjs.com/files/package-lock.json).
* Your editor should recognize the `.eslintrc` and `.editorconfig` files within the Repo's root directory _(if any exists)_. Please only submit PRs following those coding style rulesets.
* Read [CONTRIBUTING.md](https://github.com/mehul0810/instamojo-for-give/blob/master/CONTRIBUTING.md) - it contains more about contributing to Instamojo for Give.

**P.S.:** This plugin is neither affiliated with GiveWP nor Instamojo. This plugin is developed and maintained by [Mehul Gohil](https://mehulgohil.com) as an open-source contribution towards WordPress
