<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';

// Доступ лише для авторизованих
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

// Завантаження накладних зображень
$overlays = array_diff(scandir(__DIR__ . '/public/overlays'), ['.', '..']);

// Вивід власних зображень (бічна панель)
require_once __DIR__ . '/config/database.php';
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->prepare("SELECT * FROM images WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $my_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $my_images = [];
}
?>

<main class="edit-layout">
    <section class="main-edit">
        <h1>Редагування фото</h1>
        <div id="camera-area">
            <video id="video" width="320" height="240" autoplay></video>
            <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>
            <img id="preview" src="" alt="Preview" style="display:none; max-width:320px;">
        </div>
        <div>
            <label>Або завантажте фото:
                <input type="file" id="upload" accept="image/*">
            </label>
        </div>
        <div>
            <h3>Оберіть накладку:</h3>
            <div id="overlay-list">
                <?php foreach ($overlays as $overlay): ?>
                    <img src="public/overlays/<?= htmlspecialchars($overlay) ?>" 
                         class="overlay-thumb" 
                         data-overlay="<?= htmlspecialchars($overlay) ?>"
                         width="64" height="48" alt="Overlay">
                <?php endforeach; ?>
            </div>
        </div>
        <button id="snap" disabled>Зробити знімок</button>
        <form id="save-form" method="post" enctype="multipart/form-data" action="upload.php" style="display:none;">
            <input type="hidden" name="photo" id="photo-data">
            <input type="hidden" name="overlay" id="overlay-data">
            <button type="submit">Зберегти зображення</button>
        </form>
        <div id="result-msg"></div>
    </section>
    <aside class="side-panel">
        <h2>Мої знімки</h2>
        <?php foreach ($my_images as $img): ?>
            <div class="my-image-thumb">
                <img src="public/uploads/<?= htmlspecialchars($img['filename']) ?>" width="80" alt="">
                <form method="post" action="delete_image.php" style="display:inline;">
                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                    <button type="submit" onclick="return confirm('Видалити це зображення?')">🗑️</button>
                </form>
            </div>
        <?php endforeach; ?>
    </aside>
</main>

<link rel="stylesheet" href="public/css/style.css">
<script src="public/js/main.js"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
