<?php

require_once __DIR__ . '/auth.php';

/* =========================================================
   Generic JSON-backed storage helpers
   ========================================================= */

function storagePath(string $file): string
{
    return __DIR__ . '/../storage/' . $file;
}

function ensureJsonStorage(string $file, array $default): void
{
    $dir = dirname(storagePath($file));

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    if (!file_exists(storagePath($file))) {
        file_put_contents(storagePath($file), json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

function readJsonStore(string $file, array $default = []): array
{
    ensureJsonStorage($file, $default);
    $data = json_decode((string)file_get_contents(storagePath($file)), true);

    return is_array($data) ? $data : $default;
}

function writeJsonStore(string $file, array $data): bool
{
    ensureJsonStorage($file, []);

    return file_put_contents(storagePath($file), json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

function newRefId(string $prefix): string
{
    return $prefix . '-' . strtoupper(substr(uniqid(), -6));
}

function formatMoney($amount): string
{
    return number_format((float)$amount, 2) . ' ر.س';
}

function statusSlug(string $status): string
{
    $map = [
        'متوفر' => 'in-stock',
        'متوفر محدود' => 'limited',
        'غير متوفر' => 'out-of-stock',
        'قيد المراجعة' => 'pending',
        'مقبول' => 'accepted',
        'مرفوض' => 'rejected',
        'قيد التنفيذ' => 'in-progress',
        'مكتمل' => 'completed',
        'بانتظار الدفع' => 'pending-payment',
        'مؤكد' => 'confirmed',
        'ملغى' => 'cancelled',
    ];

    return $map[$status] ?? 'pending';
}

/* =========================================================
   Products (owned by supplier accounts)
   ========================================================= */

function seedProducts(): array
{
    return [
        [
            'id' => 'PRD-001',
            'name' => 'مجموعة بداية الجرار',
            'category' => 'معدات',
            'price' => 2850,
            'availability' => 'متوفر',
            'supplier_email' => 'supplier@zra3a.com',
            'supplier' => 'الحديقة الخضراء',
            'location' => 'الرياض',
            'description' => 'مجموعة كاملة لإعداد الجرار وصيانته قبل موسم الزراعة.',
        ],
        [
            'id' => 'PRD-002',
            'name' => 'خط الري بالتنقيط',
            'category' => 'مواد',
            'price' => 120,
            'availability' => 'متوفر محدود',
            'supplier_email' => 'supplier@zra3a.com',
            'supplier' => 'مزرعة المياه',
            'location' => 'جدة',
            'description' => 'أنابيب ري متينة تساعد على توزيع الماء بكفاءة داخل الحقول.',
        ],
        [
            'id' => 'PRD-003',
            'name' => 'خلاط السماد',
            'category' => 'معدات',
            'price' => 1450,
            'availability' => 'متوفر',
            'supplier_email' => 'supplier@zra3a.com',
            'supplier' => 'محصول الصحراء',
            'location' => 'الدمام',
            'description' => 'خلاط قوي لتوزيع الأسمدة بدقة وسهولة خلال العمل اليومي.',
        ],
        [
            'id' => 'PRD-004',
            'name' => 'رش المبيدات الزراعية',
            'category' => 'مواد',
            'price' => 75,
            'availability' => 'متوفر',
            'supplier_email' => 'supplier@zra3a.com',
            'supplier' => 'صحراء الزراعة',
            'location' => 'أبها',
            'description' => 'منتج فعال لمكافحة الآفات والأمراض النباتية في الحقول.',
        ],
    ];
}

function getProducts(): array
{
    return readJsonStore('products.json', seedProducts());
}

function saveProducts(array $products): bool
{
    return writeJsonStore('products.json', $products);
}

function searchProducts(string $query = '', string $category = ''): array
{
    $items = getProducts();
    $query = trim(mb_strtolower($query));
    $category = trim($category);

    return array_values(array_filter($items, function ($product) use ($query, $category) {
        $matchesQuery = $query === ''
            || mb_stripos($product['name'], $query) !== false
            || mb_stripos($product['description'], $query) !== false;

        $matchesCategory = $category === ''
            || $category === 'الكل'
            || $category === 'كل الفئات'
            || $product['category'] === $category;

        return $matchesQuery && $matchesCategory;
    }));
}

/* =========================================================
   Maintenance / service requests
   ========================================================= */

function getServiceRequests(): array
{
    return readJsonStore('service_requests.json', []);
}

function saveServiceRequests(array $requests): bool
{
    return writeJsonStore('service_requests.json', $requests);
}

/* =========================================================
   Consultation appointments
   ========================================================= */

function getAppointments(): array
{
    return readJsonStore('appointments.json', []);
}

function saveAppointments(array $appointments): bool
{
    return writeJsonStore('appointments.json', $appointments);
}

/* =========================================================
   Mechanic / specialist profiles
   Profile fields live directly on the user's account record so
   that the professional profile always matches a real login.
   ========================================================= */

function mechanicProfile(array $user): array
{
    return [
        'email' => $user['email'] ?? '',
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'specialization' => $user['specialization'] ?? 'صيانة عامة للمعدات الزراعية',
        'experience' => $user['experience'] ?? 'غير محدد',
        'services' => $user['services'] ?? 'تشخيص الأعطال، صيانة دورية',
    ];
}

function getMechanicProfiles(): array
{
    return array_map('mechanicProfile', usersByRole('mechanic'));
}

function specialistProfile(array $user): array
{
    $types = $user['types'] ?? ['حضورياً', 'عن بعد'];
    if (!is_array($types)) {
        $types = array_filter(array_map('trim', explode(',', (string)$types)));
    }

    $slots = $user['slots'] ?? ['الأحد 10:00ص', 'الثلاثاء 1:00م', 'الخميس 11:00ص'];
    if (!is_array($slots)) {
        $slots = array_filter(array_map('trim', explode(',', (string)$slots)));
    }

    return [
        'email' => $user['email'] ?? '',
        'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
        'specialization' => $user['specialization'] ?? 'استشارات زراعية عامة',
        'qualifications' => $user['qualifications'] ?? 'غير محدد',
        'experience' => $user['experience'] ?? 'غير محدد',
        'fee' => isset($user['fee']) && $user['fee'] !== '' ? (float)$user['fee'] : 150,
        'types' => array_values($types),
        'slots' => array_values($slots),
    ];
}

function getSpecialistProfiles(): array
{
    return array_map('specialistProfile', usersByRole('specialist'));
}

function findSpecialistProfile(string $email): ?array
{
    foreach (getSpecialistProfiles() as $profile) {
        if (strtolower($profile['email']) === strtolower($email)) {
            return $profile;
        }
    }

    return null;
}
