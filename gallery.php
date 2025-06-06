<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

// Підключення до БД
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}

// Пагінація
$per_page = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Всього зображень
$stmt = $pdo->query("SELECT COUNT(*) FROM images");
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Вибірка зображень (найновіші)
$stmt = $pdo->prepare("SELECT images.*, users.username FROM images JOIN users ON images.user_id = users.id ORDER BY images.created_at DESC LIMIT :per_page OFFSET :offset");
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="container">
    <h1>Галерея</h1>

    <?php foreach ($images as $img): ?>
        <div class="gallery-item">
            <img src="public/uploads/<?= htmlspecialchars($img['filename']) ?>" alt="Image" width="320" />
            <div>
                <span>Автор: <?= htmlspecialchars($img['username']) ?></span> |
                <span>Дата: <?= htmlspecialchars($img['created_at']) ?></span>
            </div>
            <div>
                <?php
                // Лайки
                $like_stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE image_id = ?");
                $like_stmt->execute([$img['id']]);
                $likes = $like_stmt->fetchColumn();

                // Коментарі
                $com_stmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE image_id = ?");
                $com_stmt->execute([$img['id']]);
                $comments_count = $com_stmt->fetchColumn();
                ?>
                <span>❤️ <?= $likes ?></span>
                <span>💬 <?= $comments_count ?></span>
            </div>

            <?php if (is_logged_in()): ?>
                <!-- Лайк -->
                <form method="post" action="like.php" style="display:inline;">
                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                    <button type="submit">Лайк</button>
                </form>

                <!-- Коментар -->
                <form method="post" action="comment.php">
                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                    <input type="text" name="comment" maxlength="255" required placeholder="Ваш коментар">
                    <button type="submit">Додати</button>
                </form>
            <?php endif; ?>

            <!-- Вивід коментарів -->
            <div class="comments">
                <?php
                $com_stmt = $pdo->prepare("SELECT comments.comment, users.username FROM comments JOIN users ON comments.user_id = users.id WHERE comments.image_id = ? ORDER BY comments.created_at ASC");
                $com_stmt->execute([$img['id']]);
                $comments = $com_stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($comments as $c):
                ?>
                    <div class="comment">
                        <b><?= htmlspecialchars($c['username']) ?>:</b>
                        <?= nl2br(htmlspecialchars($c['comment'])) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <hr>
    <?php endforeach; ?>

    <!-- Пагінація -->
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
