# DayZ-SA.cz's ServerLog Map Tool
> Version v1.1


## Installation
1. `git clone <this-repo-url>`
2. `composer install`

**Warning:** It is crucial that only `www` directory and its contents are publicly accessible.
Otherwise all configuration files (which might contain user serice credentials) will be exposed to public.


## Sources
| Directory        | Description
|------------------|-------------
| `app`            | Core application files.
| `app/control`    | Contains application source files allowing log access, and event processing.
| `app/templates`  | Location of `Latte` template files.
| `cache`          | Directory for both log and translated `Latte` templates cache.
| `config`         | Application configuration files are located here. [Read more about configuration](#configuration). 
| `config/servers` | Server configuration files are located here. [Read more about configuration](#configuration).
| `www`            | Publicly accessible files - map access and important application endpoints.

You should not have need to edit anything other than documented configuration files.
Which are all located in `config` directory.
[Read more about configuration](#configuration) below.


## Configuration

### `map.neon`
This file contains configuration mainly for Leaflet.js library and event visuals.

| Entry           | Description
|-----------------|-------------
| `settings`      | Default map instance settings, dimensions, …
| `baseLayers`    | Specifications of map tiles.
| `ovarlayLayers` | Specifications of map tile overlay layers.
| `markers`       | Map marker type specifications (icons, colors, …).
| `events`        | Display settings for each specified event. Marker mapping and other configuration.

More details can be found inside the file.
All the important config entries are commented and explained.

### `service.neon`
Configuration of internal application behaviour is defined in this file.
It can be used to turn this admin interface into publicly usable display of selected events.

| Entry       | Description
|-------------|-------------
| `debug`     | When allowed, app will provide useful debug information (somewhere).
| `meta`      | Contains general website settings - site name, description, keywords, etc.
| `steam`     | Allows configuration of Steam profile login.
| `regex`     | Specifications of server log format.
| `limits`    | Used to apply limitations to app frontend and/or backend. Allows event filtering and enabling/disabling UI elements. 
| `behaviour` | Used for server/log selection forcing/aggregation. Aggregation configuration is not available through web UI. 

### `servers/{SERVER_ID}.neon`
Specifications of servers and log sources.

#### Server & Map
| Entry    | Description
|----------|-------------
| `server` | Conains basic information about server - its identifier, name and group...
| `map`    | Primarily used to specify static zones on given server.

#### Log source
| Entry    | Description
|----------|-------------
| `webdav` | Configuration for WebDAV client allowing log transmission through WebDAV service.
| `ftp`    | _Not supported yet._
| `local`  | _Not supported yet._

