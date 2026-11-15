<?php
require_once __DIR__ . '/includes/data.php';

$user = requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'cancel_appointment') {
        $appointmentId = (string)($_POST['appointment_id'] ?? '');
        $appointments = getAppointments();
        foreach ($appointments as &$appointment) {
            if ($appointment['id'] === $appointmentId) {
                $appointment['status'] = 'ملغى';
            }
        }
        unset($appointment);
        saveAppointments($appointments);
        flashSet('success', 'تم إلغاء الموعد من قبل الإدارة.');
        header('Location: admin-dashboard.php');
        exit;
    }

    if ($action === 'resolve_request') {
        $requestId = (string)($_POST['request_id'] ?? '');
        $requests = getServiceRequests();
        foreach ($requests as &$request) {
            if ($request['id'] === $requestId) {
                $request['status'] = 'مكتمل';
            }
        }
        unset($request);
        saveServiceRequests($requests);
        flashSet('success', 'تم إغلاق طلب الصيانة من قبل الإدارة.');
        header('Location: admin-dashboard.php');
        exit;
    }
}

$users = getUsers();
$products = getProducts();
$requests = getServiceRequests();
$appointments = getAppointments();

$usersByRoleCounts = [
    'farmer' => 0, 'supplier' => 0, 'mechanic' => 0, 'specialist' => 0, 'admin' => 0,
];
foreach ($users as $u) {
    $role = (string)($u['role'] ?? 'farmer');
    if (isset($usersByRoleCounts[$role])) {
        $usersByRoleCounts[$role]++;
    }
}

$confirmedRevenue = array_sum(array_column(array_filter($appointments, function ($a) {
    return in_array($a['status'], ['مؤكد', 'مكتمل'], true);
}), 'fee'));

$adminStats = [
    ['label' => 'المزارعون', 'value' => (string)$usersByRoleCounts['farmer']],
    ['label' => 'الموردون', 'value' => (string)$usersByRoleCounts['supplier']],
    ['label' => 'الميكانيكيون', 'value' => (string)$usersByRoleCounts['mechanic']],
    ['label' => 'الخبراء الزراعيون', 'value' => (string)$usersByRoleCounts['specialist']],
    ['label' => 'إجمالي الحجوزات', 'value' => (string)count($appointments)],
    ['label' => 'إيرادات مؤكدة', 'value' => formatMoney($confirmedRevenue)],
];

$recentRequests = $requests;
usort($recentRequests, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
$recentRequests = array_slice($recentRequests, 0, 6);

$recentAppointments = $appointments;
usort($recentAppointments, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
$recentAppointments = array_slice($recentAppointments, 0, 6);

$pageTitle = 'لوحة المدير | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">لوحة المدير</h1>
            <p class="page-subtitle">راقب نشاط المنصة والحجوزات والمدفوعات وإدارة المستخدمين على مستوى النظام.</p>
        </section>

        <section>
            <div class="dashboard-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
                <?php foreach ($adminStats as $stat): ?>
                    <div class="metric">
                        <small><?php echo htmlspecialchars($stat['label']); ?></small>
                        <strong><?php echo htmlspecialchars($stat['value']); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="section-block">
                <div class="section-head"><h2>أحدث طلبات الصيانة</h2></div>
                <?php if (empty($recentRequests)): ?>
                    <p style="color: var(--muted); margin:0;">لا توجد طلبات بعد.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>الرقم</th><th>المزارع</th><th>الفني</th><th>الحالة</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $request): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['id']); ?></td>
                                        <td><?php echo htmlspecialchars($request['farmer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($request['mechanic_name']); ?></td>
                                        <td><span class="status <?php echo statusSlug($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
                                        <td>
                                            <?php if ($request['status'] !== 'مكتمل'): ?>
                                                <form method="post" action="admin-dashboard.php">
                                                    <input type="hidden" name="action" value="resolve_request">
                                                    <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm">إغلاق الطلب</button>
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

            <div class="section-block">
                <div class="section-head"><h2>أحدث الحجوزات والمدفوعات</h2></div>
                <?php if (empty($recentAppointments)): ?>
                    <p style="color: var(--muted); margin:0;">لا توجد حجوزات بعد.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>الرقم</th><th>المزارع</th><th>الخبير</th><th>الرسوم</th><th>الحالة</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAppointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['farmer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['specialist_name']); ?></td>
                                        <td><?php echo formatMoney($appointment['fee']); ?></td>
                                        <td><span class="status <?php echo statusSlug($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span></td>
                                        <td>
                                            <?php if ($appointment['status'] === 'مؤكد'): ?>
                                                <form method="post" action="admin-dashboard.php" onsubmit="return confirm('هل تريد إلغاء هذا الموعد؟');">
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

            <div class="section-block">
                <div class="section-head"><h2>المستخدمون المسجّلون</h2></div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الدور</th><th>تاريخ التسجيل</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars(roleLabel((string)($u['role'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($u['created_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
