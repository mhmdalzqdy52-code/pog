<?php
require_once __DIR__ . '/includes/data.php';

$user = requireRole('farmer');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_booking') {
    $specialistEmail = trim((string)($_POST['specialist_email'] ?? ''));
    $slot = trim((string)($_POST['slot'] ?? ''));
    $type = trim((string)($_POST['type'] ?? ''));
    $cardNumber = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
    $cardName = trim((string)($_POST['card_name'] ?? ''));
    $cardExpiry = trim((string)($_POST['card_expiry'] ?? ''));

    $specialist = findSpecialistProfile($specialistEmail);
    $errors = [];

    if ($specialist === null) {
        $errors[] = 'الخبير المحدد غير موجود.';
    } else {
        if (!in_array($slot, $specialist['slots'], true)) {
            $errors[] = 'يرجى اختيار موعد متاح من القائمة.';
        }
        if (!in_array($type, $specialist['types'], true)) {
            $errors[] = 'يرجى اختيار نوع استشارة متاح لهذا الخبير.';
        }
    }
    if ($cardName === '' || strlen($cardNumber) < 12 || $cardExpiry === '') {
        $errors[] = 'يرجى إدخال بيانات دفع صحيحة لإتمام الحجز.';
    }

    if (empty($errors)) {
        $appointments = getAppointments();
        $appointments[] = [
            'id' => newRefId('APT'),
            'farmer_email' => $user['email'],
            'farmer_name' => trim($user['first_name'] . ' ' . $user['last_name']),
            'specialist_email' => $specialist['email'],
            'specialist_name' => $specialist['name'],
            'slot' => $slot,
            'type' => $type,
            'fee' => $specialist['fee'],
            'status' => 'مؤكد',
            'created_at' => date('Y-m-d H:i'),
        ];
        saveAppointments($appointments);

        // Remove the booked slot so it cannot be double booked.
        $specialistUser = findUserByEmail($specialist['email']);
        if ($specialistUser !== null) {
            $remainingSlots = array_values(array_diff($specialist['slots'], [$slot]));
            updateUserProfile($specialist['email'], ['slots' => $remainingSlots]);
        }

        flashSet('success', 'تم تأكيد الحجز والدفع بنجاح مع ' . $specialist['name'] . ' (' . $slot . '). يمكنك متابعته من "مواعيدي".');
        header('Location: farmer-dashboard.php#appointments');
        exit;
    }

    flashSet('error', implode(' ', $errors));
    header('Location: booking.php?specialist=' . urlencode($specialistEmail));
    exit;
}

$specialists = getSpecialistProfiles();
$selectedEmail = trim((string)($_GET['specialist'] ?? ''));
$selected = null;
foreach ($specialists as $specialist) {
    if (strtolower($specialist['email']) === strtolower($selectedEmail)) {
        $selected = $specialist;
        break;
    }
}

$pageTitle = 'حجز موعد | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">احجز استشارة زراعية</h1>
            <p class="page-subtitle">اختر الخبير المناسب، وحدد نوع الاستشارة، وقم بالدفع الآمن ثم أكد الحجز.</p>
        </section>

        <section>
            <div class="section-head">
                <h2>اختر خبيراً زراعياً</h2>
            </div>

            <?php if (empty($specialists)): ?>
                <p style="color: var(--muted);">لا يوجد خبراء زراعيون مسجلون حالياً.</p>
            <?php endif; ?>

            <div class="request-grid" style="margin-bottom: 32px;">
                <?php foreach ($specialists as $specialist): ?>
                    <div class="request-card" style="<?php echo ($selected && $selected['email'] === $specialist['email']) ? 'border-color: var(--primary); box-shadow: 0 0 0 2px rgba(27,94,32,0.25);' : ''; ?>">
                        <h3><?php echo htmlspecialchars($specialist['name']); ?></h3>
                        <p><?php echo htmlspecialchars($specialist['specialization']); ?> — <?php echo htmlspecialchars($specialist['experience']); ?></p>
                        <small><?php echo htmlspecialchars($specialist['qualifications']); ?></small>
                        <p style="margin-top: 10px; font-weight: 800; color: var(--primary-dark);"><?php echo formatMoney($specialist['fee']); ?> / جلسة</p>
                        <p style="margin-top: 6px;">
                            <?php foreach ($specialist['types'] as $type): ?>
                                <span class="product-badge" style="margin-inline-end: 6px;"><?php echo htmlspecialchars($type); ?></span>
                            <?php endforeach; ?>
                        </p>
                        <a class="btn btn-primary btn-block" style="margin-top: 14px;" href="booking.php?specialist=<?php echo urlencode($specialist['email']); ?>">
                            <?php echo (empty($specialist['slots'])) ? 'لا توجد مواعيد متاحة' : 'اختيار هذا الخبير'; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($selected !== null): ?>
                <div class="section-head">
                    <h2>تفاصيل الحجز مع <?php echo htmlspecialchars($selected['name']); ?></h2>
                </div>
                <div class="booking-layout">
                    <div class="auth-card">
                        <h3>تفاصيل الاستشارة</h3>
                        <?php if (empty($selected['slots'])): ?>
                            <p style="color: var(--muted);">لا توجد مواعيد متاحة حالياً لدى هذا الخبير. يرجى اختيار خبير آخر أو المحاولة لاحقاً.</p>
                        <?php else: ?>
                            <form class="form-grid" method="post" action="booking.php">
                                <input type="hidden" name="action" value="confirm_booking">
                                <input type="hidden" name="specialist_email" value="<?php echo htmlspecialchars($selected['email']); ?>">
                                <div>
                                    <label for="slot">الموعد المتاح</label>
                                    <select id="slot" name="slot" required>
                                        <?php foreach ($selected['slots'] as $slotOption): ?>
                                            <option value="<?php echo htmlspecialchars($slotOption); ?>"><?php echo htmlspecialchars($slotOption); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="type">نوع الاستشارة</label>
                                    <select id="type" name="type" required>
                                        <?php foreach ($selected['types'] as $typeOption): ?>
                                            <option value="<?php echo htmlspecialchars($typeOption); ?>"><?php echo htmlspecialchars($typeOption); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-row">
                                    <div>
                                        <label for="card-number">رقم البطاقة</label>
                                        <input id="card-number" type="text" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required>
                                    </div>
                                    <div>
                                        <label for="card-expiry">تاريخ الانتهاء</label>
                                        <input id="card-expiry" type="text" name="card_expiry" placeholder="MM/YY" maxlength="5" required>
                                    </div>
                                </div>
                                <div>
                                    <label for="card-name">اسم حامل البطاقة</label>
                                    <input id="card-name" type="text" name="card_name" placeholder="كما هو مكتوب على البطاقة" required>
                                </div>
                                <p class="inline-note">بيانات دفع تجريبية لأغراض العرض فقط، ولا يتم تخزينها.</p>
                                <button type="submit" class="btn btn-primary">ادفع وتأكيد الحجز</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="summary-box">
                        <h4>ملخص الحجز</h4>
                        <ul>
                            <li>المزارع: <?php echo htmlspecialchars(trim($user['first_name'] . ' ' . $user['last_name'])); ?></li>
                            <li>الخبير: <?php echo htmlspecialchars($selected['name']); ?></li>
                            <li>التخصص: <?php echo htmlspecialchars($selected['specialization']); ?></li>
                            <li>رسوم الاستشارة: <strong><?php echo formatMoney($selected['fee']); ?></strong></li>
                        </ul>
                        <div class="price"><?php echo formatMoney($selected['fee']); ?></div>
                    </div>
                </div>
            <?php else: ?>
                <p style="color: var(--muted);">اختر خبيراً من القائمة أعلاه لعرض المواعيد المتاحة وإتمام الحجز.</p>
            <?php endif; ?>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
