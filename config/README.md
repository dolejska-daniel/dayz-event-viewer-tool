# Configuration

More details about configuration can be found inside the file.
All the important config entries are commented and explained **in their specific files**.


## `map.neon`
This file contains configuration mainly for Leaflet.js library and event visuals.

| Entry           | Description
|-----------------|-------------
| `settings`      | Default map instance settings, dimensions, …
| `baseLayers`    | Specifications of map tiles.
| `ovarlayLayers` | Specifications of map tile overlay layers.
| `markers`       | Map marker type specifications (icons, colors, …).
| `events`        | Display settings for each specified event. Marker mapping and other configuration.


## `service.neon`
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
| `events`    | Allows event configuration on type basis.
| `endpoints` | Internal endpoint configuration.


## `servers/{SERVER_ID}.neon`
Specifications of servers and log sources.

### Server & Map
| Entry    | Description
|----------|-------------
| `server` | Conains basic information about server - its identifier, name and group...
| `map`    | Primarily used to specify static zones on given server.

### Log source
| Entry    | Description
|----------|-------------
| `webdav` | Configuration for WebDAV client allowing log transmission through WebDAV service.
| `ftp`    | _Not supported yet._
| `local`  | _Not supported yet._

