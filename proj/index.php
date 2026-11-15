<?php
require_once __DIR__ . '/includes/data.php';

$query = trim((string)($_GET['query'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$results = searchProducts($query, $category);

$pageTitle = 'زراعة | نظام المعدات والخدمات الزراعية';
require __DIR__ . '/includes/header.php';
?>
    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <h1>نربط المزارعين بالمعدات والخدمات والخبرة الزراعية.</h1>
                    <p>
                        ابحث عن الآلات المناسبة، والميكانيكيين الموثوقين، والخبراء الزراعيين، والخدمات الاستشارية جميعها في منصة واحدة مصممة للزراعة الحديثة.
                    </p>
                    <div class="hero-actions">
                        <a href="register.php" class="btn btn-primary">إنشاء حساب</a>
                        <a href="booking.php" class="btn btn-secondary">احجز استشارة</a>
                    </div>
                    <div class="stats">
                        <div class="stat-box">
                            <strong><?php echo count(getProducts()); ?></strong>
                            <span>منتج ومستلزم</span>
                        </div>
                        <div class="stat-box">
                            <strong><?php echo count(getMechanicProfiles()); ?></strong>
                            <span>فني صيانة</span>
                        </div>
                        <div class="stat-box">
                            <strong><?php echo count(getSpecialistProfiles()); ?></strong>
                            <span>خبير زراعي</span>
                        </div>
                    </div>
                </div>

                <div class="hero-card">
                    <h3>لماذا يختار المزارعون زراعة؟</h3>
                    <ul class="feature-list">
                        <li>البحث عن المعدات والمواد الزراعية حسب الفئة</li>
                        <li>العثور على ميكانيكيين ومزودين مؤهلين</li>
                        <li>حجز استشارات مدفوعة حضورياً أو عبر الإنترنت</li>
                        <li>متابعة الطلبات والحجوزات من موقع واحد</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="features">
            <div class="container">
                <div class="section-header">
                    <h2>الخدمات الأساسية</h2>
                    <p>كل ما يحتاجه المزارع لإدارة المعدات والطلبات والخدمات الاستشارية.</p>
                </div>

                <div class="grid-3">
                    <article class="card">
                        <span class="tag">المعدات</span>
                        <h3>البحث عن المنتجات</h3>
                        <p>ابحث عن الأدوات والمواد الزراعية حسب الاسم أو الفئة وتحقق من توفرها من المورد.</p>
                    </article>
                    <article class="card">
                        <span class="tag">الصيانة</span>
                        <h3>طلبات الإصلاح</h3>
                        <p>أرسل مشكلة فنية وتتبع حالة طلب الصيانة والإصلاح من خلال الميكانيكيين.</p>
                    </article>
                    <article class="card">
                        <span class="tag">الاستشارة</span>
                        <h3>استشارة الخبراء</h3>
                        <p>احجز استشارات مع المختصين الزراعيين حضورياً أو عبر الإنترنت مع رسوم واضحة ودفع آمن.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="search">
            <div class="container">
                <div class="section-header">
                    <h2>ابحث في السوق</h2>
                    <p>استخدم لوحة واحدة للعثور على المعدات والمواد الزراعية بسرعة.</p>
                </div>

                <div class="search-panel">
                    <form class="search-grid" action="index.php" method="get">
                        <div>
                            <input type="text" name="query" placeholder="ابحث عن المنتجات، مثال: جرار أو ري" value="<?php echo htmlspecialchars($query); ?>">
                        </div>
                        <div>
                            <select name="category">
                                <option value="" <?php echo $category === '' ? 'selected' : ''; ?>>كل الفئات</option>
                                <option value="معدات" <?php echo $category === 'معدات' ? 'selected' : ''; ?>>معدات</option>
                                <option value="مواد" <?php echo $category === 'مواد' ? 'selected' : ''; ?>>مواد</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">بحث</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="section-header">
                    <h2><?php echo ($query !== '' || $category !== '') ? 'نتائج البحث' : 'المنتجات الشائعة'; ?></h2>
                    <p>عناصر متاحة حديثاً من موردين موثوقين.</p>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>الفئة</th>
                                <th>المورد</th>
                                <th>السعر</th>
                                <th>التوفر</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($results)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; color: var(--muted); padding: 28px;">لا توجد منتجات مطابقة لبحثك. جرّب كلمات أخرى.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($results as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                                    <td><?php echo htmlspecialchars($product['supplier']); ?></td>
                                    <td><?php echo formatMoney($product['price']); ?></td>
                                    <td><span class="status <?php echo statusSlug($product['availability']); ?>"><?php echo htmlspecialchars($product['availability']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
<?php require __DIR__ . '/includes/footer.php'; ?>
