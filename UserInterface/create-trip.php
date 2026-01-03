<?php include "create-trip-method.php" ?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างแผนการเดินทาง - พัทลุงทริป</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background-color: #1f2937;
            min-height: 100vh;
            padding: 20px 0;
        }

        .step-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .step-header {
            background: linear-gradient(45deg, #2563eb, #10b981);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .tourism-type-card {
            border: 3px solid transparent;
            border-radius: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }

        .tourism-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .tourism-type-card.selected {
            border-color: #2563eb;
            background: #eff6ff;
        }

        .btn-primary {
            background: linear-gradient(45deg, #2563eb, #10b981);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 500;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .progress-bar {
            background: linear-gradient(45deg, #2563eb, #10b981);
            border-radius: 10px;
        }

        /* ปรับขนาด checkbox ให้ใหญ่ขึ้นนิด */
        .custom-checkbox {
            width: 1.3rem;
            height: 1.3rem;
            cursor: pointer;
        }

        /* เปลี่ยนสีตอนเลือก */
        .custom-checkbox:checked {
            background-color: #0d6efd;
            /* ใช้สีน้ำเงิน bootstrap */
            border-color: #0d6efd;
        }

        /* เพิ่มเอฟเฟกต์ hover */
        .custom-checkbox:hover {
            border-color: #0d6efd;
        }

        /* ข้อความ label */
        .form-check-label {
            font-size: 1rem;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="mb-4">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: <?= ($step / 5) * 100 ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-white small ps-3 pe-3">
                        <span class="<?= $step >= 1 ? 'fw-bold' : '' ?>">เลือกธีม</span>
                        <span class="<?= $step >= 2 ? 'fw-bold' : '' ?>">จำนวนวัน/ผู้เข้าพัก</span>
                        <span class="<?= $step >= 3 ? 'fw-bold' : '' ?>">งบประมาณ</span>
                        <span class="<?= $step >= 4 ? 'fw-bold' : '' ?>">ชื่อทริป</span>
                        <span class="<?= $step >= 5 ? 'fw-bold' : '' ?>">เสร็จสิ้น</span>
                    </div>
                </div>

                <div class="step-card">
                    <?php if ($step === 1): ?>
                        <div class="step-header">
                            <h2 class="mb-0"><i class="fas fa-map-signs me-2"></i> เลือกธีมการท่องเที่ยว</h2>
                            <p class="mb-0 mt-2 opacity-75">เลือกประเภทการท่องเที่ยวที่คุณสนใจ</p>
                        </div>

                        <div class="p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <form method="POST" id="tourismTypeForm">
                                <div class="row">
                                    <?php foreach ($tourism_types as $type): ?>
                                        <div class="col-lg-4 col-md-6 mb-4">
                                            <div class="tourism-type-card card h-100" onclick="selectTourismType(<?= $type['id'] ?>, this)">
                                                <div class="card-body text-center p-4">
                                                    <div class="mb-3" style="color: <?= htmlspecialchars($type['color']) ?>">
                                                        <i class="<?= htmlspecialchars($type['icon']) ?> fa-3x"></i>
                                                    </div>
                                                    <h5 class="card-title"><?= htmlspecialchars($type['name']) ?></h5>
                                                    <p class="card-text text-muted"><?= htmlspecialchars($type['description']) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php
                                // สมมติคุณดึง category ทั้งหมดจาก DB
                                $stmt = $pdo->query("SELECT * FROM attraction_categories ORDER BY name");
                                $categories = $stmt->fetchAll();
                                ?>
                                <label class="form-label h5 mt-4">ไม่ต้องการเที่ยวประเภท:</label>
                                <div class="row mt-3 g-3 mb-4">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="col-md-3">
                                            <div class="card h-100 border-0" style="box-shadow: 5px 1px 20px 2px rgba(119, 184, 194, 0.1);">
                                                <div class="card-body d-flex justify-content-start align-items-center">
                                                    <input class="form-check-input custom-checkbox me-2" type="checkbox"
                                                        name="exclude_categories[]" value="<?= $cat['id'] ?>"
                                                        id="cat<?= $cat['id'] ?>">
                                                    <label class="form-check-label fw-semibold" for="cat<?= $cat['id'] ?>">
                                                        <?= htmlspecialchars($cat['name']) ?>
                                                    </label>
                                                </div>
                                            </div>

                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="tourism_type_id" id="tourism_type_id" value="">

                                <div class="d-flex justify-content-between mt-4">
                                    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> ยกเลิก</a>
                                    <button type="submit" class="btn btn-primary" id="nextBtn" disabled>ถัดไป <i class="fas fa-arrow-right ms-2"></i></button>
                                </div>


                            </form>
                        </div>


                    <?php elseif ($step === 2): ?>

                        <div class="step-header">
                            <h2 class="mb-0"><i class="fas fa-calendar-days me-2"></i> เลือกจำนวนวันที่จะเที่ยว</h2>
                            <p class="mb-0 mt-2 opacity-75">เลือกจำนวนวันที่คุณต้องการเที่ยว และจำนวนผู้เข้าพัก</p>
                        </div>

                        <div class="p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <?php
                            // ดึงประเภทที่พัก
                            $stmt = $pdo->query("SELECT * FROM accommodation_categories ORDER BY id");
                            $categories = $stmt->fetchAll();
                            ?>

                            <!-- เปิด form ครอบทั้ง checkbox และ input -->
                            <form method="POST">
                                <label class="form-label h5 mt-4">ต้องการให้แนะนำที่พักประเภทใด</label>
                                <div class="row mt-3 g-3 mb-4">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="col-md-4">
                                            <div class="card h-100 shadow-sm border-0">
                                                <div class="card-body d-flex justify-content-start align-items-center">
                                                    <!-- ปรับ syntax ให้ปิด > และใช้ value ที่เป็น type_name -->
                                                    <input class="form-check-input custom-checkbox me-2" type="checkbox"
                                                        name="accommodation_categories[]"
                                                        value="<?= htmlspecialchars($cat['type_name']) ?>"
                                                        id="cat<?= $cat['id'] ?>">
                                                    <label class="form-check-label fw-semibold" for="cat<?= $cat['id'] ?>">
                                                        <?= htmlspecialchars($cat['type_name']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label for="days" class="form-label h5">จำนวนวัน</label>
                                        <input type="number" class="form-control" id="days" name="days" min="1" max="14"
                                            value="<?= isset($_SESSION['trip_data']['days']) ? (int)$_SESSION['trip_data']['days'] : 1 ?>" required>
                                        <div class="form-text">เลือกจำนวนวันที่คุณจะเที่ยว ตั้งแต่ 1 ถึง 14 วัน</div>
                                    </div>

                                    <div class="col-md-6 mb-4">
                                        <label for="guest_count" class="form-label h5">จำนวนผู้เข้าพักทั้งหมด</label>
                                        <input type="number" class="form-control" id="guest_count" name="guest_count" min="1" max="20"
                                            value="<?= isset($_SESSION['trip_data']['guest_count']) ? (int)$_SESSION['trip_data']['guest_count'] : 1 ?>" required>
                                        <div class="form-text">ใส่จำนวนผู้เข้าพักทั้งหมด (ระบบจะคำนวณจำนวนห้องอัตโนมัติ)</div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="create-trip.php?step=1" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> ย้อนกลับ</a>
                                    <button type="submit" class="btn btn-primary">ถัดไป <i class="fas fa-arrow-right ms-2"></i></button>
                                </div>
                            </form>
                        </div>


                    <?php elseif ($step === 3): ?>

                        
                        <?php if (!empty($error2)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error2) ?></div>
                        <?php endif; ?>

                        <div class="step-header">
                            <h2 class="mb-0"><i class="fas fa-wallet me-2"></i> ใส่งบประมาณ</h2>
                            <p class="mb-0 mt-2 opacity-75">กำหนดงบประมาณสำหรับการเดินทาง</p>
                        </div>

                        <div class="p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-4">
                                    <label for="budget" class="form-label h5">งบประมาณ (บาท)</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text"><i class="fas fa-baht-sign"></i></span>
                                        <input type="number" class="form-control" id="budget" name="budget"
                                            min="1000" max="100000" 
                                            placeholder="เช่น 5000" required>
                                    </div>
                                    <div class="form-text">งบประมาณขั้นต่ำ 1,000 บาท</div>
                                </div>

                                <div class="mb-4">
                                    <p class="fw-bold mb-3">งบประมาณแนะนำ:</p>
                                    <div class="row">
                                        <div class="col-md-4 mb-2"><button type="button" class="btn btn-outline-primary w-100" onclick="setBudget(1500)">1500 บาท<br><small>ประหยัด</small></button></div>
                                        <div class="col-md-4 mb-2"><button type="button" class="btn btn-outline-primary w-100" onclick="setBudget(3000)">3000 บาท<br><small>ปานกลาง</small></button></div>
                                        <div class="col-md-4 mb-2"><button type="button" class="btn btn-outline-primary w-100" onclick="setBudget(5000)">5000 บาท<br><small>หรูหรา</small></button></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="create-trip.php?step=2" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> ย้อนกลับ</a>
                                    <button type="submit" class="btn btn-primary">ถัดไป <i class="fas fa-arrow-right ms-2"></i></button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($step === 4): ?>
                        <div class="step-header">
                            <h2 class="mb-0"><i class="fas fa-edit me-2"></i> กรอกชื่อทริปของคุณ</h2>
                            <p class="mb-0 mt-2 opacity-75">ตั้งชื่อทริป เพื่อบันทึกและแสดงผล</p>
                        </div>

                        <div class="p-4">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-4">
                                    <label for="trip_title" class="form-label h5">ชื่อทริป</label>
                                    <input type="text" class="form-control" id="trip_title" name="trip_title" maxlength="100" required
                                        value="<?= isset($_SESSION['trip_data']['trip_title']) ? htmlspecialchars($_SESSION['trip_data']['trip_title']) : '' ?>">
                                    <div class="form-text">ตั้งชื่อทริปของคุณ เช่น "ทริปธรรมชาติ พัทลุง 2025"</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="create-trip.php?step=3" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> ย้อนกลับ</a>
                                    <button type="submit" class="btn btn-primary">สร้างทริป <i class="fas fa-magic ms-2"></i></button>
                                </div>
                            </form>
                        </div>

                    <?php elseif ($step === 5): ?>
                        <div class="step-header">
                            <h2><i class="fas fa-check-circle me-2"></i> วางแผนเสร็จสิ้น!</h2>
                            <p class="mt-2 opacity-75">คุณสามารถดูแผนการเดินทางของคุณได้แล้ว</p>
                        </div>

                        <div class="p-4 text-center">
                            <div class="mb-4"><i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i></div>
                            <h4 class="mb-3">แผนการเดินทางของคุณพร้อมแล้ว!</h4>
                            <p class="text-muted mb-4">ระบบได้สร้างแผนการเดินทางตามความต้องการของคุณเรียบร้อยแล้ว</p>

                            <div class="d-flex gap-2 justify-content-center">
                                <a href="view-trip.php?id=<?= isset($_GET['id']) ? (int)$_GET['id'] : '' ?>" class="btn btn-success"><i class="fas fa-map me-2"></i> ดูแผนการเดินทาง</a>
                                <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-home me-2"></i> กลับหน้าหลัก</a>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Tourism type selection
        function selectTourismType(typeId, el) {
            document.querySelectorAll('.tourism-type-card').forEach(card => card.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('tourism_type_id').value = typeId;
            document.getElementById('nextBtn').disabled = false;
        }

        // Budget setter
        function setBudget(amount) {
            document.getElementById('budget').value = amount;
        }
    </script>
</body>

</html>