<?php
require_once __DIR__ . '/auth.php';
$__user = currentUser();
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'زراعة'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container navbar">
            <a href="index.php" class="logo">زراعة</a>
            <nav class="nav-links">
                <a href="index.php">الرئيسية</a>
                <?php if ($__user): ?>
                    <a href="<?php echo htmlspecialchars(getDashboardForRole((string)($__user['role'] ?? 'farmer'))); ?>">لوحة التحكم</a>
                    <?php if (($__user['role'] ?? '') === 'farmer'): ?>
                        <a href="booking.php">حجز استشارة</a>
                    <?php endif; ?>
                    <span class="nav-user">مرحباً، <?php echo htmlspecialchars(($__user['first_name'] ?? '') . ' · ' . roleLabel((string)($__user['role'] ?? ''))); ?></span>
                    <a href="logout.php" class="btn btn-secondary">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="login.php">تسجيل الدخول</a>
                    <a href="register.php" class="btn btn-secondary">إنشاء حساب</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <?php $__flash = flashGet(); ?>
    <?php if ($__flash !== null): ?>
        <div class="container">
            <div class="flash flash-<?php echo htmlspecialchars($__flash['type']); ?>"><?php echo htmlspecialchars($__flash['text']); ?></div>
        </div>
    <?php endif; ?>
