<?php

require_once __DIR__ . "/../bootstrap.php";

?>
<!doctype html>

<html lang="cs">
<head>
	<meta charset="utf-8">

	<title>DayZ-SA.cz :: ServerLog Map Tool</title>

	<meta name="description" content="">
	<meta name="author" content="Daniel Dolejška">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.4.0/leaflet.css" rel="stylesheet" integrity="sha256-YR4HrDE479EpYZgeTkQfgVJq08+277UXxMLbi/YP69o=" crossorigin="anonymous" />
	<link href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" rel="stylesheet" crossorigin="anonymous" />
	<link href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" rel="stylesheet" crossorigin="anonymous" />
	<style>
		body {
			margin: 0;
			padding: 0;
			overflow: hidden;
		}

		#map {
			height: 100vh;
			background: #222222;
		}

		.leaflet-popup-content h1,
		.leaflet-popup-content h2,
		.leaflet-popup-content h3,
		.leaflet-popup-content h4,
		.leaflet-popup-content h5,
		.leaflet-popup-content h6 {
			margin-bottom: 3px;
		}

		.leaflet-control-custom {
			background: #FFF;
			padding: 8px;
		}
	</style>
</head>

<body>
	<div id="map"></div>
	<div class="leaflet-control-container">
		<div class="leaflet-top leaflet-right">
			<div class="leaflet-control">
				<button class="btn btn-sm btn-primary" onclick="$('#controls-left').toggle('slide', {direction: 'left'}, 300);$('#controls-right').toggle('slide', {direction: 'right'}, 300);"><i class="fa fa-bars"></i></button>
			</div>
		</div>
		<div class="leaflet-top leaflet-right" id="controls-right" style="padding-top: 40px">
			<?php if ($service->limits->extensions->server): ?>
			<select class="leaflet-control form-control" id="server">
				<option selected disabled>-- Select Server --</option>
			</select>
			<?php endif; ?>
			<?php if ($service->limits->extensions->log): ?>
			<div class="leaflet-control input-group">
				<select class="form-control" id="log" disabled>
					<option selected disabled>-- Select Log --</option>
					<optgroup label="DONOR" id="log-group">
						<option>DayZServer_x64_2019_01_11_200416631.ADM</option>
					</optgroup>
				</select>
				<div class="input-group-append">
					<button type="button" class="btn btn-primary" onclick="$('#log').change();"><i class="fa fa-sync"></i></button>
				</div>
			</div>
			<?php endif; ?>
			<?php if ($service->limits->extensions->stats): ?>
			<div class="leaflet-control leaflet-control-custom rounded">
				<table>
					<tbody>
					<tr>
						<th class="pr-2">Event count</th>
						<td id="event-count">0</td>
					</tr>
					<tr>
						<th class="pr-2">Event count <small><abbr title="Filtered">F</abbr></small></th>
						<td id="event-count-filtered">0</td>
					</tr>
					<tr>
						<th class="pr-2">Log from</th>
						<td id="event-time-from">Unknown</td>
					</tr>
					<tr>
						<th class="pr-2">Log to</th>
						<td id="event-time-to">Unknown</td>
					</tr>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
			<?php if ($service->limits->extensions->status): ?>
			<div class="leaflet-control leaflet-control-custom rounded p-0" style="font-size: 12px"><pre class="p-1 m-0 text-right" id="status-bar" style="display: none;"></pre></div>
			<?php endif; ?>
		</div>
		<div class="leaflet-bottom leaflet-left" id="controls-left">
			<?php if ($service->limits->filters->steamid): ?>
			<div class="leaflet-control input-group">
				<input type="text" title="SteamID64" placeholder="76561198055158908" class=" form-control" id="steamid" value="<?php if (@$_GET['steamid64']): echo $_GET['steamid64']; endif; ?>" disabled>
				<div class="input-group-append">
					<button type="button" class="btn btn-danger" onclick="$('#steamid').val('').keyup();"><i class="fa fa-times"></i></button>
				</div>
			</div>
			<?php endif; ?>
			<?php if ($service->limits->filters->time): ?>
			<div class="leaflet-control input-group">
				<select class="form-control" id="time-from" disabled>
					<option selected>Select log first.</option>
				</select>
				<select class="form-control" id="time-to" disabled>
					<option selected>Select log first.</option>
				</select>
				<div class="input-group-append">
					<button type="button" class="btn btn-danger" onclick="time_filter__reset();"><i class="fa fa-times"></i></button>
				</div>
			</div>
			<?php endif; ?>
			<?php if ($service->limits->extensions->visuals): ?>
			<div class="leaflet-control leaflet-control-custom rounded">
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="connectEvents" value="1" onchange="displayEvents(); setUrlParam('connect_events', $('#connectEvents:checked').val());" <?php if (@$_GET['connect_events'] == 1): ?>checked<?php endif; ?>>
					<label class="custom-control-label" for="connectEvents">Visually connect player events</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="visualizeAdditionalData" value="1" onchange="displayEvents(); setUrlParam('visualize_additional_data', $('#visualizeAdditionalData:checked').val());" <?php if (@$_GET['visualize_additional_data'] == 1): ?>checked<?php endif; ?>>
					<label class="custom-control-label" for="visualizeAdditionalData">Visualize additional event data</label>
				</div>
			</div>
			<?php endif; ?>
			<?php if ($service->limits->filters->type): ?>
			<div class="leaflet-control leaflet-control-custom rounded">
				<select style="height: 140px" class="pl-1 pr-2 form-control" id="event-types" multiple>
					<option disabled>Select log first.</option>
				</select>
				<button type="button" class="btn btn-sm btn-danger w-100 mt-1" onclick="event_types__reset();"><i class="fa fa-times"></i></button>
			</div>
			<?php endif; ?>
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
	<script type="text/javascript">
		<?php if ($service->limits->filters->steamid): ?>
		var steamid64;
		<?php if (@$_GET['steamid64']): ?>
		steamid64 = "<?=$_GET['steamid64']?>";
		<?php endif; ?>
		$( "#steamid" ).on('keyup', function ( event ) {
			var $this = $( this );
			if (!$this.val())
				steamid64 = null;
			else
				steamid64 = $this.val();

			if (event.key === "Enter" || typeof event.key === "undefined")
			{
				setUrlParam('steamid64', $this.val());
				filterEvents();
				displayEvents();
			}
		});
		<?php endif; ?>

		var server;
		<?php if (@$_GET['server']): ?>
		server = "<?=$_GET['server']?>";
		<?php endif; ?>
		$( "#server" ).on('change', function() {
			var $this = $( this );
			server = $this.val();
			setUrlParam('server', $this.val());
			loadLogfiles();
		});

		var log;
		<?php if (@$_GET['log']): ?>
		log = "<?=$_GET['log']?>";
		<?php endif; ?>
		$( "#log" ).on('change', function(event) {
			var $this = $( this );
			log = $this.val();
			if (event.eventPhase)
			{
				// Real change by user
				time_from = null;
				time_to = null;
			}
			setUrlParam('log', $this.val());
			loadEvents();
			loadZones();
		});

		var time_from, time_from_default;
		var time_to, time_to_default;
		<?php if ($service->limits->filters->time): ?>
		<?php if (@$_GET['time_from']): ?>
		time_from = "<?=$_GET['time_from']?>";
		<?php endif; ?>
		$("#time-from").on('change', function () {
			var $this = $( this );
			if (!$this.val())
				$this.val(time_from_default);
			$("#time-to option").each(function (key, opt) {
				var $opt = $(opt);
				if (parseInt($opt.val()) <= parseInt($this.val()))
					$opt.attr("disabled", "disabled");
				else
					$opt.removeAttr("disabled");
			});
			setUrlParam('time_from', $this.val());
			filterEvents();
			displayEvents();
		});

		<?php if (@$_GET['time_to']): ?>
		time_to = "<?=$_GET['time_to']?>";
		<?php endif; ?>
		$("#time-to").on('change', function () {
			var $this = $( this );
			if (!$this.val())
				$this.val(time_to_default);
			$("#time-from option").each(function (key, opt) {
				var $opt = $(opt);
				if (parseInt($opt.val()) >= parseInt($this.val()))
					$opt.attr("disabled", "disabled");
				else
					$opt.removeAttr("disabled");
			});
			setUrlParam('time_to', $this.val());
			filterEvents();
			displayEvents();
		});
		function time_filter__reset() {
			$('#time-from').val(time_from_default).change();
			$('#time-to').val(time_to_default).change();
		}
		<?php endif; ?>

		<?php if ($service->limits->filters->type): ?>
		var event_types;
		<?php if (@$_GET['event_types']): ?>
		event_types = JSON.parse(atob("<?=$_GET['event_types']?>"));
		<?php endif; ?>
		$("#event-types").on('change', function () {
			var $this = $( this );
			event_types = $this.val();
			setUrlParam('event_types', btoa(JSON.stringify($this.val())));
			filterEvents();
			displayEvents();
		});
		function event_types__reset() {
			$('#event-types option').prop('selected', true);
			$('#event-types').change();
			setUrlParam('event_types', null);
			event_types = null;
		}
		<?php endif; ?>

		var statusBarTimeout;
		function showStatusMessage(message, timeout) {
			<?php if ($service->limits->extensions->status): ?>
			var $statusBar = $("#status-bar");
			if (statusBarTimeout)
			{
				clearTimeout(statusBarTimeout);
				$statusBar.append("\n");
			}
			$statusBar.show().append(message);
			statusBarTimeout = setTimeout(function() {
				statusBarTimeout = null;
				$statusBar.hide().html("");
			}, timeout || 3000);
			<?php endif; ?>
		}
	</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.4.0/leaflet.js" integrity="sha256-6BZRSENq3kxI4YYBDqJ23xg0r1GwTHEpvp3okdaIqBw=" crossorigin="anonymous"></script>
	<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js" crossorigin="anonymous"></script>
	<script type="text/javascript">
		//15360x15360m
		//je to 2048*7,5m
		/*
		var width = 15360.0,
			height = 15360.0;
			*/
		var width = 15927.0,
			height = 15928.0;

		var mapWidth = 256,
			mapHeight = 256;

		var eventSettings = JSON.parse('<?=json_encode($service->events)?>');

		// https://github.com/pointhi/leaflet-color-markers
		var pinDefaults = {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
			iconSize: [25, 41],
			iconAnchor: [12, 41],
			popupAnchor: [1, -34],
			shadowSize: [41, 41]
		};
		var pins = {};
		pins['blue'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['red'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['green'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['yellow'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['orange'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['violet'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-violet.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['grey'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-grey.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));
		pins['black'] = new L.Icon(Object.assign(pinDefaults, {
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-black.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
		}));

		var baseLayers = {};
		<?php foreach($map->baseLayers as $id => $baseLayer): ?>
		baseLayers['<?=$id?>'] = L.tileLayer('<?=$baseLayer->source?>', JSON.parse('<?=json_encode($baseLayer->options)?>'));
		baseLayers['<?=$id?>'].getTileUrl = function (coords) {
			coords.y = -coords.y - 1;
			return L.TileLayer.prototype.getTileUrl.bind(baseLayers['<?=$id?>'])(coords);
		};
		<?php endforeach; ?>

		var map = L.map('map', Object.assign(JSON.parse('<?=json_encode($map->settings->options)?>'), {
			crs: L.CRS['<?=$map->settings->options->crs?>'],
		}));
		baseLayers['<?=$map->settings->defaults->baseLayer?>'].addTo(map);
		L.control.layers(baseLayers, null, {
			position: 'topleft'
		}).addTo(map);
		//map.setMaxBounds(map.getBounds());

		//  Right click
		map.on("contextmenu", function (event) {
			console.log("Coordinates: %s => %o, %o", event.latlng.toString(), getXY(event.latlng), getLatLng(getXY(event.latlng)[0], getXY(event.latlng)[1]));
			//L.marker(event.latlng).addTo(map);
		});

		function getXY(latLng) {
			var x = latLng.lng;
			var y = latLng.lat;
			return [x, y];
		}

		function getLatLngFromPosition(p) {
			return getLatLng(p.x, p.z, p.y);
		}

		function getLatLng(x, y, z) {
			var lng = x / width;
			lng *= mapWidth;
			var lat = y / height;
			lat *= mapHeight;
			return new L.LatLng(lat, lng, z);
		}

		function timeToInts(secs)
		{
			return [
				Math.floor(secs / 3600) % 24,
				Math.floor((secs % 3600) / 60),
				Math.floor((secs % 3600) % 60),
			];
		}

		function intsToTime(ints)
		{
			return ints[0] * 3600 + ints[1] * 60 + ints[2];
		}

		function timeToString(secs)
		{
			var x = timeToInts(secs);
			var H = x[0].toString();
			var i = x[1].toString();
			var s = x[2].toString();
			if (H.length === 1)
				H = "0" + H;
			if (i.length === 1)
				i = "0" + i;
			if (s.length === 1)
				s = "0" + s;
			return H + ":" + i + ":" + s;
		}

		function setTimeFilter(from, min_diff) {
			var low = from - min_diff * 60;
			low = Math.floor(low / 300) * 300;

			var high = from + min_diff * 60;
			high = Math.ceil(high / 300) * 300;

			$( "#time-from" ).val(low || time_from_default).change();
			$( "#time-to" ).val(high || time_to_default).change();
		}

		function timeFilter(filterEntries) {
			var $timeFrom = $( "#time-from" ).html("");
			var $timeTo = $( "#time-to" ).html("");

			for (var timestamp in filterEntries)
			{
				if (!filterEntries.hasOwnProperty(timestamp))
					continue;

				$timeFrom.append($("<option>").val(timestamp).text(filterEntries[timestamp]));
				$timeTo.append($("<option>").val(timestamp).text(filterEntries[timestamp]));
			}

			$timeFrom.val(time_from || time_from_default).change();
			$timeTo.val(time_to || time_to_default).change();
		}

		function loadLogfiles() {
			$.get("logs.php", { server: server }).done(function (data) {
				var logs = JSON.parse(data);
				var $group = $( "#log-group" ).attr("label", logs.server.name).html("");
				for (var fileid in logs.files)
				{
					if (!logs.files.hasOwnProperty(fileid))
						continue;

					var file = logs.files[fileid];
					$group.append($("<option>").attr("value", fileid).text(file));
				}
				var $log = $( "#log" ).removeAttr("disabled");
				$log.val(log);
				if ($log.val() == null)
				{
					// Select current log if no other log has been selected
					$log.val("DayZServer_x64.ADM");
				}
				$log.change();
			});
		}

		var zones = [];
		var zonePolygons = [];
		function loadZones() {
			showStatusMessage("Loading zones...");
			for (var i = 0; i < zonePolygons.length; i++) {
				map.removeLayer(zonePolygons[i]);
			}
			zonePolygons = [];

			$.get("zones.php", { server: server }).done(function (data) {
				var result = JSON.parse(data);
				for (var zoneName in result.zones)
				{
					if (!result.zones.hasOwnProperty(zoneName))
						continue;

					var z = zones[zoneName] = result.zones[zoneName];
					var latLngs = [];
					for (var i = 0; i < z.bounds.length; i++)
						latLngs.push(getLatLng(z.bounds[i][0], z.bounds[i][1], 0));

					var polygon = L.polygon(latLngs, z.options);
					polygon.addTo(map);
					zonePolygons.push(polygon);
				}
			});
		}

		// https://leafletjs.com/reference-1.4.0.html#control-layers
		var events = [];
		var visibleEvents = [];
		var eventTypes = [];

		function loadEvents() {
			showStatusMessage("Loading events...");
			eventTypes = [];
			$.get("events.php", { server: server, file: log }).done(function (data) {
				var result = JSON.parse(data);
				events = result.events;
				$( "#event-count" ).text(events.length);
				$( "#event-time-from" ).text(result.time.from);
				$( "#event-time-to" ).text(result.time.to);
				$( "#time-from" ).removeAttr("disabled");
				$( "#time-to" ).removeAttr("disabled");
				$( "#steamid" ).removeAttr("disabled");

				<?php if ($service->limits->filters->type): ?>
				for (var i = 0; i < events.length; i++) {
					var e = events[i];
					eventTypes[e.event_type] = e.event_type;
				}

				var $eventTypes = $("#event-types").html("");
				eventTypes.sort();
				for (var eventType in eventTypes)
				{
					if (!eventTypes.hasOwnProperty(eventType))
						continue;
					$eventTypes.append($("<option selected>").attr("value", eventType).text(eventTypes[eventType]));
				}

				if (event_types)
					$eventTypes.val(event_types).change();
				<?php endif; ?>

				time_from_default = result.time.first - 1;
				time_to_default = result.time.last + 1;
				timeFilter(result.time.filter);
				filterEvents();
				displayEvents();
			});
		}

		function filterEvents() {
			showStatusMessage("Filtering events...");
			visibleEvents = [];
			for (var i = 0; i < events.length; i++) {
				var e = events[i];

				<?php if ($service->limits->filters->type): ?>
				// Event type filters
				if (event_types && event_types.indexOf(e.event_type) === -1)
					continue;
				<?php endif; ?>

				<?php if ($service->limits->filters->time): ?>
				// Time filters
				if (e.event_time < $("#time-from").val())
					continue;
				if (e.event_time > $("#time-to").val())
					continue;
				<?php endif; ?>

				<?php if ($service->limits->filters->steamid): ?>
				// SteamID filter
				if (steamid64 && e.event_data.steamid64 != steamid64)
					continue;
				<?php endif; ?>

				visibleEvents.push(e);
			}
		}

		// https://github.com/Leaflet/Leaflet.markercluster
		var clusterGroup = L.markerClusterGroup({
			/*disableClusteringAtZoom:  ,*/
			maxClusterRadius: 30,
		});
		map.addLayer(clusterGroup);

		var paths = [];
		function paths_global__validate( id ) {
			if (typeof paths[id] === "undefined")
			{
				paths[id] = {};
				paths__validate(id, 'normal');
				paths__validate(id, 'kill');
			}
		}

		function paths__validate( id, type, options ) {
			if (typeof paths[id] === "undefined")
				paths_global__validate(id);

			if (typeof paths[id][type] === "undefined")
			{
				paths[id][type] = {
					id: 0,
					entries: {
						0: [],
					},
					options: options || {
						color: "#000000",
						weight: 1.75,
						opacity: 0.8,
					}
				};
			}
		}

		function paths__terminate( id, type ) {
			paths[id][type].id++;
			paths[id][type].entries[paths[id][type].id] = [];
		}

		function paths__push( id, type, data ) {
			paths[id][type].entries[paths[id][type].id].push(data);
		}

		function lineTo_foreach( targets, object, callback, callback_data ) {
			for (var propId in targets)
			{
				if (!targets.hasOwnProperty(propId))
					continue;

				object = object[propId];
				var prop = targets[propId];
				if (typeof prop === "object")
				{
					lineTo_foreach(prop, object, callback, callback_data);
				}
				else
				{
					callback(object[prop], callback_data);
				}
			}
		}

		var markers = [];
		var lines = [];
		function displayEvents() {
			showStatusMessage("Rendering events...");
			$("#event-count-filtered").text(visibleEvents.length);
			clusterGroup.clearLayers();
			markers = [];

			for (var i = 0; i < lines.length; i++) {
				map.removeLayer(lines[i]);
			}
			lines = [];

			paths = [];
			for (var i = 0; i < visibleEvents.length; i++) {
				var e = visibleEvents[i];
				if (e.event_data.position == null
					|| e.event_data.position.x == null
					|| e.event_data.position.y == null
					|| e.event_data.position.z == null)
				{
					console.log(e);
					continue;
				}

				var pin = pins['blue'];
				var tooltipContent = "";
				var popupContent = "";

				tooltipContent = e.event_time + ": <b>" + e.event_data.name + "</b>";
				popupContent = "<h6>" + e.event_type + " <small>" + e.event_time + "<a href='javascript:setTimeFilter(" + e.event_time_numeric + ", 15);'>&plusmn;15m</a></small></h6>";
				popupContent += "<b>" + e.event_data.name + "</b> (<a href=\"https://steamcommunity.com/profiles/" + e.event_data.steamid64 + "/\" target=\"_blank\">Steam profile</a>, <small><a href='javascript:$(\"#steamid\").val(\"" + e.event_data.steamid64  + "\").keyup();'>filtr</a></small>)";
				popupContent += "<br><pre class='mt-2'>" + JSON.stringify(e.event_data, null, 2) + "</pre>";

				var settings = eventSettings[e.event_type];
				if (settings)
				{
					pin = pins[settings.marker]
				}

				<?php if ($service->limits->extensions->visuals): ?>
				if ($("#connectEvents:checked").val()) {
					paths__validate(e.event_data.steamid64, 'normal', {
						color: "#000000",
						weight: 1.75,
						opacity: 0.8,
					});
					paths__push(e.event_data.steamid64, 'normal', getLatLngFromPosition(e.event_data.position));

					if (settings)
					{
						if (settings.terminatesSequence)
						{
							paths__terminate(e.event_data.steamid64, 'normal');
						}
					}
				}

				if ($("#visualizeAdditionalData:checked").val()) {
					if (settings && settings.lineTo)
					{
						paths__validate(e.event_data.steamid64, 'additional', settings.lineTo.options);
						lineTo_foreach(settings.lineTo.targets, e.event_data, function( targetPosition, e ) {
							paths__push(e.event_data.steamid64, 'additional', getLatLngFromPosition(e.event_data.position));
							paths__push(e.event_data.steamid64, 'additional', getLatLngFromPosition(targetPosition));
							paths__terminate(e.event_data.steamid64, 'additional');
						}, e);
					}
				}
				<?php endif; ?>

				var marker = L.marker(getLatLng(e.event_data.position.x, e.event_data.position.z, e.event_data.position.y), {
					//title: e.event_type,
					icon: pin,
				});

				marker.bindTooltip(tooltipContent);
				marker.bindPopup(popupContent);
				clusterGroup.addLayer(marker);
				//marker.addTo(clusterGroup);

				markers.push(marker);
			}

			for (var steamid in paths) {
				if (!paths.hasOwnProperty(steamid))
					continue;

				for (var type in paths[steamid]) {
					if (!paths[steamid].hasOwnProperty(type))
						continue;

					for (var id in paths[steamid][type].entries)
					{
						if (!paths[steamid][type].entries.hasOwnProperty(id))
							continue;

						lines.push(L.polyline(paths[steamid][type].entries[id], paths[steamid][type].options).addTo(map));
					}
				}
			}

			showStatusMessage("Events successfully processed!");
		}

		/*
		L.marker(getLatLng(0, 0)).addTo(map);
		L.marker(getLatLng(15360, 15360)).addTo(map);
		L.marker(getLatLng(0, 15360)).addTo(map);
		L.marker(getLatLng(15360, 0)).addTo(map);
		*/

		function setUrlParam(key, value) {
			const searchParams = new URLSearchParams(window.location.search);
			if (value == null)
				searchParams.delete(key);
			else
				searchParams.set(key, value);
			window.history.pushState(null, document.title, "?" + searchParams.toString());
		}

		$(function() {
			showStatusMessage("Loading available server list...");
			$.get("servers.php").done(function (data) {
				var servers = JSON.parse(data);

				<?php if ($service->limits->extensions->server): ?>
				for (var group in servers)
				{
					if (!servers.hasOwnProperty(group))
						continue;

					var g = $("<optgroup>").attr("label", group);
					for (var s in servers[group])
					{
						if (!servers[group].hasOwnProperty(s))
							continue;

						g.append($("<option>").attr("value", s).text(servers[group][s]));
					}
				}
				var $server = $( "#server" ).append(g);
				if (server)
					$server.val(server).change();

				<?php else: ?>
				for (var group in servers)
				{
					if (!servers.hasOwnProperty(group))
						continue;

					for (var s in servers[group])
					{
						if (!servers[group].hasOwnProperty(s))
							continue;

						server = s;
						<?php if ($service->limits->extensions->log): ?>
						loadLogfiles();
						<?php else: ?>
						loadEvents();
						loadZones();
						<?php endif; ?>
					}
				}
				<?php endif; ?>
			});
		});
	</script>
</body>
</html>