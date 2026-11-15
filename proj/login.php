<?php
require_once __DIR__ . '/includes/auth.php';

redirectIfLoggedIn();

$message = '';
$errorMessage = '';

if (isset($_GET['message'])) {
    $message = (string)$_GET['message'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = loginUser(
        (string)($_POST['email'] ?? ''),
        (string)($_POST['password'] ?? '')
    );
    if ($result['success']) {
        $_SESSION['auth_user'] = $result['user'];
        flashSet('success', 'تم تسجيل الدخول بنجاح. أهلاً بعودتك!');
        $dashboard = getDashboardForRole((string)($result['user']['role'] ?? 'farmer'));
        header('Location: ' . $dashboard);
        exit;
    }
    $errorMessage = $result['message'];
}

$pageTitle = 'تسجيل الدخول | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">مرحباً بعودتك</h1>
            <p class="page-subtitle">سجل دخولك للوصول إلى لوحة التحكم وإدارة المنتجات والحجوزات والطلبات.</p>
        </section>

        <section>
            <div class="auth-wrap">
                <div class="auth-card">
                    <h3>تسجيل الدخول</h3>
                    <?php if (!empty($message)): ?>
                        <div class="flash flash-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errorMessage)): ?>
                        <div class="flash flash-error"><?php echo htmlspecialchars($errorMessage); ?></div>
                    <?php endif; ?>
                    <form class="form-grid" method="post" action="login.php">
                        <div>
                            <label for="email">البريد الإلكتروني</label>
                            <input id="email" type="email" name="email" placeholder="farmer@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        <div>
                            <label for="password">كلمة المرور</label>
                            <input id="password" type="password" name="password" placeholder="أدخل كلمة المرور" required>
                        </div>
                        <button type="submit" class="btn btn-primary">دخول</button>
                    </form>
                    <p class="inline-note">ليس لديك حساب؟ <a href="register.php">أنشئ حساباً الآن</a>.</p>
                </div>

                <div class="summary-box">
                    <h4>حسابات تجريبية للاختبار</h4>
                    <ul>
                        <li>مزارع: farmer@zra3a.com</li>
                        <li>مورد: supplier@zra3a.com</li>
                        <li>ميكانيكي: mechanic@zra3a.com</li>
                        <li>خبير زراعي: specialist@zra3a.com</li>
                        <li>مدير النظام: admin@zra3a.com</li>
                    </ul>
                    <p class="inline-note">كلمة المرور لجميع الحسابات التجريبية: <strong>Demo@1234</strong></p>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
