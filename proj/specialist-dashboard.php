<?php
require_once __DIR__ . '/includes/data.php';

$user = requireRole('specialist');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'update_profile') {
        $types = $_POST['types'] ?? [];
        if (!is_array($types)) {
            $types = [];
        }
        updateUserProfile($user['email'], [
            'specialization' => trim((string)($_POST['specialization'] ?? '')),
            'qualifications' => trim((string)($_POST['qualifications'] ?? '')),
            'experience' => trim((string)($_POST['experience'] ?? '')),
            'fee' => (float)($_POST['fee'] ?? 0),
            'types' => array_values($types),
        ]);
        flashSet('success', 'تم تحديث ملفك المهني.');
        header('Location: specialist-dashboard.php');
        exit;
    }

    if ($action === 'add_slot') {
        $newSlot = trim((string)($_POST['new_slot'] ?? ''));
        if ($newSlot !== '') {
            $profile = specialistProfile($user);
            $slots = $profile['slots'];
            if (!in_array($newSlot, $slots, true)) {
                $slots[] = $newSlot;
                updateUserProfile($user['email'], ['slots' => $slots]);
                flashSet('success', 'تمت إضافة الموعد المتاح.');
            }
        }
        header('Location: specialist-dashboard.php');
        exit;
    }

    if ($action === 'remove_slot') {
        $slotToRemove = (string)($_POST['slot'] ?? '');
        $profile = specialistProfile($user);
        $slots = array_values(array_diff($profile['slots'], [$slotToRemove]));
        updateUserProfile($user['email'], ['slots' => $slots]);
        flashSet('success', 'تمت إزالة الموعد.');
        header('Location: specialist-dashboard.php');
        exit;
    }

    if ($action === 'set_appointment_status') {
        $appointmentId = (string)($_POST['appointment_id'] ?? '');
        $newStatus = (string)($_POST['new_status'] ?? '');
        $allowed = ['مكتمل', 'ملغى'];
        if (in_array($newStatus, $allowed, true)) {
            $appointments = getAppointments();
            foreach ($appointments as &$appointment) {
                if ($appointment['id'] === $appointmentId && $appointment['specialist_email'] === $user['email']) {
                    $appointment['status'] = $newStatus;
                }
            }
            unset($appointment);
            saveAppointments($appointments);
            flashSet('success', 'تم تحديث حالة الموعد.');
        }
        header('Location: specialist-dashboard.php');
        exit;
    }
}

$profile = specialistProfile($user);

$myAppointments = array_values(array_filter(getAppointments(), function ($a) use ($user) {
    return $a['specialist_email'] === $user['email'];
}));
usort($myAppointments, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });

$upcoming = count(array_filter($myAppointments, function ($a) { return $a['status'] === 'مؤكد'; }));
$revenue = array_sum(array_column(array_filter($myAppointments, function ($a) { return in_array($a['status'], ['مؤكد', 'مكتمل'], true); }), 'fee'));

$pageTitle = 'لوحة الخبير الزراعي | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">لوحة الخبير الزراعي</h1>
            <p class="page-subtitle">أدر خدمات الاستشارة، والمواعيد المتاحة، وتأكيدات الحجوزات.</p>
        </section>

        <section>
            <div class="dashboard-grid">
                <div class="metric">
                    <small>المواعيد المتاحة</small>
                    <strong><?php echo count($profile['slots']); ?></strong>
                </div>
                <div class="metric">
                    <small>حجوزات قادمة</small>
                    <strong><?php echo $upcoming; ?></strong>
                </div>
                <div class="metric">
                    <small>الإيرادات المؤكدة</small>
                    <strong><?php echo formatMoney($revenue); ?></strong>
                </div>
            </div>

            <div class="section-block">
                <div class="section-head">
                    <h2>الحجوزات</h2>
                </div>
                <?php if (empty($myAppointments)): ?>
                    <p style="color: var(--muted); margin: 0;">لا توجد حجوزات بعد.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>رقم الموعد</th>
                                    <th>المزارع</th>
                                    <th>الموعد</th>
                                    <th>النوع</th>
                                    <th>الرسوم</th>
                                    <th>الحالة</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myAppointments as $appointment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['id']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['farmer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['slot']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['type']); ?></td>
                                        <td><?php echo formatMoney($appointment['fee']); ?></td>
                                        <td><span class="status <?php echo statusSlug($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span></td>
                                        <td>
                                            <?php if ($appointment['status'] === 'مؤكد'): ?>
                                                <form method="post" action="specialist-dashboard.php" style="display:inline-block;">
                                                    <input type="hidden" name="action" value="set_appointment_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                    <input type="hidden" name="new_status" value="مكتمل">
                                                    <button type="submit" class="btn btn-primary btn-sm">إتمام</button>
                                                </form>
                                                <form method="post" action="specialist-dashboard.php" style="display:inline-block;" onsubmit="return confirm('هل تريد إلغاء هذا الموعد؟');">
                                                    <input type="hidden" name="action" value="set_appointment_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                                    <input type="hidden" name="new_status" value="ملغى">
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
                <div class="section-head">
                    <h2>المواعيد المتاحة</h2>
                </div>
                <form method="post" action="specialist-dashboard.php" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:18px;">
                    <input type="hidden" name="action" value="add_slot">
                    <div style="flex:1;">
                        <label>إضافة موعد جديد</label>
                        <input type="text" name="new_slot" placeholder="مثال: الأحد 5:00م" required>
                    </div>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </form>

                <?php if (empty($profile['slots'])): ?>
                    <p style="color: var(--muted);">لا توجد مواعيد متاحة حالياً. أضف موعداً من الأعلى.</p>
                <?php else: ?>
                    <div class="request-grid">
                        <?php foreach ($profile['slots'] as $slot): ?>
                            <div class="request-card" style="display:flex; align-items:center; justify-content:space-between;">
                                <strong><?php echo htmlspecialchars($slot); ?></strong>
                                <form method="post" action="specialist-dashboard.php">
                                    <input type="hidden" name="action" value="remove_slot">
                                    <input type="hidden" name="slot" value="<?php echo htmlspecialchars($slot); ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">إزالة</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="section-block">
                <div class="section-head">
                    <h2>ملفي المهني</h2>
                </div>
                <form class="form-grid" method="post" action="specialist-dashboard.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-row">
                        <div>
                            <label>التخصص</label>
                            <input type="text" name="specialization" value="<?php echo htmlspecialchars($profile['specialization']); ?>">
                        </div>
                        <div>
                            <label>سنوات الخبرة</label>
                            <input type="text" name="experience" value="<?php echo htmlspecialchars($profile['experience']); ?>">
                        </div>
                    </div>
                    <div>
                        <label>المؤهلات</label>
                        <input type="text" name="qualifications" value="<?php echo htmlspecialchars($profile['qualifications']); ?>">
                    </div>
                    <div class="form-row">
                        <div>
                            <label>رسوم الاستشارة (ر.س)</label>
                            <input type="number" name="fee" min="0" step="1" value="<?php echo htmlspecialchars((string)$profile['fee']); ?>">
                        </div>
                        <div>
                            <label>أنواع الاستشارة المتاحة</label>
                            <label style="display:flex; align-items:center; gap:8px; font-weight:400; margin-top:8px;">
                                <input type="checkbox" name="types[]" value="حضورياً" style="width:auto;" <?php echo in_array('حضورياً', $profile['types'], true) ? 'checked' : ''; ?>> حضورياً
                            </label>
                            <label style="display:flex; align-items:center; gap:8px; font-weight:400;">
                                <input type="checkbox" name="types[]" value="عن بعد" style="width:auto;" <?php echo in_array('عن بعد', $profile['types'], true) ? 'checked' : ''; ?>> عن بعد
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary" style="justify-self: start;">حفظ التعديلات</button>
                </form>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
