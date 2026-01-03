    <section class="hero-section">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-4">
                        ค้นพบเสน่ห์พัทลุง
                    </h1>
                    <p class="mb-5 fs-sm-5 w-100 w-sm-75 w-md-50 text-center">
                        วางแผนการเดินทางท่องเที่ยวในจังหวัดพัทลุงอย่างง่ายดาย พร้อมแนะนำสถานที่ท่องเที่ยว ที่พัก<br>
                        และเส้นทางที่น่าสนใจ
                    </p>


                    <?php if ($isLoggedIn): ?>
                        <a href="create-trip.php" class="btn btn-primary btn-lg rounded-5">
                            <i class="fas fa-plus me-2"></i>
                            สร้างแผนการเดินทางใหม่
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>
                            เริ่มต้นวางแผนการเดินทาง
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>