<?php
require_once __DIR__ . '/includes/db_connect.php';

// メッセージ
$message = '';

// ----------------------
// データ削除
// ----------------------
if (isset($_GET['delete_type'], $_GET['id'])) {
  $delete_type = $_GET['delete_type']; // hero / skill
  $id = (int)$_GET['id'];

  if ($delete_type === 'hero') {
    $stmt = $pdo->prepare("SELECT image_filename FROM heroes WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['image_filename'] && file_exists('images/heroes/' . $row['image_filename'])) {
      unlink('images/heroes/' . $row['image_filename']);
    }
    $stmt = $pdo->prepare("DELETE FROM heroes WHERE id = ?");
    $stmt->execute([$id]);
    $message = '英傑を削除しました';
  } elseif ($delete_type === 'skill') {
    $stmt = $pdo->prepare("SELECT image_filename FROM skills WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['image_filename'] && file_exists('images/skills/' . $row['image_filename'])) {
      unlink('images/skills/' . $row['image_filename']);
    }
    $stmt = $pdo->prepare("DELETE FROM skills WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'スキルを削除しました';
  }
}

// ----------------------
// データ追加
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
  $add_type = $_POST['add_type']; // hero / skill
  $name = trim($_POST['name']);

  // 画像処理
  $image_filename = '';
  if (!empty($_FILES['image']['name'])) {
    $uploadDir = $add_type === 'hero' ? 'images/heroes/' : 'images/skills/';
    $image_filename = basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_filename);
  }

  // hero_id / skill_id の自動採番
  if ($add_type === 'hero') {
    $stmt = $pdo->query("SELECT hero_id FROM heroes ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $num = $last ? (int)substr($last, 1) + 1 : 1;
    $new_id = 'h' . str_pad($num, 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO heroes (hero_id, name, image_filename) VALUES (?, ?, ?)");
    $stmt->execute([$new_id, $name, $image_filename]);
    $message = '英傑を追加しました';
  } elseif ($add_type === 'skill') {
    $stmt = $pdo->query("SELECT skill_id FROM skills ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetchColumn();
    $num = $last ? (int)substr($last, 1) + 1 : 1;
    $new_id = 's' . str_pad($num, 3, '0', STR_PAD_LEFT);
    $stmt = $pdo->prepare("INSERT INTO skills (skill_id, name, image_filename) VALUES (?, ?, ?)");
    $stmt->execute([$new_id, $name, $image_filename]);
    $message = 'スキルを追加しました';
  }
}

// ----------------------
// 英傑・スキル一覧取得
// ----------------------
$heroes = $pdo->query("SELECT * FROM heroes ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$skills = $pdo->query("SELECT * FROM skills ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="UTF-8">
  <title>管理画面 - 英傑・スキル管理</title>
  <style>
    body {
      font-family: sans-serif;
      padding: 20px;
    }

    h1,
    h2 {
      color: #0056b3;
    }

    table {
      border-collapse: collapse;
      width: 100%;
      margin-bottom: 20px;
    }

    th,
    td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: center;
    }

    th {
      background-color: #f0f0f0;
    }

    input[type=text] {
      padding: 5px;
      width: 200px;
    }

    button {
      padding: 5px 10px;
      margin: 2px;
    }

    .message {
      color: green;
      font-weight: bold;
      margin-bottom: 10px;
    }

    img {
      width: 50px;
      height: 50px;
      object-fit: contain;
    }
  </style>
</head>

<body>
  <h1>管理画面 - 英傑・スキル管理</h1>

  <?php if ($message) echo "<div class='message'>" . htmlspecialchars($message) . "</div>"; ?>

  <!-- 英傑追加フォーム -->
  <h2>英傑追加</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="add_type" value="hero">
    名前：<input type="text" name="name" required>
    画像：<input type="file" name="image">
    <button type="submit">追加</button>
  </form>

  <!-- 英傑一覧 -->
  <h2>英傑一覧</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>hero_id</th>
      <th>名前</th>
      <th>画像</th>
      <th>操作</th>
    </tr>
    <?php foreach ($heroes as $hero): ?>
      <tr>
        <td><?php echo $hero['id']; ?></td>
        <td><?php echo htmlspecialchars($hero['hero_id']); ?></td>
        <td><?php echo htmlspecialchars($hero['name']); ?></td>
        <td><?php if ($hero['image_filename']): ?><img src="images/heroes/<?php echo htmlspecialchars($hero['image_filename']); ?>" alt=""><?php endif; ?></td>
        <td>
          <a href="admin_edit.php?type=hero&id=<?php echo $hero['id']; ?>">編集</a>
          <a href="?delete_type=hero&id=<?php echo $hero['id']; ?>" onclick="return confirm('削除しますか？');">削除</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <!-- スキル追加フォーム -->
  <h2>スキル追加</h2>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="add_type" value="skill">
    名前：<input type="text" name="name" required>
    画像：<input type="file" name="image">
    <button type="submit">追加</button>
  </form>

  <!-- スキル一覧 -->
  <h2>スキル一覧</h2>
  <table>
    <tr>
      <th>ID</th>
      <th>skill_id</th>
      <th>名前</th>
      <th>画像</th>
      <th>操作</th>
    </tr>
    <?php foreach ($skills as $skill): ?>
      <tr>
        <td><?php echo $skill['id']; ?></td>
        <td><?php echo htmlspecialchars($skill['skill_id']); ?></td>
        <td><?php echo htmlspecialchars($skill['name']); ?></td>
        <td><?php if ($skill['image_filename']): ?><img src="images/skills/<?php echo htmlspecialchars($skill['image_filename']); ?>" alt=""><?php endif; ?></td>
        <td>
          <a href="admin_edit.php?type=skill&id=<?php echo $skill['id']; ?>">編集</a>
          <a href="?delete_type=skill&id=<?php echo $skill['id']; ?>" onclick="return confirm('削除しますか？');">削除</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

</body>

</html>