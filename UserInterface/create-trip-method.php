<?php
session_start();
require_once '../config/database.php';


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get tourism types for the selection
$stmt = $pdo->query("SELECT * FROM tourism_types ORDER BY id");
$tourism_types = $stmt->fetchAll();

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$error2 = '';
$success = '';

if (isset($_SESSION['error2'])) {
    $error2 = $_SESSION['error2'];
    unset($_SESSION['error2']);
}

$exclude_categories = $_SESSION['trip_data']['exclude_categories'] ?? [];
$accommodation_categories = $_SESSION['trip_data']['accommodation_categories'] ?? [];
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 1) {
        // เลือกธีมการท่องเที่ยว
        $tourism_type_id = (int)$_POST['tourism_type_id'];
        if ($tourism_type_id > 0) {

            $_SESSION['trip_data']['tourism_type_id'] = $tourism_type_id;
            header('Location: create-trip.php?step=2');
            exit();
        } else {
            $error = 'กรุณาเลือกธีมการท่องเที่ยว';
        }
    } elseif ($step === 2) {
        $days = (int)$_POST['days'];
        $guest_count = (int)$_POST['guest_count'];
        $accommodation_categories = $_POST['accommodation_categories'] ?? []; // << เพิ่มตรงนี้

        if ($days >= 1 && $days <= 14) {
            if ($guest_count >= 1 && $guest_count <= 20) {
                $_SESSION['trip_data']['days'] = $days;
                $_SESSION['trip_data']['guest_count'] = $guest_count;
                $_SESSION['trip_data']['accommodation_categories'] = $accommodation_categories; // << เก็บลง session

                header('Location: create-trip.php?step=3');
                exit();
            } else {
                $error = 'จำนวนผู้เข้าพักต้องอยู่ในช่วง 1-20 คน';
            }
        } else {
            $error = 'กรุณาเลือกจำนวนวันที่ถูกต้อง (1-14 วัน)';
        }
    } elseif ($step === 3) {
        // งบประมาณ
        $budget = (float)$_POST['budget'];
        if ($budget > 0) {
            $_SESSION['trip_data']['budget'] = $budget;
            header('Location: create-trip.php?step=4');
            exit();
        } else {
            $error = 'กรุณาใส่งบประมาณที่ถูกต้อง';
        }
    } elseif ($step === 4) {
        // ตั้งชื่อทริปและสร้างแผนการเดินทาง
        $trip_title = trim($_POST['trip_title']);
        if (!empty($trip_title)) {
            $_SESSION['trip_data']['trip_title'] = $trip_title;

            // ตรวจสอบข้อมูลที่จำเป็นครบ
            if (
                isset($_SESSION['trip_data']['tourism_type_id']) &&
                isset($_SESSION['trip_data']['budget']) &&
                isset($_SESSION['trip_data']['days']) &&
                isset($_SESSION['trip_data']['guest_count'])
            ) {
                try {
                    $tourism_type_id = $_SESSION['trip_data']['tourism_type_id'];
                    $budget = $_SESSION['trip_data']['budget'];
                    $days = $_SESSION['trip_data']['days'];
                    $guest_count = $_SESSION['trip_data']['guest_count'];
                    $title = $_SESSION['trip_data']['trip_title'];

                    $stmt = $pdo->prepare("
                        INSERT INTO trip_plans (user_id, title, tourism_type_id, budget, total_days,guest_count, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'planning')
                    ");
                    $stmt->execute([$_SESSION['user_id'], $title, $tourism_type_id, $budget, $days,$guest_count]);
                    $trip_id = $pdo->lastInsertId();

                    try {
                        // เรียก generateItinerary พร้อม guest_count
                        generateItinerary($trip_id, $tourism_type_id, $budget, $days, $guest_count);
                        unset($_SESSION['trip_data']);
                        header('Location: create-trip.php?step=5&id=' . $trip_id);
                        exit();
                    } catch (Exception $e) {
                        // ลบข้อมูลทริปที่ไม่สมบูรณ์
                        $stmt = $pdo->prepare("DELETE FROM trip_plans WHERE id = ?");
                        $stmt->execute([$trip_id]);

                        // เก็บข้อความ error ลง session
                        $_SESSION['error2'] = $e->getMessage();

                        // กลับไปยังหน้ากรอกงบ (step=3)
                        header('Location: create-trip.php?step=3');
                        exit();
                    }
                } catch (PDOException $e) {
                    $error = 'เกิดข้อผิดพลาดในการสร้างแผนการเดินทาง: ' . $e->getMessage();
                }
            } else {
                $error = 'ข้อมูลไม่ครบถ้วน กรุณาเริ่มใหม่';
            }
        } else {
            $error = 'กรุณาตั้งชื่อทริปของคุณ';
        }
    }
}



function generateItinerary($trip_id, $tourism_type_id, $budget, $days, $guest_count)
{
    global $pdo;

    try {
        // แบ่งงบประมาณ
        $accommodation_budget = $budget * 0.6; // 60% สำหรับที่พัก
        $food_transport_buffer = $budget * 0.1; // 10% สำหรับค่าเดินทาง อาหาร
        $remaining_budget = $budget - $accommodation_budget - $food_transport_buffer;

        // รับ category ที่ผู้ใช้เลือก
        $accommodation_categories = $_SESSION['trip_data']['accommodation_categories'] ?? [];

        // เลือกที่พักที่เหมาะสม
        $accommodation = selectOptimalAccommodation(
            $accommodation_budget,
            $days,
            $guest_count,
            $accommodation_categories
        );

        // debug
        error_log("DEBUG accommodation: " . print_r($accommodation, true));

        $actual_accommodation_cost = 0;
        if ($accommodation) {
            $actual_accommodation_cost = $accommodation['price_per_night'] * $days * $accommodation['rooms_needed'];
        }

        $remaining_budget = $budget - $actual_accommodation_cost - $food_transport_buffer;

        // ดึงสถานที่ท่องเที่ยว
        $all_attractions = getAvailableAttractions($tourism_type_id);
        $theme_attractions = array_filter($all_attractions, fn($a) => $a['tourism_type_id'] == $tourism_type_id);

        $theme_budget = max(0, $remaining_budget * 0.8);
        if ($theme_budget <= 0) {
            throw new Exception('งบประมาณไม่เพียงพอในการวางแผนสถานที่ท่องเที่ยว กรุณาลดจำนวนวันหรือเพิ่มงบประมาณ');
        }

        $selected_theme = optimizeAttractionSelection(array_values($theme_attractions), $theme_budget, $days);
        $itinerary = distributeAttractionsSmart($selected_theme, $days);

        saveItinerary($trip_id, $itinerary, $accommodation, $days);

        $total_used_budget = calculateTotalBudget($itinerary, $accommodation, $days);

        $stmt = $pdo->prepare("UPDATE trip_plans SET status = 'completed', actual_budget = ? WHERE id = ?");
        $stmt->execute([$total_used_budget, $trip_id]);

        return [
            'success' => true,
            'budget_used' => $total_used_budget,
            'budget_efficiency' => ($total_used_budget / $budget) * 100,
            'accommodation' => $accommodation,
            'itinerary' => $itinerary
        ];
    } catch (PDOException $e) {
        error_log("Error generating itinerary: " . $e->getMessage());
        throw $e;
    }
}

/**
 * เลือก accommodation + room type ที่เหมาะสมตามงบและจำนวนคน
 */
/**
 * เลือก accommodation + room type ที่เหมาะสมตามงบและจำนวนคน
 * รองรับ guest_count ทุกค่า และ category (ถ้ามี)
 */
function selectOptimalAccommodation($accommodation_budget, $days, $guest_count = 2, $accommodation_categories = [])
{
    global $pdo;

    $max_per_night = $days > 0 ? ($accommodation_budget / $days) : $accommodation_budget;

    $sql = "
        SELECT a.id AS accommodation_id, a.name, a.type, a.description, a.rating, a.image_url,
               r.id AS room_id, r.room_name, r.capacity, r.price_per_night
        FROM accommodations a
        INNER JOIN room_types r ON a.id = r.accommodation_id
        WHERE r.price_per_night <= ?
    ";

    $params = [$max_per_night];

    if (!empty($accommodation_categories)) {
        $placeholders = implode(',', array_fill(0, count($accommodation_categories), '?'));
        $sql .= " AND a.type IN ($placeholders)";
        $params = array_merge($params, $accommodation_categories);
    }

    $sql .= " ORDER BY a.rating DESC, r.price_per_night ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        // ถ้าไม่เจอห้องที่ตรงตามงบ ให้เลือกห้องถูกสุด 3 ห้อง
        $stmt = $pdo->prepare("
            SELECT a.id AS accommodation_id, a.name, a.type, a.description, a.rating, a.image_url,
                   r.id AS room_id, r.room_name, r.capacity, r.price_per_night
            FROM accommodations a
            INNER JOIN room_types r ON a.id = r.accommodation_id
            ORDER BY r.price_per_night ASC
            LIMIT 3
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $best = null;

    foreach ($rows as $r) {
        $capacity = max(1, (int)$r['capacity']);

        // ❗ บังคับให้เลือกเฉพาะห้องที่จุคนได้พอดีในห้องเดียว
        if ($capacity < $guest_count) continue;

        $rooms_needed = 1; // มีความหมายว่า user ต้องการห้องแบบเดียว 1 ห้อง

        $total_room_cost_for_days = $r['price_per_night'] * $rooms_needed * $days;

        if ($total_room_cost_for_days > $accommodation_budget) continue;

        // ความเหมาะสมของจำนวนคน
        $capacity_diff = abs($capacity * $rooms_needed - $guest_count);

        // เตรียม candidate
        $candidate = $r;
        $candidate['rooms_needed'] = $rooms_needed;
        $candidate['capacity_diff'] = $capacity_diff;
        $candidate['total_room_cost_for_days'] = $total_room_cost_for_days;
        $candidate['rating'] = (float)$r['rating'];

        // ถ้ายังไม่มีตัวเลือกเลย
        if ($best === null) {
            $best = $candidate;
            continue;
        }

        // 1) เลือกตัวที่ capacity_diff น้อยที่สุดก่อน
        if ($candidate['capacity_diff'] < $best['capacity_diff']) {
            $best = $candidate;
            continue;
        }
        if ($candidate['capacity_diff'] > $best['capacity_diff']) {
            continue;
        }

        // 2) ถ้า capacity_diff เท่ากัน → เลือกที่ราคาถูกกว่า
        if ($candidate['total_room_cost_for_days'] < $best['total_room_cost_for_days']) {
            $best = $candidate;
            continue;
        }
        if ($candidate['total_room_cost_for_days'] > $best['total_room_cost_for_days']) {
            continue;
        }

        // 3) ถ้าเหมือนกันหมด → ดู rating
        if ($candidate['rating'] > $best['rating']) {
            $best = $candidate;
        }
    }


    if ($best === null && !empty($rows)) {
        $r = $rows[0];
        $capacity = max(1, (int)$r['capacity']);
        $rooms_needed = (int)ceil($guest_count / $capacity);
        $total_room_cost_for_days = $r['price_per_night'] * $rooms_needed * $days;

        $best = $r;
        $best['score'] = 0;
        $best['rooms_needed'] = $rooms_needed;
        $best['total_room_cost_for_days'] = $total_room_cost_for_days;
    }

    // debug
    error_log("DEBUG selectOptimalAccommodation: " . print_r($best, true));

    return $best;
}


/**
 * ฟังก์ชันดึงสถานที่ทั้งหมด (เหมือนเดิม)
 */
function getAvailableAttractions($tourism_type_id)
{
    global $pdo;

    $exclude = $_SESSION['trip_data']['exclude_categories'] ?? [];

    $sql = "SELECT *, 
            (rating * 0.4 + CASE WHEN tourism_type_id = ? THEN 0.6 ELSE 0.2 END) as priority_score
            FROM attractions
            WHERE 1";

    if (!empty($exclude)) {
        $placeholders = implode(',', array_fill(0, count($exclude), '?'));
        $sql .= " AND category_id NOT IN ($placeholders)";
    }

    $sql .= " ORDER BY priority_score DESC, entrance_fee ASC";

    $stmt = $pdo->prepare($sql);

    $params = [$tourism_type_id];
    if (!empty($exclude)) {
        $params = array_merge($params, $exclude);
    }

    $stmt->execute($params);
    return $stmt->fetchAll();
}


/**
 * optimizeAttractionSelection (knapsack) — ไม่เปลี่ยนจากเดิม
 */
function optimizeAttractionSelection($attractions, $budget, $days)
{
    $n = count($attractions);
    $budget_int = (int)$budget;

    $dp = array_fill(0, $n + 1, array_fill(0, $budget_int + 1, 0));
    $selected = array_fill(0, $n + 1, array_fill(0, $budget_int + 1, []));

    for ($i = 1; $i <= $n; $i++) {
        $attraction = $attractions[$i - 1];
        $cost = (int)$attraction['entrance_fee'];
        $value = $attraction['priority_score'] * 100;

        for ($w = 0; $w <= $budget_int; $w++) {
            $dp[$i][$w] = $dp[$i - 1][$w];
            $selected[$i][$w] = $selected[$i - 1][$w];

            if ($cost <= $w) {
                $new_value = $dp[$i - 1][$w - $cost] + $value;
                if ($new_value > $dp[$i][$w]) {
                    $dp[$i][$w] = $new_value;
                    $selected[$i][$w] = array_merge($selected[$i - 1][$w - $cost], [$attraction]);
                }
            }
        }
    }

    return $selected[$n][$budget_int];
}

/**
 * distributeAttractionsSmart (เหมือนเดิม)
 */
function distributeAttractionsSmart($attractions, $days)
{
    $time_slots = [
        ['start' => '09:00', 'end' => '12:00'],
        ['start' => '13:00', 'end' => '16:00'],
        ['start' => '16:30', 'end' => '18:00'],
    ];

    $itinerary = [];
    $current_day = 0;
    $slot_index = 0;
    $slot_time_used = 0;

    foreach ($attractions as $attraction) {
        $duration = isset($attraction['estimated_duration'])
            ? (int)$attraction['estimated_duration']
            : 120;

        while ($duration + $slot_time_used > (strtotime($time_slots[$slot_index]['end']) - strtotime($time_slots[$slot_index]['start'])) / 60) {
            $slot_index++;
            $slot_time_used = 0;

            if ($slot_index >= count($time_slots)) {
                $current_day++;
                if ($current_day >= $days) {
                    break 2;
                }
                $slot_index = 0;
            }
        }

        $start_time_sec = strtotime($time_slots[$slot_index]['start']) + ($slot_time_used * 60);
        $start_time = date('H:i', $start_time_sec);

        $itinerary[$current_day][] = [
            'attraction' => $attraction,
            'start_time' => $start_time,
        ];

        $slot_time_used += $duration;
    }

    return $itinerary;
}

/**
 * saveItinerary: บันทึก itinerary และข้อมูลห้อง (ใช้ room_type_id)
 */
/**
 * saveItinerary: บันทึก itinerary และข้อมูลห้อง (รองรับ guest_count / rooms_needed)
 */
function saveItinerary($trip_id, $itinerary, $accommodation, $days)
{
    global $pdo;
    $order_index = 0;

    // ลบ itinerary เดิม
    $stmt = $pdo->prepare("DELETE FROM trip_itinerary WHERE trip_plan_id = ?");
    $stmt->execute([$trip_id]);

    // กำหนดค่า accommodation
    $accom_id = $accommodation['accommodation_id'] ?? null;
    $room_type_id = $accommodation['room_id'] ?? null;
    $perNightCost = $accommodation['price_per_night'] ?? 0.0;
    $rooms_needed = $accommodation['rooms_needed'] ?? 1;

    for ($day = 0; $day < $days; $day++) {
        // บันทึก attractions ของวัน
        if (!empty($itinerary[$day])) {
            foreach ($itinerary[$day] as $slot) {
                $attraction = $slot['attraction'];
                $start_time = $slot['start_time'] . ':00';

                $stmt = $pdo->prepare("
                    INSERT INTO trip_itinerary 
                        (trip_plan_id, day_number, attraction_id, start_time, estimated_cost, order_index)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $trip_id,
                    $day + 1,
                    $attraction['id'],
                    $start_time,
                    $attraction['entrance_fee'] ?? 0,
                    $order_index++
                ]);
            }
        } else {
            // Free Day
            $stmt = $pdo->prepare("
                INSERT INTO trip_itinerary 
                    (trip_plan_id, day_number, attraction_id, start_time, estimated_cost, order_index)
                VALUES (?, ?, NULL, NULL, 0, ?)
            ");
            $stmt->execute([$trip_id, $day + 1, $order_index++]);
        }

        // บันทึกที่พักท้ายวัน
        if ($accom_id && $room_type_id) {
            // estimated_cost = ราคาต่อคืน * จำนวนห้อง
            $estimatedCost = $perNightCost * $rooms_needed;

            $stmt = $pdo->prepare("
                INSERT INTO trip_itinerary 
                    (trip_plan_id, day_number, attraction_id, accommodation_id, room_type_id, start_time, estimated_cost, order_index)
                VALUES (?, ?, NULL, ?, ?, '18:00:00', ?, ?)
            ");
            $stmt->execute([
                $trip_id,
                $day + 1,
                $accom_id,
                $room_type_id,
                $estimatedCost,
                $order_index++
            ]);
        }
    }
}


/**
 * calculateTotalBudget: คำนวณรวมค่า attraction + ห้อง (คูณ rooms_needed)
 */
function calculateTotalBudget($itinerary, $accommodation, $days)
{
    $total = 0;

    foreach ($itinerary as $day_attractions) {
        foreach ($day_attractions as $attraction) {
            $total += $attraction['entrance_fee'];
        }
    }

    if ($accommodation) {
        $room_price = $accommodation['price_per_night'] ?? 0;
        $rooms_needed = $accommodation['rooms_needed'] ?? 1;
        $total += ($room_price * $rooms_needed * $days);
    }

    return $total;
}
