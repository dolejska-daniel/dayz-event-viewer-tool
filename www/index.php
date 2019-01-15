<?php

?>
<!doctype html>

<html lang="cs">
<head>
	<meta charset="utf-8">

	<title>DayZ-SA.cz :: ServerLog Map Tool</title>

	<meta name="description" content="">
	<meta name="author" content="Daniel Dolejška">

	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.4.0/leaflet.css" rel="stylesheet" integrity="sha256-YR4HrDE479EpYZgeTkQfgVJq08+277UXxMLbi/YP69o=" crossorigin="anonymous" />
	<style>
		body {
			margin: 0;
			padding: 0;
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
			<select class="leaflet-control form-control" id="server">
				<option selected disabled>-- Select Server --</option>
			</select>
			<select class="leaflet-control form-control" id="log" disabled>
				<option selected disabled>-- Select Log --</option>
				<optgroup label="DONOR" id="log-group">
					<option>DayZServer_x64_2019_01_11_200416631.ADM</option>
				</optgroup>
			</select>
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
		</div>
		<div class="leaflet-bottom leaflet-left">
			<div class="leaflet-control input-group">
				<input type="text" title="SteamID64" placeholder="76561198055158908" class=" form-control" id="steamid" disabled>
				<div class="input-group-append">
					<button type="button" class="btn btn-danger" onclick="$('#steamid').val('').keyup();">Reset</button>
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
					<button type="button" class="btn btn-danger" onclick="$('#time-from').val('').change();$('#time-to').val('').change();">Reset</button>
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
		</div>
	</div>
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
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
				if (log)
					$log.val(log).change();
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
	</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.4.0/leaflet.js" integrity="sha256-6BZRSENq3kxI4YYBDqJ23xg0r1GwTHEpvp3okdaIqBw=" crossorigin="anonymous"></script>
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

		var map = L.map('map', {
			center: [128, 128],
			zoom: 2,
			preferCanvas: true,
			minZoom: 1,
			maxZoom: 7,
			/*
			zoomSnap: 0.25,
			zoomDelta: 0.25,
			wheelDebounceTime: 0,
			wheelPxPerZoomLevel: 1000,
			*/
			crs: L.CRS.Simple,
		});
		//map.setMaxBounds(map.getBounds());

		//  Right click
		map.on("contextmenu", function (event) {
			console.log("Coordinates: %s => %o, %o", event.latlng.toString(), getXY(event.latlng), getLatLng(getXY(event.latlng)[0], getXY(event.latlng)[1]));
			//L.marker(event.latlng).addTo(map);
		});

		/*
		L.tileLayer('map/{z}/{x}/{y}.png', {
			attribution: '&copy; 2019 <a href="https://dayz-sa.cz/">DayZ-SA.cz</a>, Daniel Dolejška',
			errorTileUrl: 'map/0.png',
			reuseTiles: true,
			tms: true,
			noWrap: true,
		}).addTo(map);*/

		var tileLayer = L.tileLayer('https://maps.izurvive.com/maps/CH-Top/1.11.7/tiles/{z}/{x}/{y}.png', {
			attribution: '<a href="https://www.izurvive.com/">iZurvive</a>, &copy; 2019 <a href="https://dayz-sa.cz/">DayZ-SA.cz</a>, Daniel Dolejška',
			bounds: [[0, 0], [256, 256]],
			reuseTiles: true,
			tms: true,
			noWrap: true,
		});

		tileLayer.getTileUrl = function (coords) {
			//coords.x = coords.x;
			coords.y = -coords.y - 1;
			return L.TileLayer.prototype.getTileUrl.bind(tileLayer)(coords);
		};

		tileLayer.addTo(map);

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

		function loadEvents() {
			$.get("log.php", { file: log }).done(function (data) {
				var eventsData = JSON.parse(data);
				events = eventsData.events;
				$( "#event-count" ).text(events.length);
				$( "#event-time" ).text(eventsData.time.from + "–" + eventsData.time.to);
				$( "#time-from" ).removeAttr("disabled");
				$( "#time-to" ).removeAttr("disabled");
				$( "#steamid" ).removeAttr("disabled");

				generateTimeFilter(eventsData.time.from_numeric, eventsData.time.to_numeric);
				filterEvents();
				displayEvents();
			});
		}

		function filterEvents() {
			visibleEvents = [];
			for (var i = 0; i < events.length; i++) {
				var e = events[i];

				// SteamID filter
				if (steamid64 && e.event_data.steamid64 != steamid64)
					continue;

				if (e.event_time_numeric < $("#time-from").val())
					continue;

				if (e.event_time_numeric > $("#time-to").val())
					continue;

				visibleEvents.push(e);
			}
		}

		var markers = [];
		var lines = [];
		function displayEvents() {
			$("#event-count-filtered").text(visibleEvents.length);

			for (var i = 0; i < markers.length; i++) {
				map.removeLayer(markers[i]);
			}
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
				popupContent = "<h6>" + e.event_type + " <small>" + e.event_time + "<a href='javascript:$(\"#steamid\").val(\"" + e.event_data.steamid64  + "\").keyup();'>&plusmn;15m</a></small></h6>";
				popupContent += "<b>" + e.event_data.name + "</b> (" + e.event_data.steamid64 + " <small><a href='javascript:$(\"#steamid\").val(\"" + e.event_data.steamid64  + "\").keyup();'>filtr</a></small>)";
				popupContent += "<br><pre class='mt-2'>" + JSON.stringify(e.event_data, null, 2) + "</pre>";

				if (e.event_type === "KILLED_BY_PLAYER" || e.event_type === "KILLED_BY_ZOMBIE") {
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
					else
					{
						if (e.event_type === "DISCONNECT"
							|| e.event_type === "KILLED_BY_PLAYER"
							|| e.event_type === "SUICIDE")
						{
							paths[e.event_data.steamid64].id++;
							paths[e.event_data.steamid64].paths[paths[e.event_data.steamid64].id] = [];
						}
					}
					paths[e.event_data.steamid64].paths[paths[e.event_data.steamid64].id].push(getLatLng(e.event_data.position.x, e.event_data.position.z, e.event_data.position.y));
				}

				var marker = L.marker(getLatLng(e.event_data.position.x, e.event_data.position.z, e.event_data.position.y), {
					//title: e.event_type,
					icon: pin,
				});

				marker.bindTooltip(tooltipContent);
				marker.bindPopup(popupContent);
				marker.addTo(map);

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
		}

		/*
		L.marker(getLatLng(0, 0)).addTo(map);
		L.marker(getLatLng(15360, 15360)).addTo(map);
		L.marker(getLatLng(0, 15360)).addTo(map);
		L.marker(getLatLng(15360, 0)).addTo(map);
		*/

		function setUrlParam(key, value) {
			const searchParams = new URLSearchParams(window.location.search);
			searchParams.set(key, value);
			window.history.pushState(null, document.title, "?" + searchParams.toString());
		}

		$(function() {
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