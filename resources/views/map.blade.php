@extends('layouts.app')

@section('content')

<!-- ================= ACTION BAR (FIX DI ATAS) ================= -->
<div class="action-bar">


    <button onclick="smartSalesMode()">Smart Sales</button>
    <button onclick="focusFollowUp()">FollowUp Today</button>
    <button onclick="resetMap()">Reset Map</button>

    <div style="position:relative;display:inline-block;">
        <input type="text" id="searchInput" placeholder="Cari nama lokasi..." autocomplete="off">
        <div id="suggestions" style="
            display:none;
            position:absolute;
            top:100%;
            left:0;
            right:0;
            background:#fff;
            border:1px solid #dee2e6;
            border-radius:0 0 8px 8px;
            box-shadow:0 4px 12px rgba(0,0,0,.15);
            z-index:9999;
            max-height:220px;
            overflow-y:auto;
            min-width:280px;
        "></div>
    </div>
    <button onclick="searchLocation()">Cari</button>

</div>


<!-- ================= MAIN ================= -->
<div class="map-layout">

    <!-- SIDEBAR -->
    <div class="map-panel">

        <!-- Counter realtime -->
        <div id="markerCounter" style="
            background:#f8f9fa;border-radius:8px;padding:8px 12px;
            text-align:center;font-size:13px;color:#495057;margin-bottom:4px;
            border:1px solid #dee2e6;">
            Menampilkan <strong id="counterNum">0</strong> lokasi
        </div>

        <!-- Stat box per kategori -->
        <div class="stat-box">
            <span>Education</span>
            <strong>{{ count($education ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>SPPG</span>
            <strong>{{ count($sppg ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>KDMP</span>
            <strong>{{ count($kdmp ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>Faskes</span>
            <strong>{{ count($faskes ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>Bank</span>
            <strong>{{ count($bank ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>Koperasi</span>
            <strong>{{ count($koperasi ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>Hotel</span>
            <strong>{{ count($hotel ?? []) }}</strong>
        </div>

        <div class="stat-box">
            <span>Wisata</span>
            <strong>{{ count($wisata ?? []) }}</strong>
        </div>

        <!-- Filter Kategori -->
        <label style="font-size:11px;color:#6c757d;margin-bottom:2px;">Kategori</label>
        <select id="filter">
            <option value="all">Semua Kategori</option>
            <option value="edu">Education</option>
            <option value="sppg">SPPG</option>
            <option value="kdmp">KDMP</option>
            <option value="faskes">Faskes</option>
            <option value="bank">Bank</option>
            <option value="koperasi">Koperasi</option>
            <option value="hotel">Hotel</option>
            <option value="wisata">Wisata</option>
        </select>

        <!-- Filter Status -->
        <label style="font-size:11px;color:#6c757d;margin-top:8px;margin-bottom:2px;">Status</label>
        <div id="statusFilter" style="display:flex;flex-direction:column;gap:4px;">

            <label class="status-filter-item" style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:5px 8px;border-radius:6px;border:1px solid #dee2e6;font-size:13px;">
                <input type="checkbox" value="WIN" checked onchange="applyFilters()">
                <span style="width:10px;height:10px;border-radius:50%;background:#28a745;display:inline-block;"></span>
                WIN
            </label>

            <label class="status-filter-item" style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:5px 8px;border-radius:6px;border:1px solid #dee2e6;font-size:13px;">
                <input type="checkbox" value="LOSE" checked onchange="applyFilters()">
                <span style="width:10px;height:10px;border-radius:50%;background:#dc3545;display:inline-block;"></span>
                LOSE
            </label>

            <label class="status-filter-item" style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:5px 8px;border-radius:6px;border:1px solid #dee2e6;font-size:13px;">
                <input type="checkbox" value="NOT_VISIT" checked onchange="applyFilters()">
                <span style="width:10px;height:10px;border-radius:50%;background:#adb5bd;display:inline-block;"></span>
                NOT VISIT
            </label>

            <label class="status-filter-item" style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:5px 8px;border-radius:6px;border:1px solid #dee2e6;font-size:13px;">
                <input type="checkbox" value="UNKNOWN" checked onchange="applyFilters()">
                <span style="width:10px;height:10px;border-radius:50%;background:#ffc107;display:inline-block;"></span>
                UNKNOWN
            </label>

        </div>

        <label style="margin-top:8px;">
            <input type="checkbox" id="toggleFollowUp" checked>
            Follow Up
        </label>

    </div>

    <!-- MAP -->
    <div class="map-container">
        <div id="map"></div>
    </div>

</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>

// ================= MAIN MAP =================

var map = L.map('map').setView([-7.98,112.63], 12);
var followUpLayer = L.layerGroup().addTo(map);

var routePoints = [];
var routeLine = null;
var userMarker = null;
var routeMarkers = [];
var smartRouteLine = null;

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'&copy; OpenStreetMap'
}).addTo(map);


// ================= ICON CREATOR =================

function getCustomIcon(type, status, isFollowUpToday = false) {

    let iconClass = "fa-circle";

    if (type === "edu") iconClass = "fa-book";
    if (type === "faskes") iconClass = "fa-syringe";
    if (type === "sppg") iconClass = "fa-utensils";
    if (type === "kdmp") iconClass = "fa-store";
    if (type === "bank") iconClass = "fa-building-columns";
    if (type === "koperasi") iconClass = "fa-handshake";
    if (type === "hotel") iconClass = "fa-bed";
    if (type === "wisata") iconClass = "fa-umbrella-beach";

    let color = "#6c757d";

    if (status) {

        status = status.toLowerCase();

        if (status === "win") color = "#28a745";
        if (status === "lose") color = "#dc3545";
        if (status === "unknown") color = "#ffd61e";
        if (status === "not_visit") color = "#adb5bd";

    }

    if (isFollowUpToday) {
        color = "#ff0000";
    }

    return L.divIcon({
        html: `
        <div class="custom-marker ${isFollowUpToday ? 'follow-up' : ''}" 
             style="border-color:${color}">
            <i class="fa-solid ${iconClass}" 
               style="color:${color}"></i>
        </div>
        `,
        className: '',
        iconSize: [36, 36],
        iconAnchor: [18, 18]
    });

}


// ================= STATUS BADGE =================

function getStatusBadge(status) {

    if (!status) status = 'not_visit';

    status = status.toLowerCase();

    if (status === 'win')
        return `<span class="badge badge-win">WIN</span>`;

    if (status === 'lose')
        return `<span class="badge badge-lose">LOSE</span>`;

    if (status === 'unknown')
        return `<span class="badge badge-unknown">UNKNOWN</span>`;

    if (status === 'not_visit')
        return `<span class="badge badge-notvisit">NOT VISIT</span>`;

}


// ================= FOLLOW UP CHECK =================

function isToday(dateStr){

    if(!dateStr) return false;

    try{

        const parts=dateStr.split('/');
        if(parts.length===3){

            const formatted=`${parts[2]}-${parts[1]}-${parts[0]}`;
            const parsed=new Date(formatted);

            const today=new Date();

            const todayStr=today.toISOString().split('T')[0];
            const parsedStr=parsed.toISOString().split('T')[0];

            return parsedStr===todayStr;

        }

        return false;

    }catch(e){
        return false;
    }

}


// ================= POPUP =================

function generatePopup(item, type) {

    // encode di sini, SEBELUM masuk ke HTML string
    var hasilEnc   = encodeURIComponent(item.hasil    ?? '');
    var followEnc  = encodeURIComponent(item.follow_up ?? '');
    var internetVal = (item.nomor_internet ?? '').replace(/'/g, "\\'");

    var editBtn = isLoggedIn
        ? `<button class="btn-edit" onclick="openEditForm(
                ${item.id},
                '${item.sheet}',
                '${item.status}',
                '${item.visit}',
                '${internetVal}',
                '${hasilEnc}',
                '${followEnc}'
            )">✏ Edit Data</button>`
        : `<a href="/login"
               style="display:inline-block;margin-top:8px;padding:6px 14px;
                      background:#ed1c24;color:#fff;border-radius:6px;
                      font-size:12px;font-weight:bold;text-decoration:none;">
               🔑 Login untuk Edit
            </a>`;

    var navBtn = `
        <a href="https://www.google.com/maps?q=${item.lat},${item.lng}"
           target="_blank" class="btn-nav">🧭 Navigate</a>`;

    if (type === 'edu') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>NPSN:</b> ${item.npsn ?? '-'}<br>
                <b>Jenjang:</b> ${item.jenjang ?? '-'}<br>
                <b>NIPNAS:</b> ${item.nipnas ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'sppg') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'kdmp') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Cooperative ID:</b> ${item.cooperative_id ?? '-'}<br>
                <b>Registration:</b> ${item.registrationdate ?? '-'}<br>
                <b>PIC:</b> ${item.pic ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'faskes') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Jenis Faskes:</b> ${item.jenis ?? '-'}<br>
                <b>NIPNAS:</b> ${item.nipnas ?? '-'}<br>
                <b>Nomor telp:</b> ${item.nomor_telephone ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'bank') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Tanggal PS:</b> ${item.tanggal_ps || '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'koperasi') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Tanggal PS:</b> ${item.tanggal_ps || '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'hotel') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Tanggal PS:</b> ${item.tanggal_ps || '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }

    if (type === 'wisata') {
        return `
            <div class="popup-box">
                <strong>${item.nama}</strong>
                <hr>
                <b>Status:</b> ${getStatusBadge(item.status)}<br>
                <b>Alamat:</b> ${item.alamat ?? '-'}<br>
                <b>Nomor Internet:</b> ${item.nomor_internet ?? '-'}<br>
                <b>Visiting:</b> ${item.visit ?? '-'}<br>
                <b>Follow Up:</b> ${item.follow_up ?? '-'}<br>
                <b>Tanggal PS:</b> ${item.tanggal_ps || '-'}<br>
                <b>Hasil:</b> ${item.hasil ?? '-'}<br>
                ${navBtn}${editBtn}
            </div>`;
    }


}


// ================= LOAD MARKERS =================

var allMarkers=[];

function addMarkers(data,type,targetMap){

data.forEach(function(item){

    if(!item.lat || !item.lng) return;

    var followToday=false;

    if(item.follow_up && isToday(item.follow_up)){
        followToday=true;
    }

    var marker = L.marker(
        [item.lat,item.lng],
        {icon:getCustomIcon(type,item.status,followToday)}
    )
    .bindPopup(generatePopup(item,type))
    .addTo(targetMap);

    // simpan info marker
    marker.type    = type;
    marker.nama    = item.nama.toLowerCase();
    marker.status  = item.status;
    marker.followUp = item.follow_up;
    marker.itemId  = item.id;

    if(targetMap === map){
        allMarkers.push(marker);
    }

    if(followToday && targetMap === map){

        var circle = L.circleMarker([item.lat,item.lng],{
            radius:16,
            color:"red",
            weight:3,
            fill:false
        }).addTo(followUpLayer);

        circle.type = type; // simpan kategori
    }

});

}


// ================= LOAD DATA =================

var education=@json($education ?? []);
var sppg=@json($sppg ?? []);
var kdmp=@json($kdmp ?? []);
var faskes=@json($faskes ?? []);
var bank=@json($bank ?? []);
var koperasi=@json($koperasi ?? []);
var hotel=@json($hotel ?? []);
var wisata=@json($wisata ?? []);

// Auth state — dipakai untuk tampilkan/sembunyikan tombol Edit
var isLoggedIn = {{ session('auth_user') ? 'true' : 'false' }};

addMarkers(education,'edu',map);
addMarkers(sppg,'sppg',map);
addMarkers(kdmp,'kdmp',map);
addMarkers(faskes,'faskes',map);
addMarkers(bank,'bank',map);
addMarkers(koperasi,'koperasi',map);
addMarkers(hotel,'hotel',map);
addMarkers(wisata,'wisata',map);

// ================= FILTER (KATEGORI + STATUS) =================

function getActiveStatuses() {
    return Array.from(document.querySelectorAll('#statusFilter input[type=checkbox]:checked'))
        .map(cb => cb.value);
}

function updateCounter(count) {
    document.getElementById('counterNum').textContent = count;
}

function applyFilters() {
    var selectedKategori = document.getElementById('filter').value;
    var activeStatuses   = getActiveStatuses();

    var visible = 0;
    var visibleMarkerLatLngs = []; // ← tambah ini

    allMarkers.forEach(function(marker) {
        var matchKategori = (selectedKategori === 'all' || marker.type === selectedKategori);
        var markerStatus  = (marker.status || 'NOT_VISIT').toUpperCase();
        var matchStatus   = activeStatuses.includes(markerStatus);

        if (matchKategori && matchStatus) {
            marker.addTo(map);
            visible++;
            visibleMarkerLatLngs.push(marker.getLatLng().toString()); // ← tambah ini
        } else {
            map.removeLayer(marker);
        }
    });

    // followup circles ikut filter kategori DAN hanya tampil kalau markernya visible
    followUpLayer.eachLayer(function(layer) {
        var matchKategori = (selectedKategori === 'all' || layer.type === selectedKategori);
        var latLngStr = layer.getLatLng().toString();
        var markerVisible = visibleMarkerLatLngs.includes(latLngStr); // ← cek ini

        if (matchKategori && markerVisible) {
            layer.addTo(map);
        } else {
            map.removeLayer(layer);
        }
    });

    updateCounter(visible);
}

// filter kategori trigger applyFilters
document.getElementById('filter').addEventListener('change', function() {
    applyFilters();
});

// inisialisasi counter setelah marker dimuat
setTimeout(function() {
    updateCounter(allMarkers.length);
}, 100);


// ================= SEARCH =================

function searchLocation(){

var keyword=document.getElementById("searchInput").value.trim().toLowerCase();
if(!keyword) return;

var coordMatch=keyword.match(/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/);

if(coordMatch){

var lat=parseFloat(coordMatch[1]);
var lng=parseFloat(coordMatch[2]);

map.setView([lat,lng],16);

L.marker([lat,lng])
.addTo(map)
.bindPopup("📍 Titik Koordinat")
.openPopup();

return;
}

var foundMarkers=allMarkers.filter(marker=>marker.nama.includes(keyword));

if(foundMarkers.length===0){
    alert("Data tidak ditemukan");
    return;
    }

if(foundMarkers.length>1){
    var group=new L.featureGroup(foundMarkers);
    map.fitBounds(group.getBounds());
    }else{
    map.setView(foundMarkers[0].getLatLng(),15);
    foundMarkers[0].openPopup();
    }

}

var suggestionBox = document.getElementById("suggestions");
var searchInput   = document.getElementById("searchInput");

searchInput.addEventListener("input", function(){

    var keyword = this.value.toLowerCase();

    suggestionBox.innerHTML = "";

    if(!keyword){
        suggestionBox.style.display = "none";
        return;
    }

    var matches = allMarkers.filter(function(marker){

        return marker.nama.includes(keyword);

    });

    if(matches.length === 0){
        suggestionBox.style.display = "none";
        return;
    }

    matches.slice(0,10).forEach(function(marker){

        var div = document.createElement("div");

        // warna badge per tipe
        var typeColor = {edu:'#0d6efd',sppg:'#fd7e14',kdmp:'#6f42c1',faskes:'#20c997'};
        var statusColor = {WIN:'#28a745',LOSE:'#dc3545',NOT_VISIT:'#adb5bd',UNKNOWN:'#ffc107'};
        var st = (marker.status || 'NOT_VISIT').toUpperCase();

        div.style.cssText = "padding:8px 12px;cursor:pointer;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;";
        div.innerHTML = `
            <span>
                <strong style="font-size:13px;">${marker.nama}</strong><br>
                <small style="color:#6c757d;">${marker.type.toUpperCase()}</small>
            </span>
            <span style="
                font-size:11px;font-weight:bold;padding:2px 7px;border-radius:10px;
                background:${statusColor[st] || '#adb5bd'};color:#fff;
            ">${st.replace('_',' ')}</span>
        `;

        div.addEventListener("mouseenter", function(){ this.style.background = "#f8f9fa"; });
        div.addEventListener("mouseleave", function(){ this.style.background = "#fff"; });

        div.addEventListener("click", function(){
            map.setView(marker.getLatLng(), 16);
            marker.openPopup();
            searchInput.value = marker.nama;
            suggestionBox.style.display = "none";
        });

        suggestionBox.appendChild(div);

    });

    suggestionBox.style.display = "block";

});

document.addEventListener("click", function(e){

    if(!searchInput.contains(e.target)){
        suggestionBox.style.display = "none";
    }

});



// ================= FOLLOWUP TOGGLE =================

document.getElementById("toggleFollowUp").addEventListener("change",function(){

    if(this.checked){
        map.addLayer(followUpLayer);
        }else{
        map.removeLayer(followUpLayer);
        }

    });


// ================= OSRM ROUTING HELPER =================

async function getOSRMRoute(waypoints) {
    var coords = waypoints.map(function(p){
        return p.lng + ',' + p.lat;
    }).join(';');

    var url = 'https://router.project-osrm.org/route/v1/driving/' + coords
            + '?overview=full&geometries=geojson&steps=false';

    var res  = await fetch(url);
    var json = await res.json();

    if (!json.routes || json.routes.length === 0) return null;

    return json.routes[0].geometry.coordinates.map(function(c){
        return L.latLng(c[1], c[0]);
    });
}

async function drawOSRMRoute(startLatLng, selectedMarkers, lineVarName, color, label) {

    if (window[lineVarName]) {
        map.removeLayer(window[lineVarName]);
        window[lineVarName] = null;
    }

    var waypoints = [startLatLng].concat(
        selectedMarkers.map(function(t){ return t.marker.getLatLng(); })
    );

    var toast = showRoutingToast('🗺️ Menghitung rute...');

    try {
        var routeLatLngs = await getOSRMRoute(waypoints);
        toast.remove();

        if (!routeLatLngs) {
            routeLatLngs = waypoints;
            showToast('⚠️ OSRM gagal, pakai garis lurus');
        }

        window[lineVarName] = L.polyline(routeLatLngs, {
            color: color,
            weight: 5,
            opacity: 0.85,
        }).addTo(map);

        // fit bounds ke waypoint target (bukan seluruh rute), supaya marker tetap fokus
        var waypointBounds = L.latLngBounds(waypoints);
        map.fitBounds(waypointBounds, { padding:[60,60] });

        var totalDist = 0;
        for (var i = 0; i < waypoints.length - 1; i++) {
            totalDist += waypoints[i].distanceTo(waypoints[i+1]);
        }
        var km = (totalDist / 1000).toFixed(1);

        showToast(label + ' • ' + selectedMarkers.length + ' titik • ~' + km + ' km');

    } catch(e) {
        toast.remove();
        window[lineVarName] = L.polyline(waypoints, { color: color, weight: 5 }).addTo(map);
        map.fitBounds(window[lineVarName].getBounds());
        showToast('⚠️ Offline mode — garis lurus');
    }
}

function showRoutingToast(msg) {
    var t = document.createElement('div');
    t.innerText = msg;
    t.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%);'
        + 'background:#0d6efd;color:#fff;padding:10px 22px;border-radius:8px;'
        + 'font-weight:bold;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2);';
    document.body.appendChild(t);
    return t;
}


// ================= SMART SALES =================

function smartSalesMode(){

    if(!navigator.geolocation){
        alert('Browser tidak mendukung GPS');
        return;
    }

    navigator.geolocation.getCurrentPosition(async function(position){

        var lat = position.coords.latitude;
        var lng = position.coords.longitude;
        var userLocation = L.latLng(lat, lng);

        if(userMarker) map.removeLayer(userMarker);

        userMarker = L.marker([lat, lng])
            .addTo(map)
            .bindPopup('📍 Lokasi Kamu')
            .openPopup();

        map.setView([lat, lng], 14);

        var targets = [];

        // PRIORITAS 1: Follow Up Hari Ini
        allMarkers.forEach(function(marker){
            if(marker.followUp && isToday(marker.followUp)){
                targets.push({
                    marker: marker,
                    distance: userLocation.distanceTo(marker.getLatLng()),
                    priority: 1
                });
            }
        });

        // PRIORITAS 2: LOSE
        if(targets.length === 0){
            allMarkers.forEach(function(marker){
                if(marker.status === 'LOSE'){
                    targets.push({
                        marker: marker,
                        distance: userLocation.distanceTo(marker.getLatLng()),
                        priority: 2
                    });
                }
            });
        }

        // PRIORITAS 3: NOT VISIT
        if(targets.length === 0){
            allMarkers.forEach(function(marker){
                if(marker.status === 'NOT_VISIT'){
                    targets.push({
                        marker: marker,
                        distance: userLocation.distanceTo(marker.getLatLng()),
                        priority: 3
                    });
                }
            });
        }

        if(targets.length === 0){
            alert('Tidak ada target yang tersedia');
            return;
        }

        targets.sort(function(a,b){ return a.distance - b.distance; });
        var selected = targets.slice(0, 5);
        var selectedMarkers = selected.map(function(t){ return t.marker; });

        // sembunyikan SEMUA marker yang bukan target
        allMarkers.forEach(function(m){ map.removeLayer(m); });

        // tampilkan hanya target + naikkan z-index supaya menonjol
        selectedMarkers.forEach(function(m, i){
            m.addTo(map);
            m.setZIndexOffset(2000 + (5 - i) * 100); // stop pertama paling atas
        });

        // auto fit bounds pas ke titik-titik target
        var group = L.featureGroup(selectedMarkers);
        map.fitBounds(group.getBounds(), { padding: [60, 60] });

        updateCounter(selected.length);

        var priorityLabel = selected[0].priority === 1
            ? '🔔 Smart Sales (Follow Up)'
            : selected[0].priority === 2
                ? '🔴 Smart Sales (LOSE)'
                : '⚪ Smart Sales (NOT VISIT)';

        await drawOSRMRoute(userLocation, selected, 'routeLine', '#0d6efd', priorityLabel);

    }, function(){
        alert('Tidak bisa akses GPS');
    });
}


// ================= FOCUS FOLLOW UP =================
function focusFollowUp(){

    var visibleMarkers = [];

    allMarkers.forEach(function(marker){

        if(marker.followUp && isToday(marker.followUp)){

            marker.addTo(map);
            marker.setZIndexOffset(2000);
            visibleMarkers.push(marker);

        }else{

            map.removeLayer(marker);

        }

    });

    updateCounter(visibleMarkers.length);

    if(visibleMarkers.length > 0){
        var group = new L.featureGroup(visibleMarkers);
        map.fitBounds(group.getBounds());
    }else{
        alert("Tidak ada Follow Up hari ini");
    }

}

// ================= RESET MAP =================
function resetMap(){


    if(userMarker){
        map.removeLayer(userMarker);
        userMarker = null;
    }

    if(routeLine){
        map.removeLayer(routeLine);
        routeLine = null;
    }

    routeMarkers = [];

    // bersihkan marker dulu
    allMarkers.forEach(function(marker){
        if(map.hasLayer(marker)){
            map.removeLayer(marker);
        }
    });

    // tampilkan semua marker lagi
    allMarkers.forEach(function(marker){
        marker.addTo(map);
        marker.setZIndexOffset(0);
    });

    document.getElementById("filter").value = "all";

    // reset semua checkbox status
    document.querySelectorAll('#statusFilter input[type=checkbox]').forEach(function(cb) {
        cb.checked = true;
    });

    document.getElementById("toggleFollowUp").checked = true;
    map.addLayer(followUpLayer);

    document.getElementById("searchInput").value = "";

    updateCounter(allMarkers.length);

    map.setView([-7.98,112.63],12);

}

// ================= HIGHLIGHT TARGETS =================
function autoHighlightTargets(){

    if(!navigator.geolocation){
        return;
    }

    navigator.geolocation.getCurrentPosition(function(position){

        var lat = position.coords.latitude;
        var lng = position.coords.longitude;

        var userLocation = L.latLng(lat,lng);

        var targets = [];

        // PRIORITAS FOLLOW UP TODAY
        allMarkers.forEach(function(marker){

            if(marker.followUp && isToday(marker.followUp)){

                var distance = userLocation.distanceTo(marker.getLatLng());

                targets.push({
                    marker:marker,
                    distance:distance
                });

            }

        });

        // PRIORITAS LOSE
        if(targets.length === 0){

            allMarkers.forEach(function(marker){

                if(marker.status === "LOSE"){

                    var distance = userLocation.distanceTo(marker.getLatLng());

                    targets.push({
                        marker:marker,
                        distance:distance
                    });

                }

            });

        }

        // PRIORITAS NOT VISIT
        if(targets.length === 0){

            allMarkers.forEach(function(marker){

                if(marker.status === "NOT_VISIT"){

                    var distance = userLocation.distanceTo(marker.getLatLng());

                    targets.push({
                        marker:marker,
                        distance:distance
                    });

                }

            });

        }

        if(targets.length === 0){
            return;
        }

        targets.sort(function(a,b){
            return a.distance - b.distance;
        });

        var selected = targets.slice(0,5);

        selected.forEach(function(item){

            item.marker.setZIndexOffset(2000);
            item.marker.openPopup();

        });

        // ZOOM KE TARGET TERPILIH
        var group = selected.map(function(t){
            return t.marker;
        });

        map.fitBounds(L.featureGroup(group).getBounds());

    });

}
map.whenReady(function(){

    setTimeout(function(){

        autoHighlightTargets();
        showFollowUpNotification();

    },1000);

});

// ================= FOLLOW UP NOTIFICATION =================

function showFollowUpNotification() {

    var todayFollowUps = allMarkers.filter(function(marker){
        return marker.followUp && isToday(marker.followUp);
    });

    if(todayFollowUps.length === 0) return;

    // Buat panel notifikasi
    var panel = document.createElement('div');
    panel.id = 'followup-notif';
    panel.style.cssText = `
        position: fixed;
        top: 80px;
        right: 20px;
        width: 300px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,.18);
        z-index: 9999;
        overflow: hidden;
        animation: slideIn .3s ease;
        font-family: sans-serif;
    `;

    // Header
    var header = document.createElement('div');
    header.style.cssText = `
        background: #dc3545;
        color: #fff;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    `;
    header.innerHTML = `
        <span style="font-weight:bold;font-size:14px;">
            🔔 Follow Up Hari Ini (${todayFollowUps.length})
        </span>
        <span id="close-notif" style="cursor:pointer;font-size:18px;line-height:1;">✕</span>
    `;

    // List item
    var list = document.createElement('div');
    list.style.cssText = 'max-height:220px;overflow-y:auto;';

    todayFollowUps.forEach(function(marker, i){
        var item = document.createElement('div');
        item.style.cssText = `
            padding: 10px 16px;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            transition: background .15s;
        `;

        var typeLabel = {edu:'Education',sppg:'SPPG',kdmp:'KDMP',faskes:'Faskes',bank:'Bank',koperasi:'Koperasi',hotel:'Hotel',wisata:'Wisata'};
        var statusColor = {WIN:'#28a745',LOSE:'#dc3545',NOT_VISIT:'#adb5bd',UNKNOWN:'#ffc107'};
        var st = (marker.status || 'NOT_VISIT').toUpperCase();

        item.innerHTML = `
            <span>
                <strong>${marker.nama}</strong><br>
                <small style="color:#6c757d;">${typeLabel[marker.type] || marker.type}</small>
            </span>
            <span style="
                font-size:11px;font-weight:bold;padding:2px 8px;border-radius:10px;
                background:${statusColor[st]||'#adb5bd'};color:#fff;
            ">${st.replace('_',' ')}</span>
        `;

        item.addEventListener('mouseenter', function(){ this.style.background = '#f8f9fa'; });
        item.addEventListener('mouseleave', function(){ this.style.background = '#fff'; });

        // Klik item → zoom ke marker dan buka popup
        item.addEventListener('click', function(){
            map.setView(marker.getLatLng(), 16);
            marker.openPopup();
            panel.remove();
        });

        list.appendChild(item);
    });

    // Footer — tombol langsung jalankan FollowUp Today
    var footer = document.createElement('div');
    footer.style.cssText = 'padding:10px 16px;background:#f8f9fa;';
    footer.innerHTML = `
        <button id="btn-goto-followup" style="
            width:100%;padding:8px;background:#dc3545;color:#fff;
            border:none;border-radius:6px;cursor:pointer;font-weight:bold;font-size:13px;
        ">📍 Tampilkan di Peta</button>
    `;

    panel.appendChild(header);
    panel.appendChild(list);
    panel.appendChild(footer);
    document.body.appendChild(panel);

    // Animasi CSS
    var style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { opacity:0; transform: translateX(20px); }
            to   { opacity:1; transform: translateX(0); }
        }
    `;
    document.head.appendChild(style);

    // Tutup panel
    document.getElementById('close-notif').addEventListener('click', function(){
        panel.remove();
    });

    // Tombol tampilkan di peta → jalankan focusFollowUp
    document.getElementById('btn-goto-followup').addEventListener('click', function(){
        panel.remove();
        focusFollowUp();
    });
}

function openEditForm(id, sheet, status, visit, internet, hasilEncoded, followUpEncoded) {

    var hasil    = decodeURIComponent(hasilEncoded   ?? '');
    var followUp = decodeURIComponent(followUpEncoded ?? '');

    var form = `
    <div class="popup-form">

        <label>Status</label>
        <select id="edit_status">
            <option value="WIN"       ${status==="WIN"      ?"selected":""}>WIN</option>
            <option value="LOSE"      ${status==="LOSE"     ?"selected":""}>LOSE</option>
            <option value="UNKNOWN"   ${status==="UNKNOWN"  ?"selected":""}>UNKNOWN</option>
            <option value="NOT_VISIT" ${status==="NOT_VISIT"?"selected":""}>NOT VISIT</option>
        </select>

        <label>Visiting</label>
        <select id="edit_visit">
            <option value="DONE"     ${visit==="DONE"    ?"selected":""}>DONE</option>
            <option value="NOT YET"  ${visit==="NOT YET" ?"selected":""}>NOT YET</option>
            <option value="ASIGNED"  ${visit==="ASIGNED" ?"selected":""}>ASIGNED</option>
        </select>

        <label>No Internet</label>
        <input type="text" id="edit_internet" value="${internet ?? ''}">

        <label>Hasil Kunjungan</label>
        <textarea id="edit_hasil" rows="3" placeholder="Tulis hasil kunjungan..."
            style="width:100%;padding:6px;border-radius:6px;border:1px solid #ccc;resize:vertical;font-size:13px;">${hasil}</textarea>

        <label>Follow Up Tanggal</label>
        <input type="date" id="edit_followup"
            style="width:100%;padding:6px;border-radius:6px;border:1px solid #ccc;"
            value="${followUp ? toInputDate(followUp) : ''}">

        <div style="display:flex;gap:8px;margin-top:12px;">
            <button onclick="submitEdit(${id},'${sheet}')"
                style="flex:1;background:#28a745;color:#fff;border:none;padding:8px;border-radius:6px;cursor:pointer;font-weight:bold;">
                ✅ Simpan
            </button>
            <button onclick="map.closePopup()"
                style="flex:1;background:#6c757d;color:#fff;border:none;padding:8px;border-radius:6px;cursor:pointer;">
                ✖ Batal
            </button>
        </div>

    </div>
    `;

    L.popup({ maxWidth: 320 })
        .setLatLng(map.getCenter())
        .setContent(form)
        .openOn(map);
}

// helper: konversi DD/MM/YYYY → YYYY-MM-DD untuk input[type=date]
function toInputDate(str) {
    if (!str) return '';
    var parts = str.split('/');
    if (parts.length === 3) return `${parts[2]}-${parts[1]}-${parts[0]}`;
    return str; // kalau sudah format ISO, langsung return
}

// helper: konversi YYYY-MM-DD → DD/MM/YYYY untuk simpan ke sheet
function toSheetDate(str) {
    if (!str) return '';
    var parts = str.split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return str;
}

function submitEdit(id, sheet) {

    var status   = document.getElementById("edit_status").value;
    var visit    = document.getElementById("edit_visit").value;
    var internet = document.getElementById("edit_internet").value;
    var hasil    = document.getElementById("edit_hasil").value;
    var followUp = toSheetDate(document.getElementById("edit_followup").value);

    fetch(`/update-location/${id}`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            status:         status,
            visit:          visit,
            nomor_internet: internet,
            hasil:          hasil,
            follow_up:      followUp,
            sheet:          sheet
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success !== false) {
            map.closePopup();
            // update marker di memori supaya tidak perlu full reload
            allMarkers.forEach(function(marker) {
                if (marker.itemId === id) {
                    marker.status   = status;
                    marker.followUp = followUp;
                }
            });
            showToast("✅ Data berhasil disimpan!");
            setTimeout(() => location.reload(), 1200);
        } else {
            alert("Gagal update: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        alert("Error: " + err);
    });
}

// toast notifikasi kecil
function showToast(msg) {
    var t = document.createElement("div");
    t.innerText = msg;
    t.style.cssText = `
        position:fixed;bottom:30px;left:50%;transform:translateX(-50%);
        background:#28a745;color:#fff;padding:10px 22px;border-radius:8px;
        font-weight:bold;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2);
        transition:opacity .4s;
    `;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = 0; setTimeout(() => t.remove(), 400); }, 2000);
}

</script>

@endsection