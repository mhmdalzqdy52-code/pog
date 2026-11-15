<?php
require_once __DIR__ . '/includes/data.php';

$user = requireRole('mechanic');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'set_status') {
        $requestId = (string)($_POST['request_id'] ?? '');
        $newStatus = (string)($_POST['new_status'] ?? '');
        $allowed = ['مقبول', 'مرفوض', 'قيد التنفيذ', 'مكتمل'];

        if (in_array($newStatus, $allowed, true)) {
            $requests = getServiceRequests();
            foreach ($requests as &$request) {
                if ($request['id'] === $requestId && $request['mechanic_email'] === $user['email']) {
                    $request['status'] = $newStatus;
                }
            }
            unset($request);
            saveServiceRequests($requests);
            flashSet('success', 'تم تحديث حالة الطلب إلى: ' . $newStatus);
        }
        header('Location: mechanic-dashboard.php');
        exit;
    }

    if ($action === 'update_profile') {
        updateUserProfile($user['email'], [
            'specialization' => trim((string)($_POST['specialization'] ?? '')),
            'experience' => trim((string)($_POST['experience'] ?? '')),
            'services' => trim((string)($_POST['services'] ?? '')),
        ]);
        flashSet('success', 'تم تحديث ملفك المهني.');
        header('Location: mechanic-dashboard.php');
        exit;
    }
}

$profile = mechanicProfile($user);

$myRequests = array_values(array_filter(getServiceRequests(), function ($r) use ($user) {
    return $r['mechanic_email'] === $user['email'];
}));
usort($myRequests, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });

$openCount = count(array_filter($myRequests, function ($r) { return $r['status'] === 'قيد المراجعة'; }));
$acceptedCount = count(array_filter($myRequests, function ($r) { return in_array($r['status'], ['مقبول', 'قيد التنفيذ'], true); }));
$completedCount = count(array_filter($myRequests, function ($r) { return $r['status'] === 'مكتمل'; }));

$pageTitle = 'لوحة الميكانيكي | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">لوحة الميكانيكي</h1>
            <p class="page-subtitle">راجع طلبات الصيانة وحدّث حالة كل مشكلة في المعدات.</p>
        </section>

        <section>
            <div class="dashboard-grid">
                <div class="metric">
                    <small>الطلبات المفتوحة</small>
                    <strong><?php echo $openCount; ?></strong>
                </div>
                <div class="metric">
                    <small>قيد المعالجة</small>
                    <strong><?php echo $acceptedCount; ?></strong>
                </div>
                <div class="metric">
                    <small>المكتملة</small>
                    <strong><?php echo $completedCount; ?></strong>
                </div>
            </div>

            <?php if (empty($myRequests)): ?>
                <div class="section-block">
                    <p style="color: var(--muted); margin: 0;">لا توجد طلبات صيانة موجهة إليك حالياً. عندما يرسل أحد المزارعين طلباً باسمك سيظهر هنا.</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>المزارع</th>
                                <th>المعدات</th>
                                <th>المشكلة</th>
                                <th>الحالة</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                                    <td><?php echo htmlspecialchars($request['farmer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['equipment']); ?></td>
                                    <td><?php echo htmlspecialchars($request['issue']); ?></td>
                                    <td><span class="status <?php echo statusSlug($request['status']); ?>"><?php echo htmlspecialchars($request['status']); ?></span></td>
                                    <td>
                                        <?php if ($request['status'] === 'قيد المراجعة'): ?>
                                            <form method="post" action="mechanic-dashboard.php" style="display:inline-block;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                                <input type="hidden" name="new_status" value="مقبول">
                                                <button type="submit" class="btn btn-primary btn-sm">قبول</button>
                                            </form>
                                            <form method="post" action="mechanic-dashboard.php" style="display:inline-block;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                                <input type="hidden" name="new_status" value="مرفوض">
                                                <button type="submit" class="btn btn-danger btn-sm">رفض</button>
                                            </form>
                                        <?php elseif ($request['status'] === 'مقبول'): ?>
                                            <form method="post" action="mechanic-dashboard.php" style="display:inline-block;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                                <input type="hidden" name="new_status" value="قيد التنفيذ">
                                                <button type="submit" class="btn btn-secondary btn-sm">بدء العمل</button>
                                            </form>
                                        <?php elseif ($request['status'] === 'قيد التنفيذ'): ?>
                                            <form method="post" action="mechanic-dashboard.php" style="display:inline-block;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                                <input type="hidden" name="new_status" value="مكتمل">
                                                <button type="submit" class="btn btn-primary btn-sm">تحديد كمكتمل</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="section-block" style="margin-top: 28px;">
                <div class="section-head">
                    <h2>ملفي المهني</h2>
                </div>
                <form class="form-grid" method="post" action="mechanic-dashboard.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div>
                        <label>التخصص</label>
                        <input type="text" name="specialization" value="<?php echo htmlspecialchars($profile['specialization']); ?>">
                    </div>
                    <div>
                        <label>سنوات الخبرة</label>
                        <input type="text" name="experience" value="<?php echo htmlspecialchars($profile['experience']); ?>">
                    </div>
                    <div>
                        <label>الخدمات المقدَّمة</label>
                        <input type="text" name="services" value="<?php echo htmlspecialchars($profile['services']); ?>">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="justify-self: start;">حفظ التعديلات</button>
                </form>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
