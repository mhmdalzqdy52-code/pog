<?php
require_once __DIR__ . '/includes/data.php';

$user = requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'submit_request') {
        $mechanicEmail = trim((string)($_POST['mechanic_email'] ?? ''));
        $equipment = trim((string)($_POST['equipment'] ?? ''));
        $issue = trim((string)($_POST['issue'] ?? ''));

        $mechanic = findUserByEmail($mechanicEmail);

        if ($mechanic === null || $equipment === '' || $issue === '') {
            flashSet('error', 'يرجى اختيار فني واضح المشكلة قبل الإرسال.');
        } else {
            $requests = getServiceRequests();
            $requests[] = [
                'id' => newRefId('SR'),
                'farmer_email' => $user['email'],
                'farmer_name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'mechanic_email' => $mechanic['email'],
                'mechanic_name' => trim($mechanic['first_name'] . ' ' . $mechanic['last_name']),
                'equipment' => $equipment,
                'issue' => $issue,
                'status' => 'قيد المراجعة',
                'created_at' => date('Y-m-d H:i'),
            ];
            saveServiceRequests($requests);
            flashSet('success', 'تم إرسال طلب الصيانة بنجاح. سيصلك إشعار عند تحديث حالته.');
        }
        header('Location: farmer-dashboard.php#requests');
        exit;
    }

    if ($action === 'cancel_request') {
        $requestId = (string)($_POST['request_id'] ?? '');
        $requests = getServiceRequests();
        foreach ($requests as &$request) {
            if ($request['id'] === $requestId && $request['farmer_email'] === $user['email'] && $request['status'] === 'قيد المراجعة') {
                $request['status'] = 'مرفوض';
            }
        }
        unset($request);
        saveServiceRequests($requests);
        flashSet('success', 'تم إلغاء طلب الصيانة.');
        header('Location: farmer-dashboard.php#requests');
        exit;
    }

    if ($action === 'cancel_appointment') {
        $appointmentId = (string)($_POST['appointment_id'] ?? '');
        $appointments = getAppointments();
        foreach ($appointments as &$appointment) {
            if ($appointment['id'] === $appointmentId && $appointment['farmer_email'] === $user['email'] && $appointment['status'] !== 'ملغى') {
                $appointment['status'] = 'ملغى';
            }
        }
        unset($appointment);
        saveAppointments($appointments);
        flashSet('success', 'تم إلغاء الموعد.');
        header('Location: farmer-dashboard.php#appointments');
        exit;
    }
}

$query = trim((string)($_GET['query'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$results = searchProducts($query, $category);
$mechanics = getMechanicProfiles();

$myRequests = array_values(array_filter(getServiceRequests(), function ($r) use ($user) {
    return $r['farmer_email'] === $user['email'];
}));
usort($myRequests, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });

$myAppointments = array_values(array_filter(getAppointments(), function ($a) use ($user) {
    return $a['farmer_email'] === $user['email'];
}));
usort($myAppointments, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });

$activeRequests = count(array_filter($myRequests, function ($r) { return $r['status'] === 'قيد المراجعة' || $r['status'] === 'قيد التنفيذ'; }));
$upcomingAppointments = count(array_filter($myAppointments, function ($a) { return $a['status'] === 'مؤكد'; }));

$pageTitle = 'لوحة المزارع | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">لوحة المزارع</h1>
            <p class="page-subtitle">ابحث عن المعدات والمواد الزراعية، واطلب الصيانة، وتابع مواعيدك بسهولة.</p>
        </section>

        <section>
            <div class="dashboard-grid">
                <div class="metric">
                    <small>المنتجات المتاحة</small>
                    <strong><?php echo count(getProducts()); ?></strong>
                </div>
                <div class="metric">
                    <small>طلباتي النشطة</small>
                    <strong><?php echo $activeRequests; ?></strong>
                </div>
                <div class="metric">
                    <small>مواعيدي القادمة</small>
                    <strong><?php echo $upcomingAppointments; ?></strong>
                </div>
            </div>

            <div class="search-panel" style="margin-bottom: 28px;">
                <form class="search-grid" method="get" action="farmer-dashboard.php">
                    <div>
                        <input type="text" name="query" placeholder="ابحث عن المعدات أو المواد" value="<?php echo htmlspecialchars($query); ?>">
                    </div>
                    <div>
                        <select name="category">
                            <option value="" <?php echo $category === '' ? 'selected' : ''; ?>>كل الفئات</option>
                            <option value="معدات" <?php echo $category === 'معدات' ? 'selected' : ''; ?>>معدات</option>
                            <option value="مواد" <?php echo $category === 'مواد' ? 'selected' : ''; ?>>مواد</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">بحث</button>
                    </div>
                </form>
            </div>

            <div class="section-block">
                <div class="section-head">
                    <h2>المعدات والمواد المتاحة</h2>
                    <a href="booking.php" class="btn btn-secondary">حجز استشارة</a>
                </div>

                <?php if (empty($results)): ?>
                    <p style="color: var(--muted);">لا توجد نتائج مطابقة لبحثك. جرّب كلمة أخرى أو امسح الفلتر.</p>
                <?php endif; ?>

                <div class="equipment-grid">
                    <?php foreach ($results as $product): ?>
                        <article class="equipment-card">
                            <div class="equipment-header">
                                <span class="product-badge"><?php echo htmlspecialchars($product['category']); ?></span>
                                <span class="price-tag"><?php echo formatMoney($product['price']); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="equipment-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <ul class="equipment-meta">
                                <li><strong>المورد:</strong> <?php echo htmlspecialchars($product['supplier']); ?></li>
                                <li><strong>الموقع:</strong> <?php echo htmlspecialchars($product['location']); ?></li>
                                <li><strong>التوفر:</strong> <span class="status <?php echo statusSlug($product['availability']); ?>"><?php echo htmlspecialchars($product['availability']); ?></span></li>
                            </ul>
                            <?php if ($product['availability'] !== 'غير متوفر' && !empty($product['supplier_email'])): ?>
                                <a class="btn btn-primary btn-block" href="mailto:<?php echo htmlspecialchars($product['supplier_email']); ?>?subject=<?php echo rawurlencode('استفسار عن ' . $product['name']); ?>">تواصل مع المورد</a>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-block" disabled>غير متوفر حالياً</button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-block" id="requests">
                <div class="section-head">
                    <h2>طلب صيانة جديد</h2>
                </div>

                <?php if (empty($mechanics)): ?>
                    <p style="color: var(--muted);">لا يوجد فنيو صيانة مسجلون حالياً.</p>
                <?php endif; ?>

                <div class="request-grid">
                    <?php foreach ($mechanics as $mechanic): ?>
                        <div class="request-card">
                            <h3><?php echo htmlspecialchars($mechanic['name']); ?></h3>
                            <p><?php echo htmlspecialchars($mechanic['specialization']); ?> — <?php echo htmlspecialchars($mechanic['experience']); ?></p>
                            <small><?php echo htmlspecialchars($mechanic['services']); ?></small>
                            <details style="margin-top: 14px;">
                                <summary class="btn btn-secondary btn-sm">إرسال طلب صيانة</summary>
                                <form method="post" action="farmer-dashboard.php#requests" class="form-grid" style="margin-top: 14px;">
                                    <input type="hidden" name="action" value="submit_request">
                                    <input type="hidden" name="mechanic_email" value="<?php echo htmlspecialchars($mechanic['email']); ?>">
                                    <div>
                                        <label>اسم المعدة</label>
                                        <input type="text" name="equipment" placeholder="مثال: جرار، مضخة ري" required>
                                    </div>
                                    <div>
                                        <label>وصف المشكلة</label>
                                        <textarea name="issue" rows="3" placeholder="صف المشكلة بالتفصيل" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">إرسال الطلب</button>
                                </form>
                            </details>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section-block">
                <div class="section-head">
                    <h2>طلبات الصيانة الخاصة بي</h2>
                </div>

                <?php if (empty($myRequests)): ?>
                    <p style="color: var(--muted);">لم ترسل أي طلبات صيانة بعد.</p>
                <?php else: ?>
                    <div class="request-grid">
                        <?php foreach ($myRequests as $request): ?>
                            <div class="request-card">
                                <div class="request-top">
                                    <span class="request-id"><?php echo htmlspecialchars($request['id']); ?></span>
                                    <span class="status <?php echo statusSlug($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars($request['equipment']); ?></h3>
                                <p><?php echo htmlspecialchars($request['issue']); ?></p>
                                <small>الفني: <?php echo htmlspecialchars($request['mechanic_name']); ?></small>
                                <?php if ($request['status'] === 'قيد المراجعة'): ?>
                                    <form method="post" action="farmer-dashboard.php#requests" style="margin-top: 12px;" onsubmit="return confirm('هل تريد إلغاء هذا الطلب؟');">
                                        <input type="hidden" name="action" value="cancel_request">
                                        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">إلغاء الطلب</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-block" id="appointments">
                <div class="section-head">
                    <h2>مواعيدي</h2>
                    <a href="booking.php" class="btn btn-secondary">حجز موعد جديد</a>
                </div>

                <?php if (empty($myAppointments)): ?>
                    <p style="color: var(--muted);">لا توجد مواعيد محجوزة بعد.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>رقم الموعد</th>
                                    <th>الخبير</th>
                                    <th>الموعد</th>
                                    <th>النوع</th>
                                    <th>الرسوم</th>
                                    <th>الحالة</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myAppointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['specialist_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['slot']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['type']); ?></td>
                                        <td><?php echo formatMoney($appointment['fee']); ?></td>
                                        <td><span class="status <?php echo statusSlug($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span></td>
                                        <td>
                                            <?php if ($appointment['status'] === 'مؤكد'): ?>
                                                <form method="post" action="farmer-dashboard.php#appointments" onsubmit="return confirm('هل تريد إلغاء هذا الموعد؟');">
                                                    <input type="hidden" name="action" value="cancel_appointment">
                                                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">إلغاء</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
