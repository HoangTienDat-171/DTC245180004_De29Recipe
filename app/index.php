<?php
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

function e($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

$host = getenv('MYSQL_HOST') ?: 'mysql';
$dbname = getenv('MYSQL_DATABASE') ?: 'recipe29';
$user = getenv('MYSQL_USER') ?: '';
$password = getenv('MYSQL_PASSWORD') ?: '';

$keyword = is_string($_GET['q'] ?? '')
    ? trim($_GET['q'] ?? '') : '';

$categoryId = filter_var(
    $_GET['category'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) ?: 0;

$recipeId = filter_var(
    $_GET['id'] ?? 0,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
) ?: 0;

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    $categories = $pdo->query(
        'SELECT id, name FROM categories ORDER BY id'
    )->fetchAll();

    $recipe = null;
    $recipes = [];

    if ($recipeId > 0) {
        $stmt = $pdo->prepare(
            'SELECT r.*, c.name AS category_name
             FROM recipes r
             JOIN categories c ON c.id = r.category_id
             WHERE r.id = :id'
        );
        $stmt->execute(['id' => $recipeId]);
        $recipe = $stmt->fetch();
    } else {
        $sql = 'SELECT r.*, c.name AS category_name
                FROM recipes r
                JOIN categories c ON c.id = r.category_id
                WHERE 1=1';

        $params = [];

        if ($keyword !== '') {
            $sql .= ' AND (
                r.name LIKE :search_name
                OR r.description LIKE :search_desc
            )';
            $params['search_name'] = "%{$keyword}%";
            $params['search_desc'] = "%{$keyword}%";
        }

        if ($categoryId > 0) {
            $sql .= ' AND r.category_id = :category';
            $params['category'] = $categoryId;
        }

        $sql .= ' ORDER BY r.id';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $recipes = $stmt->fetchAll();
    }
} catch (PDOException $ex) {
    error_log('Recipe database error: ' . $ex->getMessage());
    http_response_code(503);
    exit('Khong the ket noi co so du lieu. Vui long thu lai.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bếp Nhà Mình - Công thức nấu ăn</title>
<style>
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Arial, sans-serif;
    color: #332b25;
    background: #fffaf4;
    line-height: 1.6;
}
header {
    background: #fff;
    border-bottom: 1px solid #eee4d7;
    padding: 18px 0;
}
.container {
    width: 92%;
    max-width: 1100px;
    margin: auto;
}
nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.logo {
    font-size: 25px;
    font-weight: bold;
    color: #e65b19;
    text-decoration: none;
}
nav a {
    color: #664f3c;
    text-decoration: none;
}
.hero {
    background: #ffeed6;
    border-radius: 18px;
    padding: 40px;
    margin: 35px 0 25px;
}
.hero h1 {
    font-size: 34px;
    margin: 0 0 8px;
}
.hero p { margin: 0; color: #745b45; }
form {
    display: flex;
    gap: 12px;
    margin: 25px 0;
    flex-wrap: wrap;
}
input, select {
    padding: 13px;
    border: 1px solid #e2d8ce;
    border-radius: 8px;
    background: #fff;
    font-size: 15px;
}
input { flex: 1; min-width: 200px; }
button, .btn {
    padding: 13px 22px;
    background: #e65b19;
    border: 0;
    border-radius: 8px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}
h2 { margin-top: 30px; }
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 22px;
}
.card {
    background: white;
    border: 1px solid #f0e7dc;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 6px 20px #3a24100a;
}
.card-image {
    height: 125px;
    background: linear-gradient(135deg, #ffe4c5, #ffbe90);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9e4318;
    font-size: 19px;
    font-weight: bold;
}
.card-body { padding: 20px; }
.card h3 { color: #c64a16; margin: 8px 0; }
.card p { color: #706459; }
.tag {
    color: #b45309;
    background: #fff3df;
    padding: 5px 10px;
    border-radius: 30px;
    font-size: 13px;
}
.card a { color: #d44e14; font-weight: bold; }
.detail {
    margin: 35px 0;
    background: white;
    padding: 32px;
    border-radius: 16px;
}
.detail h1 { color: #c64a16; }
.detail li { margin: 7px 0; }
.notice {
    padding: 25px;
    background: white;
    border-radius: 10px;
}
footer {
    margin-top: 60px;
    text-align: center;
    padding: 20px;
    color: #8a7565;
    border-top: 1px solid #eee4d7;
}
@media (max-width: 800px) {
    .grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 550px) {
    .grid { grid-template-columns: 1fr; }
    .hero { padding: 25px; }
    .hero h1 { font-size: 27px; }
}
</style>
</head>
<body>
<header>
  <div class="container">
    <nav>
      <a class="logo" href="/">Bếp Nhà Mình</a>
      <a href="/">Trang chủ</a>
    </nav>
  </div>
</header>

<main class="container">
<?php if ($recipeId > 0): ?>
    <?php if ($recipe): ?>
        <article class="detail">
            <a href="/">← Quay lại danh sách</a>
            <h1><?= e($recipe['name']) ?></h1>
            <span class="tag"><?= e($recipe['category_name']) ?></span>

            <p><?= e($recipe['description']) ?></p>
            <p>
                <strong>Thời gian:</strong>
                <?= (int)$recipe['cooking_time'] ?> phút
                |
                <strong>Khẩu phần:</strong>
                <?= (int)$recipe['servings'] ?> người
            </p>

            <h2>Nguyên liệu</h2>
            <ul>
            <?php foreach (explode(',', $recipe['ingredients']) as $item): ?>
                <li><?= e(trim($item)) ?></li>
            <?php endforeach; ?>
            </ul>

            <h2>Cách chế biến</h2>
            <p><?= nl2br(e($recipe['instructions'])) ?></p>
        </article>
    <?php else: ?>
        <div class="notice">
            <h2>Không tìm thấy công thức</h2>
            <a href="/">Quay lại trang chủ</a>
        </div>
    <?php endif; ?>

<?php else: ?>
    <section class="hero">
        <h1>Hôm nay ăn gì?</h1>
        <p>Khám phá những món ăn ngon và dễ làm cho gia đình bạn.</p>
    </section>

    <form method="get" action="/">
        <input
            type="text"
            name="q"
            placeholder="Tìm món ăn..."
            value="<?= e($keyword) ?>"
        >
        <select name="category">
            <option value="0">Tất cả danh mục</option>
            <?php foreach ($categories as $category): ?>
                <option
                    value="<?= (int)$category['id'] ?>"
                    <?= $categoryId === (int)$category['id']
                        ? 'selected' : '' ?>
                >
                    <?= e($category['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Tìm kiếm</button>
    </form>

    <h2>Danh sách công thức (<?= count($recipes) ?>)</h2>

    <?php if (!$recipes): ?>
        <div class="notice">
            Không tìm thấy công thức phù hợp.
        </div>
    <?php else: ?>
        <div class="grid">
        <?php foreach ($recipes as $item): ?>
            <article class="card">
                <div class="card-image">
                    Bếp Nhà Mình
                </div>
                <div class="card-body">
                    <span class="tag">
                        <?= e($item['category_name']) ?>
                    </span>
                    <h3><?= e($item['name']) ?></h3>
                    <p><?= e($item['description']) ?></p>
                    <p>
                        Thời gian:
                        <?= (int)$item['cooking_time'] ?> phút
                    </p>
                    <a href="/?id=<?= (int)$item['id'] ?>">
                        Xem công thức →
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
</main>

<footer>
    Bếp Nhà Mình - Website Công thức Nấu ăn
    | Đề tài 29 - DTC245180004
</footer>
</body>
</html>
