# Localhost

A minimal macOS local development environment with automatic Nginx configuration, hosts file management, multiple PHP versions, and SSL support.

## Installation

First, make sure you have [Homebrew](https://brew.sh) installed.

Run the following command in your terminal, which will download the localhost PHAR executable, make it executable, and place it in the `/usr/local/bin` directory:
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/philipnewcomer/localhost/master/install.sh)"
```

After installing Localhost, the script will install additional dependencies via Homebrew.

## Commands

- `localhost install`: Installs required dependencies.
- `localhost start`: Boots up the system.
- `localhost stop`: Shuts down the system.
- `localhost reload`: Reloads the sites and restarts services.

## Local Sites

localhost will look for any directories that exist in your user's `Sites` directory. Any directories that exist will automatically be accessible at the URL `http://{directory}.test`, unless the host has been customized via a `localhost.yml` config file (see below). You can optionally create a subdirectory named `htdocs` and place your project files in it instead of in the site root directory, if you want to keep the localhost config file out of your project's version control.

## Local Site Configuration

localhost can read an optional file named `localhost.yml` placed in the site's root directory to customize the site settings.
This file is not required, but with it you can customize any of the following directives:
```yaml
php_version: 8.1 # 8.1 or 8.2
host: host.test
hosts: # if a site requires multiple hosts
 - host1.test
 - host2.test
 - host3.test
```
### Custom Nginx Site Configuration

If a site requires a custom Nginx configuration, place a file named `nginx.conf` in the site directory. If it exists, that file will be used instead of the Localhost-generated Nginx configuration for the site.

This file should contain the complete Nginx `server` block for the site. You can find the automatically-generated `server` blocks for all sites at `/usr/local/etc/nginx/servers/localhost.conf`, which you can use as a starting point for your own custom configuration.

## Global Defaults

To change the global defaults, create a YAML file at `~/.localhost/localhost.yml`. The following directives are available:

- `default_php_version`: The default PHP version for all sites that do not have a PHP version specified in their site-specific config file.
- `sites_directory`: The full filesystem path to your sites directory.

## Credentials

- MySQL
    - Host: `127.0.0.1`
    - User: `root`
    - Password: `root`
