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
			<select class="leaflet-control form-control" id="server">
				<option selected disabled>-- Select Server --</option>
			</select>
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
						<th class="pr-2">Time</th>
						<td id="event-time">Unknown</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div class="leaflet-control leaflet-control-custom rounded p-0" style="font-size: 12px"><pre class="p-1 m-0" id="status-bar" style="display: none;"></pre></div>
		</div>
		<div class="leaflet-bottom leaflet-left" id="controls-left">
			<div class="leaflet-control input-group">
				<input type="text" title="SteamID64" placeholder="76561198055158908" class=" form-control" id="steamid" disabled>
				<div class="input-group-append">
					<button type="button" class="btn btn-danger" onclick="$('#steamid').val('').keyup();"><i class="fa fa-times"></i></button>
				</div>
			</div>
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
			<div class="leaflet-control leaflet-control-custom rounded">
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="connectEvents" value="1" onchange="displayEvents(); setUrlParam('connect_events', $('#connectEvents:checked').val());" <?php if (@$_GET['connect_events'] == 1): ?>checked<?php endif; ?>>
					<label class="custom-control-label" for="connectEvents">Visually connect player events</label>
				</div>
				<div class="custom-control custom-checkbox">
					<input type="checkbox" class="custom-control-input" id="connectKillEvents" value="1" onchange="displayEvents(); setUrlParam('connect_kill_events', $('#connectKillEvents:checked').val());" <?php if (@$_GET['connect_kill_events'] == 1): ?>checked<?php endif; ?> disabled>
					<label class="custom-control-label" for="connectKillEvents">Visually connect kill events</label>
				</div>
			</div>
			<div class="leaflet-control leaflet-control-custom rounded">
				<select style="height: 140px" class="pl-1 pr-2 form-control" id="event-types" multiple>
					<option disabled>Select log first.</option>
				</select>
				<button type="button" class="btn btn-sm btn-danger w-100 mt-1" onclick="event_types__reset();"><i class="fa fa-times"></i></button>
			</div>
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
	<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
	<script type="text/javascript">
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

		var server;
		<?php if (@$_GET['server']): ?>
		server = "<?=$_GET['server']?>";
		<?php endif; ?>
		$( "#server" ).on('change', function() {
			var $this = $( this );
			server = $this.val();
			setUrlParam('server', $this.val());
			$.get("logs.php", { server: $this.val() }).done(function (data) {
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
		});

		var time_from;
		<?php if (@$_GET['time_from']): ?>
		time_from = "<?=$_GET['time_from']?>";
		<?php endif; ?>
		$("#time-from").on('change', function () {
			var $this = $( this );
			if (!$this.val())
				$this.val(0);
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

		var time_to;
		<?php if (@$_GET['time_to']): ?>
		time_to = "<?=$_GET['time_to']?>";
		<?php endif; ?>
		$("#time-to").on('change', function () {
			var $this = $( this );
			if (!$this.val())
				$this.val(2 * 24 * 3600);
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
			$('#time-from').val('').change();
			$('#time-to').val('').change();
		}

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

		var statusBarTimeout;
		function showStatusMessage(message, timeout) {
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

		// https://github.com/pointhi/leaflet-color-markers
		var pinBlue = new L.Icon({
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
			iconSize: [25, 41],
			iconAnchor: [12, 41],
			popupAnchor: [1, -34],
			shadowSize: [41, 41]
		});

		var pinRed = new L.Icon({
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
			iconSize: [25, 41],
			iconAnchor: [12, 41],
			popupAnchor: [1, -34],
			shadowSize: [41, 41]
		});

		var pinGreen = new L.Icon({
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
			iconSize: [25, 41],
			iconAnchor: [12, 41],
			popupAnchor: [1, -34],
			shadowSize: [41, 41]
		});

		var pinOrange = new L.Icon({
			iconUrl: 'https://cdn.rawgit.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-orange.png',
			shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
			iconSize: [25, 41],
			iconAnchor: [12, 41],
			popupAnchor: [1, -34],
			shadowSize: [41, 41]
		});

		/*
		L.tileLayer('map/{z}/{x}/{y}.png', {
			attribution: '&copy; 2019 <a href="https://dayz-sa.cz/">DayZ-SA.cz</a>, Daniel Dolejška',
			errorTileUrl: 'map/0.png',
			reuseTiles: true,
			tms: true,
			noWrap: true,
		}).addTo(map);*/

		var viewTop = L.tileLayer('https://maps.izurvive.com/maps/CH-Top/1.11.7/tiles/{z}/{x}/{y}.png', {
			attribution: 'Map data &copy; <a href="https://www.izurvive.com/">iZurvive</a>; Event processor &copy; <a href="https://dayz-sa.cz/">DayZ-SA.cz</a>, Daniel Dolejška',
			bounds: [[0, 0], [256, 256]],
			reuseTiles: true,
			tms: true,
			noWrap: true,
			minNativeZoom: 1,
			maxNativeZoom: 7,
		});
		viewTop.getTileUrl = function (coords) {
			//coords.x = coords.x;
			coords.y = -coords.y - 1;
			return L.TileLayer.prototype.getTileUrl.bind(viewTop)(coords);
		};

		var viewSatellite = L.tileLayer('https://maps.izurvive.com/maps/CH-Sat/1.11.7/tiles/{z}/{x}/{y}.png', {
			attribution: 'Map data &copy; <a href="https://www.izurvive.com/">iZurvive</a>; Event processor &copy; <a href="https://dayz-sa.cz/">DayZ-SA.cz</a>, Daniel Dolejška',
			bounds: [[0, 0], [256, 256]],
			reuseTiles: true,
			tms: true,
			noWrap: true,
			minNativeZoom: 1,
			maxNativeZoom: 7,
		});
		viewSatellite.getTileUrl = function (coords) {
			//coords.x = coords.x;
			coords.y = -coords.y - 1;
			return L.TileLayer.prototype.getTileUrl.bind(viewSatellite)(coords);
		};

		var views = {
			"Top": viewTop,
			"Satellite": viewSatellite,
		};

		var map = L.map('map', {
			center: [128, 128],
			preferCanvas: true,
			minZoom: 1,
			maxZoom: 12,
			zoom: 2,
			/*
			zoomSnap: 0.25,
			zoomDelta: 0.25,
			wheelDebounceTime: 0,
			wheelPxPerZoomLevel: 1000,
			*/
			crs: L.CRS.Simple,
		});
		viewTop.addTo(map);
		L.control.layers(views, null, {
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
				Math.floor(secs / 3600),
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

			// TODO: out of bounds?
			$( "#time-from" ).val(low || 0).change();
			$( "#time-to" ).val(high || 2 * 24 * 3600).change();
		}

		function generateTimeFilter(from, to) {
			var fromInts = timeToInts(from);
			var current = fromInts[0] * 3600 + Math.ceil(fromInts[1] * 60 / 300) * 300;

			var array = {};
			array[from] = timeToString(from);
			while (current < to)
			{
				array[current] = timeToString(current);
				current += 5 * 60;
			}
			array[to] = timeToString(to);

			var $timeFrom = $( "#time-from" ).html("").append($("<option>").attr("value", 0).text("From first"));
			var $timeTo = $( "#time-to" ).html("");
			for (var time in array)
			{
				if (!array.hasOwnProperty(time))
					continue;
				$timeFrom.append($("<option>").attr("value", time).text(array[time]));
				$timeTo.append($("<option>").attr("value", time).text(array[time]));
			}
			$timeTo.append($("<option>").attr("value", 2 * 24 * 3600).text("Until last"));
			$timeFrom.val(time_from || 0).change();
			$timeTo.val(time_to || 2 * 24 * 3600).change();
		}

		// https://leafletjs.com/reference-1.4.0.html#control-layers
		var events = [];
		var visibleEvents = [];
		var eventTypes = [];

		function loadEvents() {
			showStatusMessage("Loading events...");
			eventTypes = [];
			$.get("log.php", { server: server, file: log }).done(function (data) {
				var eventsData = JSON.parse(data);
				events = eventsData.events;
				$( "#event-count" ).text(events.length);
				$( "#event-time" ).text(eventsData.time.from + "–" + eventsData.time.to);
				$( "#time-from" ).removeAttr("disabled");
				$( "#time-to" ).removeAttr("disabled");
				$( "#steamid" ).removeAttr("disabled");

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

				generateTimeFilter(eventsData.time.from_numeric, eventsData.time.to_numeric);
				filterEvents();
				displayEvents();
			});
		}

		function filterEvents() {
			showStatusMessage("Filtering events...");
			visibleEvents = [];
			for (var i = 0; i < events.length; i++) {
				var e = events[i];

				// Event type filters
				if (event_types && event_types.indexOf(e.event_type) === -1)
					continue;

				// Time filters
				if (e.event_time_numeric < $("#time-from").val())
					continue;
				if (e.event_time_numeric > $("#time-to").val())
					continue;

				// SteamID filter
				if (steamid64 && e.event_data.steamid64 != steamid64)
					continue;

				visibleEvents.push(e);
			}
		}

		var clusterGroup = L.markerClusterGroup({
			/*disableClusteringAtZoom:  ,*/
			maxClusterRadius: 30,
		});
		map.addLayer(clusterGroup);

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

			var paths = [];
			for (var i = 0; i < visibleEvents.length; i++) {
				var e = visibleEvents[i];
				/*
				if (e.event_data.position.x == null
					|| e.event_data.position.y == null
					|| e.event_data.position.z == null)
					continue;*/

				var pin = pinBlue;
				var tooltipContent = "";
				var popupContent = "";

				tooltipContent = e.event_time + ": <b>" + e.event_data.name + "</b>";
				popupContent = "<h6>" + e.event_type + " <small>" + e.event_time + "<a href='javascript:setTimeFilter(" + e.event_time_numeric + ", 15);'>&plusmn;15m</a></small></h6>";
				popupContent += "<b>" + e.event_data.name + "</b> (<a href=\"https://steamcommunity.com/profiles/" + e.event_data.steamid64 + "/\" target=\"_blank\">Steam profile</a>, <small><a href='javascript:$(\"#steamid\").val(\"" + e.event_data.steamid64  + "\").keyup();'>filtr</a></small>)";
				popupContent += "<br><pre class='mt-2'>" + JSON.stringify(e.event_data, null, 2) + "</pre>";

				if (e.event_type === "KILLED_BY_PLAYER" || e.event_type === "KILLED_BY_ZOMBIE" || e.event_type === "KILLED_BY_CAR") {
					pin = pinRed;
				} else if (e.event_type === "CONNECTED") {
					pin = pinGreen;
				} else if (e.event_type === "DISCONNECTED") {
					pin = pinOrange;
				}

				if ($("#connectEvents:checked").val()) {
					if (typeof paths[e.event_data.steamid64] === "undefined")
					{
						paths[e.event_data.steamid64] = {
							id: 0,
							paths: {
								0: []
							}
						};
					}
					paths[e.event_data.steamid64].paths[paths[e.event_data.steamid64].id].push(getLatLng(e.event_data.position.x, e.event_data.position.z, e.event_data.position.y));

					if (e.event_type === "DISCONNECT"
						|| e.event_type === "KILLED_BY_PLAYER"
						|| e.event_type === "KILLED_BY_ZOMBIE"
						|| e.event_type === "KILLED_BY_CAR"
						|| e.event_type === "SUICIDE")
					{
						paths[e.event_data.steamid64].id++;
						paths[e.event_data.steamid64].paths[paths[e.event_data.steamid64].id] = [];
					}
				}

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
				for (var path in paths[steamid].paths)
				{
					if (!paths[steamid].paths.hasOwnProperty(path))
						continue;
					lines.push(L.polyline(paths[steamid].paths[path], {
						color: "#000000",
						weight: 1.75,
						opacity: 0.8,
					}).addTo(map));
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
			});
		});
	</script>
</body>
</html>