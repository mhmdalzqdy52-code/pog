<?php
require_once __DIR__ . '/includes/data.php';

$user = requireRole('supplier');
$displayName = $user['company_name'] !== '' ? $user['company_name'] : trim($user['first_name'] . ' ' . $user['last_name']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_product') {
        $name = trim((string)($_POST['name'] ?? ''));
        $category = trim((string)($_POST['category'] ?? 'معدات'));
        $price = (float)($_POST['price'] ?? 0);
        $availability = trim((string)($_POST['availability'] ?? 'متوفر'));
        $location = trim((string)($_POST['location'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($name === '' || $price <= 0 || $location === '') {
            flashSet('error', 'يرجى تعبئة اسم المنتج والسعر والموقع.');
        } else {
            $products = getProducts();
            $products[] = [
                'id' => newRefId('PRD'),
                'name' => $name,
                'category' => $category,
                'price' => $price,
                'availability' => $availability,
                'supplier_email' => $user['email'],
                'supplier' => $displayName,
                'location' => $location,
                'description' => $description !== '' ? $description : '—',
            ];
            saveProducts($products);
            flashSet('success', 'تمت إضافة المنتج بنجاح.');
        }
        header('Location: supplier-dashboard.php');
        exit;
    }

    if ($action === 'update_availability') {
        $productId = (string)($_POST['product_id'] ?? '');
        $availability = (string)($_POST['availability'] ?? '');
        $products = getProducts();
        foreach ($products as &$product) {
            if ($product['id'] === $productId && $product['supplier_email'] === $user['email']) {
                $product['availability'] = $availability;
            }
        }
        unset($product);
        saveProducts($products);
        flashSet('success', 'تم تحديث حالة التوفر.');
        header('Location: supplier-dashboard.php');
        exit;
    }

    if ($action === 'delete_product') {
        $productId = (string)($_POST['product_id'] ?? '');
        $products = array_values(array_filter(getProducts(), function ($product) use ($productId, $user) {
            return !($product['id'] === $productId && $product['supplier_email'] === $user['email']);
        }));
        saveProducts($products);
        flashSet('success', 'تم حذف المنتج.');
        header('Location: supplier-dashboard.php');
        exit;
    }

    if ($action === 'update_profile') {
        updateUserProfile($user['email'], [
            'company_name' => trim((string)($_POST['company_name'] ?? '')),
        ]);
        flashSet('success', 'تم تحديث بيانات المورّد.');
        header('Location: supplier-dashboard.php');
        exit;
    }
}

$myProducts = array_values(array_filter(getProducts(), function ($product) use ($user) {
    return $product['supplier_email'] === $user['email'];
}));

$lowStock = count(array_filter($myProducts, function ($p) { return $p['availability'] !== 'متوفر'; }));
$inventoryValue = array_sum(array_column($myProducts, 'price'));

$pageTitle = 'لوحة المورد | زراعة';
require __DIR__ . '/includes/header.php';
?>
    <main class="container">
        <section class="page-header">
            <h1 class="page-title">لوحة المورد</h1>
            <p class="page-subtitle">أدر قائمة المنتجات والسعر والتوفر في المخزون.</p>
        </section>

        <section>
            <div class="dashboard-grid">
                <div class="metric">
                    <small>المنتجات المدرجة</small>
                    <strong><?php echo count($myProducts); ?></strong>
                </div>
                <div class="metric">
                    <small>منتجات منخفضة أو غير متوفرة</small>
                    <strong><?php echo $lowStock; ?></strong>
                </div>
                <div class="metric">
                    <small>إجمالي قيمة المخزون</small>
                    <strong><?php echo formatMoney($inventoryValue); ?></strong>
                </div>
            </div>

            <div class="section-block">
                <div class="section-head">
                    <h2>إضافة منتج جديد</h2>
                </div>
                <form class="form-grid" method="post" action="supplier-dashboard.php">
                    <input type="hidden" name="action" value="add_product">
                    <div class="form-row">
                        <div>
                            <label>اسم المنتج</label>
                            <input type="text" name="name" placeholder="مثال: رشاش مبيدات محمول" required>
                        </div>
                        <div>
                            <label>الفئة</label>
                            <select name="category">
                                <option value="معدات">معدات</option>
                                <option value="مواد">مواد</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>السعر (ر.س)</label>
                            <input type="number" name="price" min="1" step="0.01" required>
                        </div>
                        <div>
                            <label>الموقع</label>
                            <input type="text" name="location" placeholder="مثال: الرياض" required>
                        </div>
                    </div>
                    <div>
                        <label>حالة التوفر</label>
                        <select name="availability">
                            <option value="متوفر">متوفر</option>
                            <option value="متوفر محدود">متوفر محدود</option>
                            <option value="غير متوفر">غير متوفر</option>
                        </select>
                    </div>
                    <div>
                        <label>الوصف</label>
                        <textarea name="description" rows="3" placeholder="وصف مختصر للمنتج"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="justify-self: start;">إضافة المنتج</button>
                </form>
            </div>

            <div class="section-block">
                <div class="section-head">
                    <h2>منتجاتي</h2>
                </div>
                <?php if (empty($myProducts)): ?>
                    <p style="color: var(--muted); margin: 0;">لم تضف أي منتجات بعد. استخدم النموذج أعلاه لإضافة أول منتج.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>المنتج</th>
                                    <th>الفئة</th>
                                    <th>السعر</th>
                                    <th>الموقع</th>
                                    <th>التوفر</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myProducts as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                                        <td><?php echo formatMoney($product['price']); ?></td>
                                        <td><?php echo htmlspecialchars($product['location']); ?></td>
                                        <td>
                                            <form method="post" action="supplier-dashboard.php" style="display:flex; gap:6px; align-items:center;">
                                                <input type="hidden" name="action" value="update_availability">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                                <select name="availability" onchange="this.form.submit()">
                                                    <option value="متوفر" <?php echo $product['availability'] === 'متوفر' ? 'selected' : ''; ?>>متوفر</option>
                                                    <option value="متوفر محدود" <?php echo $product['availability'] === 'متوفر محدود' ? 'selected' : ''; ?>>متوفر محدود</option>
                                                    <option value="غير متوفر" <?php echo $product['availability'] === 'غير متوفر' ? 'selected' : ''; ?>>غير متوفر</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <form method="post" action="supplier-dashboard.php" onsubmit="return confirm('هل تريد حذف هذا المنتج؟');">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['id']); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                            </form>
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
                    <h2>بيانات المورّد</h2>
                </div>
                <form class="form-grid" method="post" action="supplier-dashboard.php">
                    <input type="hidden" name="action" value="update_profile">
                    <div>
                        <label>اسم المتجر أو المؤسسة</label>
                        <input type="text" name="company_name" value="<?php echo htmlspecialchars($displayName); ?>">
                    </div>
                    <button type="submit" class="btn btn-secondary" style="justify-self: start;">حفظ</button>
                </form>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
