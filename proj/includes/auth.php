<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function authUsersFile(): string
{
    return __DIR__ . '/../storage/users.json';
}

function ensureUsersStorage(): void
{
    $dir = dirname(authUsersFile());

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (!file_exists(authUsersFile())) {
        file_put_contents(authUsersFile(), '[]');
    }
}

function getUsers(): array
{
    ensureUsersStorage();

    $content = file_get_contents(authUsersFile());
    $users = json_decode($content, true);

    return is_array($users) ? $users : [];
}

function saveUsers(array $users): bool
{
    ensureUsersStorage();

    return file_put_contents(authUsersFile(), json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function findUserByEmail(string $email): ?array
{
    $email = strtolower(trim($email));
    foreach (getUsers() as $user) {
        if (strtolower((string)($user['email'] ?? '')) === $email) {
            return $user;
        }
    }
    return null;
}

function usersByRole(string $role): array
{
    return array_values(array_filter(getUsers(), function ($user) use ($role) {
        return (string)($user['role'] ?? '') === $role;
    }));
}

function updateUserProfile(string $email, array $fields): bool
{
    $email = strtolower(trim($email));
    $users = getUsers();
    $found = false;

    foreach ($users as &$user) {
        if (strtolower((string)($user['email'] ?? '')) === $email) {
            $user = array_merge($user, $fields);
            $found = true;
            break;
        }
    }
    unset($user);

    if (!$found) {
        return false;
    }

    $saved = saveUsers($users);

    if ($saved && isset($_SESSION['auth_user']) && strtolower((string)($_SESSION['auth_user']['email'] ?? '')) === $email) {
        $_SESSION['auth_user'] = findUserByEmail($email);
    }

    return $saved;
}

function normalizeRole(string $role): string
{
    $role = trim($role);
    $map = [
        'مزارع' => 'farmer',
        'مورد' => 'supplier',
        'ميكانيكي' => 'mechanic',
        'خبير زراعي' => 'specialist',
        'مدير' => 'admin',
        'farmer' => 'farmer',
        'supplier' => 'supplier',
        'mechanic' => 'mechanic',
        'specialist' => 'specialist',
        'admin' => 'admin',
    ];

    return $map[$role] ?? 'farmer';
}

function roleLabel(string $role): string
{
    $map = [
        'farmer' => 'مزارع',
        'supplier' => 'مورد',
        'mechanic' => 'ميكانيكي',
        'specialist' => 'خبير زراعي',
        'admin' => 'مدير النظام',
    ];

    return $map[$role] ?? $role;
}

function getDashboardForRole(string $role): string
{
    $map = [
        'farmer' => 'farmer-dashboard.php',
        'supplier' => 'supplier-dashboard.php',
        'mechanic' => 'mechanic-dashboard.php',
        'specialist' => 'specialist-dashboard.php',
        'admin' => 'admin-dashboard.php',
    ];

    return $map[$role] ?? 'farmer-dashboard.php';
}

function registerUser(array $input): array
{
    $firstName = trim((string)($input['first_name'] ?? ''));
    $lastName = trim((string)($input['last_name'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $phone = trim((string)($input['phone'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $passwordConfirm = (string)($input['password_confirm'] ?? '');
    $role = normalizeRole((string)($input['account_type'] ?? ''));
    $companyName = trim((string)($input['company_name'] ?? ''));

    if ($firstName === '' || $lastName === '' || $email === '' || $password === '') {
        return ['success' => false, 'message' => 'يرجى ملء جميع الحقول المطلوبة.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'يرجى إدخال بريد إلكتروني صحيح.'];
    }

    if (strlen($password) < 4) {
        return ['success' => false, 'message' => 'يجب أن تكون كلمة المرور 4 أحرف على الأقل.'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'message' => 'كلمتا المرور غير متطابقتين.'];
    }

    $users = getUsers();
    foreach ($users as $user) {
        if (strtolower((string)($user['email'] ?? '')) === $email) {
            return ['success' => false, 'message' => 'هذا البريد الإلكتروني مستخدم مسبقاً.'];
        }
    }

    $users[] = [
        'id' => time() . rand(100, 999),
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'phone' => $phone,
        'role' => $role,
        'company_name' => $companyName,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    if (!saveUsers($users)) {
        return ['success' => false, 'message' => 'حدثت مشكلة أثناء حفظ الحساب. حاول مرة أخرى.'];
    }

    return ['success' => true, 'message' => 'تم إنشاء الحساب بنجاح. يمكنك تسجيل الدخول الآن.'];
}

function isPasswordValid(string $password, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }

    if (password_verify($password, $storedHash)) {
        return true;
    }

    if (!preg_match('/^\$2[aby]\$/', $storedHash)) {
        return hash_equals($storedHash, $password);
    }

    return false;
}

function loginUser(string $email, string $password, ?string $selectedRole = null): array
{
    $email = strtolower(trim($email));
    $users = getUsers();

    foreach ($users as $user) {
        $storedEmail = strtolower((string)($user['email'] ?? ''));
        $storedHash = (string)($user['password_hash'] ?? '');
        $storedRole = (string)($user['role'] ?? '');

        if ($storedEmail !== $email || !isPasswordValid($password, $storedHash)) {
            continue;
        }

        if ($selectedRole !== null && $selectedRole !== '') {
            $normalizedSelectedRole = normalizeRole($selectedRole);
            if ($normalizedSelectedRole !== $storedRole) {
                return ['success' => false, 'message' => 'نوع الحساب غير مطابق للحساب المسجل.'];
            }
        }

        return ['success' => true, 'user' => $user];
    }

    return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.'];
}

function currentUser(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function redirectIfLoggedIn(): void
{
    if (($user = currentUser()) !== null) {
        $dashboard = getDashboardForRole((string)($user['role'] ?? 'farmer'));
        header('Location: ' . $dashboard);
        exit;
    }
}

function requireLogin(): array
{
    $user = currentUser();

    if ($user === null) {
        header('Location: login.php?message=' . urlencode('يرجى تسجيل الدخول للمتابعة.'));
        exit;
    }

    $fresh = findUserByEmail((string)($user['email'] ?? ''));
    if ($fresh !== null) {
        $_SESSION['auth_user'] = $fresh;
        return $fresh;
    }

    return $user;
}

function requireRole(string $role): array
{
    $user = requireLogin();

    if ((string)($user['role'] ?? '') !== $role) {
        header('Location: ' . getDashboardForRole((string)($user['role'] ?? 'farmer')));
        exit;
    }

    return $user;
}

function flashSet(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function flashGet(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}
