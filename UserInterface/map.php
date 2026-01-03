<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$trip_id = isset($_GET['trip_id']) ? (int)$_GET['trip_id'] : 0;

if (!$trip_id) {
    header('Location: index.php');
    exit();
}

// Get trip details
$stmt = $pdo->prepare("
    SELECT tp.*, tt.name as tourism_type_name, tt.color as tourism_type_color
    FROM trip_plans tp 
    LEFT JOIN tourism_types tt ON tp.tourism_type_id = tt.id 
    WHERE tp.id = ? AND tp.user_id = ?
");
$stmt->execute([$trip_id, $_SESSION['user_id']]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: index.php');
    exit();
}

// Get locations from itinerary
$stmt = $pdo->prepare("
    SELECT ti.*, 
           a.id AS attraction_id,
           a.image_url AS attraction_image_url,
           a.name AS attraction_name, 
           a.latitude AS attraction_lat, 
           a.longitude AS attraction_lng,
           a.description AS attraction_description, 
           a.historical_info,
           acc.id AS accommodation_id,
           acc.image_url AS accommodation_image_url,
           acc.name AS accommodation_name, 
           acc.latitude AS accommodation_lat, 
           acc.longitude AS accommodation_lng,
           acc.description AS accommodation_description
    FROM trip_itinerary ti
    LEFT JOIN attractions a ON ti.attraction_id = a.id
    LEFT JOIN accommodations acc ON ti.accommodation_id = acc.id
    WHERE ti.trip_plan_id = ?
    ORDER BY ti.day_number, ti.order_index
");

$stmt->execute([$trip_id]);
$locations = $stmt->fetchAll();

// Prepare locations for JavaScript
$map_locations = [];
foreach ($locations as $location) {
    if ($location['attraction_lat'] && $location['attraction_lng']) {

        $map_locations[] = [
            'img' => $location['attraction_image_url'],
            'id' => $location['attraction_id'],
            'type' => 'attraction',
            'name' => $location['attraction_name'],
            'lat' => (float)$location['attraction_lat'],
            'lng' => (float)$location['attraction_lng'],
            'description' => $location['attraction_description'],
            'historical_info' => $location['historical_info'],
            'day' => $location['day_number']
        ];
    }
    if ($location['accommodation_lat'] && $location['accommodation_lng']) {
        $map_locations[] = [
            'img' => $location['accommodation_image_url'],
            'id' => $location['accommodation_id'], // หรือ accommodation_id
            'type' => 'accommodation',
            'name' => $location['accommodation_name'],
            'lat' => (float)$location['accommodation_lat'],
            'lng' => (float)$location['accommodation_lng'],
            'description' => $location['accommodation_description'],
            'day' => $location['day_number']
        ];
    }
}
// ดึงสถานที่ทั่วไป (ไม่ต้องเกี่ยวกับแผน)
$stmt = $pdo->prepare("SELECT * FROM places");
$stmt->execute();
$general_places = $stmt->fetchAll();

// แปลงเป็นรูปแบบสำหรับ JS
foreach ($general_places as $place) {
    if ($place['lat'] && $place['lng']) {
        $map_locations[] = [
            'img' => $place['image_url'],
            'id' => $place['id'],
            'type' => 'general', // ระบุ type ใหม่
            'name' => $place['name'],
            'lat' => (float)$place['lat'],
            'lng' => (float)$place['lng'],
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนที่ - <?= htmlspecialchars($trip['title']) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background: #f8fafc;
        }

        #map {
            height: 70vh;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .map-controls {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 20px;
        }

        .location-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .location-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .location-card.attraction {
            border-left: 4px solid #10b981;
        }

        .location-card.accommodation {
            border-left: 4px solid #f59e0b;
        }

        .btn-primary {
            background: linear-gradient(45deg, #2563eb, #10b981);
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 500;
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-location-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 1000;
            background: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        #trackingButton {

            border: none;
            background-color: rgba(111, 53, 77, 1);
            color: #fff;
            width: 100%;
            height: 80px;
            padding: 8px 12px;
            border-radius: 0px 0px 25px 25px;
            cursor: pointer;
            z-index: 1000;

        }
    </style>
</head>

<body>
    
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-map-marked-alt me-2"></i>
                พัทลุงทริป
            </a>

            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="view-trip.php?id=<?= $trip['id'] ?>">
                    <i class="fas fa-list me-1"></i>
                    ดูแผน
                </a>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    หน้าหลัก
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid my-4">
        <div class="row">
            <!-- Map Controls Sidebar -->
            <div class="col-lg-3">
                <div class="map-controls">
                    <h5 class="mb-3">
                        <i class="fas fa-map-pin me-2"></i>
                        <?= htmlspecialchars($trip['title']) ?>
                    </h5>

                    <div class="mb-3">
                        <button class="btn btn-primary btn-sm w-100" onclick="showAllLocations()">
                            <i class="fas fa-expand-arrows-alt me-2"></i>
                            แสดงทั้งหมด
                        </button>
                    </div>

                    <div class="mb-3">
                        <button class="btn btn-outline-primary btn-sm w-100" onclick="getCurrentLocation()">
                            <i class="fas fa-location-arrow me-2"></i>
                            ตำแหน่งปัจจุบัน
                        </button>
                    </div>

                    <hr>

                    <h6 class="mb-3">สถานที่ในแผน</h6>
                    <div id="locationsList">
                        <?php foreach ($map_locations as $index => $location): ?>
                            <div class="location-card <?= $location['type'] ?>" onclick="focusLocation(<?= $index ?>)">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <?php if ($location['type'] === 'attraction'): ?>
                                            <i class="fas fa-map-marker-alt text-success"></i>
                                        <?php elseif ($location['type'] === 'accommodation'): ?>
                                            <i class="fas fa-bed text-warning"></i>
                                        <?php else: ?>
                                            <i class="bi bi-cup-hot"></i>
                                        <?php endif; ?>


                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= htmlspecialchars($location['name']) ?></div>

                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="col-lg-9">
                <div class="position-relative">
                    <div id="map"></div>
                    <button class="user-location-btn" onclick="getCurrentLocation()" title="ตำแหน่งปัจจุบัน">
                        <i class="fas fa-location-arrow text-primary"></i>
                    </button>
                </div>
                <div id="control">
                    <button onclick="startTracking()" id="trackingButton">Try AI travel partner</button>
                </div>
            </div>

        </div>
    </div>

    <button onclick="openAiPage()">เปิดหน้า AI พร้อมข้อมูล</button>
    <button onclick="simulateOnePlace()">ทดสอบเดินผ่านสถานที่</button>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Google Maps API -->
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_9yxHfKGJFjLZpyEcTatByPK15WTazH4&callback=initMap&libraries=geometry"></script>




    <script>
        const trackingButton = document.getElementById("trackingButton");
        let tracking = false; // สถานะติดตาม

        trackingButton.addEventListener("click", () => {
            if (!tracking) {
                // เริ่มติดตาม
                startTracking();
                tracking = true;
                trackingButton.textContent = "หยุด"; // เปลี่ยนข้อความ
            } else {
                // หยุดติดตาม
                stopTracking();
                tracking = false;
                trackingButton.textContent = "Try AI travel partner"; // เปลี่ยนกลับ
            }
        });

        let map;
        let markers = [];
        let userLocationMarker = null;
        let userMarker = null;
        let places = [];
        let visited = {};
        let watchId = null;
        let testIndex = 0;

        const locations = <?= json_encode($map_locations) ?>;
        console.log(locations);
        places = locations; // เปลี่ยนอันนี้นะ เพื่อให้มันแสดงแผนที่แบบเดิม                            

        // แจ้งหน้า opener ว่าพร้อมแล้ว
        console.log("📍 สถานที่จาก PHP:", places);

        function initMap() {
            const center = {
                lat: 7.6167,
                lng: 100.0750
            };

            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 13,
                center: center,
                styles: [{
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{
                        visibility: "off"
                    }],
                }, ],
            });

            // Render markers from PHP
            locations.forEach((location, index) => {
                const iconUrls = {
                    attraction: {
                        url: "./assets/img/traveller.png",
                        scaledSize: new google.maps.Size(50, 50)
                    },
                    accommodation: {
                        url: "./assets/img/house.png",
                        scaledSize: new google.maps.Size(60, 60)
                    },
                    general: {
                        url: "./assets/img/cafe.png",
                        scaledSize: new google.maps.Size(30,30)
                    }
                };

                const marker = new google.maps.Marker({
                    position: {
                        lat: location.lat,
                        lng: location.lng
                    },
                    map: map,
                    title: location.name,
                    icon: iconUrls[location.type] // ใช้ icon ตาม type
                });


                const infoWindow = new google.maps.InfoWindow({
                    content: createInfoWindowContent(location),
                });

                marker.addListener("click", () => {
                    markers.forEach((m) => m.infoWindow?.close());
                    infoWindow.open(map, marker);
                });

                markers.push({
                    marker,
                    infoWindow,
                    location
                });
            });

            showAllLocations();

            // ถ้า places มีอยู่แล้ว ก็ render ทันที
            if (places.length > 0) {
                renderPlacesOnMap();
            }
        }

        function renderPlacesOnMap() {
          

            places.forEach((p) => {
                const marker = new google.maps.Marker({
                    position: {
                        lat: p.lat,
                        lng: p.lng
                    },
                    map,
                    title: p.name,
                    icon: iconUrls[p.type] // ใช้ icon ตาม type
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: createInfoWindowContent(p),
                });

                marker.addListener("click", () => {
                    markers.forEach((m) => m.infoWindow?.close());
                    infoWindow.open(map, marker);
                });

                markers.push({
                    marker,
                    infoWindow,
                    location: p
                });
            });
        }



        function startTracking() {
            if (!navigator.geolocation) return alert("เบราว์เซอร์ไม่รองรับ GPS");

            watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const userPos = {
                        lat,
                        lng
                    };

                    if (userMarker) {
                        userMarker.setPosition(userPos);
                    } else {
                        userMarker = new google.maps.Marker({
                            position: userPos,
                            map,
                            title: "คุณอยู่ที่นี่",
                            icon: {
                                url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png",
                            },
                        });
                    }

                    map.setCenter(userPos);

                    places.forEach((place) => {
                        const radius = place.radius || 1000; // กำหนดค่าเริ่มต้น 1000 เมตร
                        const dist = haversineDistance(lat, lng, place.lat, place.lng);

                        if (dist <= radius && !visited[place.id]) {
                            console.log(`✅ อยู่ใกล้สถานที่: ${place.name} ระยะ: ${dist.toFixed(2)} เมตร`);
                            visited[place.id] = true;

                            const messageToSpeak = place.description || place.name || "คุณอยู่ใกล้สถานที่";
                            console.log("📢 อ่านข้อความ:", messageToSpeak);

                            speakThai(messageToSpeak);
                        } else {
                            console.log(`❌ ไม่เข้าเงื่อนไข: ${place.name}, ระยะ: ${dist.toFixed(2)} รัศมี: ${radius}`);
                        }
                    });
                },
                (err) => {
                    console.error("GPS error:", err.message);
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0,
                }
            );

            alert("กำลังติดตามตำแหน่งของคุณ กรุณาเปิดหน้านี้ไว้ และคลิกบนหน้าเว็บก่อนเพื่ออนุญาตเสียง");
        }



        function stopTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
                alert("หยุดติดตามแล้ว");
            }
        }

        function haversineDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const dLat = ((lat2 - lat1) * Math.PI) / 180;
            const dLng = ((lng2 - lng1) * Math.PI) / 180;
            const a =
                Math.sin(dLat / 2) ** 2 +
                Math.cos((lat1 * Math.PI) / 180) *
                Math.cos((lat2 * Math.PI) / 180) *
                Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function simulateOnePlace() {
            if (places.length === 0) {
                alert("ยังไม่ได้รับข้อมูลสถานที่ ลองใหม่อีกครั้ง");
                return;
            }

            if (testIndex >= places.length) {
                alert("คุณทดสอบครบทุกจุดแล้ว 🎉");
                testIndex = 0;
                return;
            }

            const testPlace = places[testIndex];
            const fakePos = {
                coords: {
                    latitude: testPlace.lat,
                    longitude: testPlace.lng,
                },
            };

            console.log(`🔍 ทดสอบจุดที่ ${testIndex + 1}: ${testPlace.name}`);
            visited = {};
            handleSimulatedPosition(fakePos);
            testIndex++;
        }

        function handleSimulatedPosition(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            const userPos = {
                lat,
                lng
            };

            if (userMarker) {
                userMarker.setPosition(userPos);
            } else {
                userMarker = new google.maps.Marker({
                    position: userPos,
                    map,
                    title: "คุณอยู่ที่นี่ (จำลอง)",
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/purple-dot.png",
                    },
                });
            }

            map.setCenter(userPos);

            places.forEach((place) => {
                const radius = place.radius || 500; // กำหนด default radius 500 เมตร
                const dist = haversineDistance(lat, lng, place.lat, place.lng);
                if (dist <= radius && !visited[place.id]) {
                    visited[place.id] = true;

                    const messageToSpeak = place.description || place.name || "คุณอยู่ใกล้สถานที่";
                    speakThai(messageToSpeak);

                    console.log(`✅ เสียงพูด: ${messageToSpeak} (ระยะ ${dist.toFixed(2)} เมตร)`);
                } else {
                    console.log(`❌ ไม่เข้าเงื่อนไข: ${place.name}, ระยะ: ${dist.toFixed(2)} รัศมี: ${radius}`);
                }
            });
        }

        //gemini ver
        // function speakThai(text) {
        //     if (!text) return;

        //     fetch(`tts.php?text=${encodeURIComponent(text)}`)
        //         .then(res => res.blob())
        //         .then(blob => {
        //             const url = URL.createObjectURL(blob);
        //             const audio = new Audio(url);
        //             audio.play().catch(err => console.error("Audio play error:", err));
        //         })
        //         .catch(err => console.error("TTS fetch error:", err));
        // }

        //normal ver
        function speakThai(text) {
            if (!window.speechSynthesis) return alert("เบราว์เซอร์ไม่รองรับ TTS");
            const utter = new SpeechSynthesisUtterance(text);
            utter.lang = "th-TH";
            utter.rate = 1;
            utter.pitch = 1;
            speechSynthesis.speak(utter);
        }






        function createInfoWindowContent(location) {
            let content = `
      <img src="${location.img}" style="max-width: 300px;border-radius:5px;"></img>
      <div class="mt-5" style="max-width: 300px; font-family: 'Kanit', sans-serif;">
        <h6 class="mb-2">${location.name}</h6>
        <div class="mb-2">
          <span class="badge ${
            location.type === "attraction"
              ? "bg-success"
              : "bg-warning text-dark"
          }">
            ${location.type === "attraction" ? "สถานที่ท่องเที่ยว" : "ที่พัก"}
          </span>
          <span class="badge bg-primary ms-1">วันที่ ${location.day}</span>
        </div>
    `;

            if (location.description) {
                content += `<p class="small text-muted mb-2">${location.description}</p>`;
            }

            if (location.historical_info) {
                content += `
        <div class="mt-2">
          <strong class="small">ประวัติ:</strong>
          <p class="small text-muted">${location.historical_info}</p>
        </div>
      `;
            }

            content += `
      <div class="mt-2">
        <button class="btn btn-primary btn-sm" onclick="getDirections(${location.lat}, ${location.lng})">
          <i class="fas fa-directions me-1"></i> เส้นทาง
        </button>
      </div>
      </div>
    `;

            return content;
        }

        function showAllLocations() {
            if (markers.length === 0) return;

            const bounds = new google.maps.LatLngBounds();
            markers.forEach((markerObj) => {
                bounds.extend(markerObj.marker.getPosition());
            });

            map.fitBounds(bounds);

            google.maps.event.addListenerOnce(map, "bounds_changed", function() {
                if (map.getZoom() > 15) {
                    map.setZoom(15);
                }
            });
        }

        function getDirections(lat, lng) {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const origin = `${position.coords.latitude},${position.coords.longitude}`;
                        const destination = `${lat},${lng}`;
                        const url = `https://www.google.com/maps/dir/${origin}/${destination}`;
                        window.open(url, "_blank");
                    },
                    () => {
                        const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
                        window.open(url, "_blank");
                    }
                );
            } else {
                const url = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
                window.open(url, "_blank");
            }
        }
    </script>





</body>

</html>