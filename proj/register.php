<?php
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerUser($_POST);
    if ($result['success']) {
        header('Location: login.php?message=' . urlencode($result['message']));
        exit;
    }
    $errorMessage = $result['message'];
}

$selectedType = $_POST['account_type'] ?? 'مزارع';
$pageTitle = 'إنشاء حساب | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">أنشئ حسابك</h1>
            <p class="page-subtitle">انضم إلى السوق الزراعي كـ مزارع أو مورد أو ميكانيكي أو خبير.</p>
        </section>

        <section>
            <div class="auth-wrap">
                <div class="auth-card">
                    <h3>إنشاء حساب</h3>
                    <?php if (!empty($errorMessage)): ?>
                        <div class="flash flash-error"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    <form class="form-grid" method="post" action="register.php">
                        <div class="form-row">
                            <div>
                                <label for="first-name">الاسم الأول</label>
                                <input id="first-name" type="text" name="first_name" placeholder="الاسم الأول" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                            </div>
                            <div>
                                <label for="last-name">اسم العائلة</label>
                                <input id="last-name" type="text" name="last_name" placeholder="اسم العائلة" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <div>
                            <label for="email">البريد الإلكتروني</label>
                            <input id="email" type="email" name="email" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="phone">رقم الهاتف</label>
                            <input id="phone" type="tel" name="phone" placeholder="+966 5xx xxx xxx" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                        <div>
                            <label for="account-type">نوع الحساب</label>
                            <select id="account-type" name="account_type">
                                <option value="مزارع" <?php echo $selectedType === 'مزارع' ? 'selected' : ''; ?>>مزارع</option>
                                <option value="مورد" <?php echo $selectedType === 'مورد' ? 'selected' : ''; ?>>مورد</option>
                                <option value="ميكانيكي" <?php echo $selectedType === 'ميكانيكي' ? 'selected' : ''; ?>>ميكانيكي</option>
                                <option value="خبير زراعي" <?php echo $selectedType === 'خبير زراعي' ? 'selected' : ''; ?>>خبير زراعي</option>
                            </select>
                        </div>
                        <div id="company-field" style="<?php echo $selectedType === 'مورد' ? '' : 'display:none;'; ?>">
                            <label for="company-name">اسم المتجر أو المؤسسة</label>
                            <input id="company-name" type="text" name="company_name" placeholder="مثال: الحديقة الخضراء" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                        </div>
                        <div class="form-row">
                            <div>
                                <label for="password">كلمة المرور</label>
                                <input id="password" type="password" name="password" placeholder="4 أحرف على الأقل" required>
                            </div>
                            <div>
                                <label for="password-confirm">تأكيد كلمة المرور</label>
                                <input id="password-confirm" type="password" name="password_confirm" placeholder="أعد كتابة كلمة المرور" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">تسجيل</button>
                    </form>
                    <p class="inline-note">هل لديك حساب؟ <a href="login.php">سجل الدخول</a>.</p>
                </div>

                <div class="summary-box">
                    <h4>مزايا المنصة</h4>
                    <ul>
                        <li>إدارة حسابك وملفك الشخصي بأمان</li>
                        <li>الوصول إلى الميزات حسب نوع المستخدم</li>
                        <li>التواصل مع المزارعين والموردين والخبراء</li>
                        <li>متابعة الحجوزات والمنتجات وحالة الخدمات</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>
    <script>
        (function () {
            var typeSelect = document.getElementById('account-type');
            var companyField = document.getElementById('company-field');
            if (!typeSelect || !companyField) { return; }
            typeSelect.addEventListener('change', function () {
                companyField.style.display = (typeSelect.value === 'مورد') ? '' : 'none';
            });
        })();
    </script>
<?php require __DIR__ . '/includes/footer.php'; ?>
