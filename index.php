<?php
// index.php - 所持英雄スキル登録画面

// データベース接続ファイルを読み込む
require_once __DIR__ . '/includes/db_connect.php';

// 英傑データをデータベースから取得
$heroes = [];
try {
    $stmt = $pdo->query("SELECT hero_id, name, image_filename FROM heroes ORDER BY id");
    $heroes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("英傑データの取得エラー: " . $e->getMessage());
    // ユーザーには一般的なエラーメッセージを表示
    $error_message = "英傑データの読み込みに失敗しました。しばらくしてから再度お試しください。";
}

// スキルデータをデータベースから取得
$skills = [];
try {
    $stmt = $pdo->query("SELECT skill_id, name, image_filename FROM skills ORDER BY id");
    $skills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("スキルデータの取得エラー: " . $e->getMessage());
    // ユーザーには一般的なエラーメッセージを表示
    $error_message = (isset($error_message) ? $error_message . "<br>" : "") . "スキルデータの読み込みに失敗しました。";
}

// エラーメッセージがあれば表示
if (isset($error_message)) {
    echo '<p style="color: red;">' . $error_message . '</p>';
    // ここで処理を中断しても良いですが、今回はUIを表示し続けます
}

// 画像のベースパス
// サーバーの構造に合わせて調整してください。
// 例: images/heroes/H001.png に画像がある場合、'images/heroes/'
// public_html直下にimagesがある場合、'/images/' もしくは 'images/'
// 今回は /images/heroes/ と /images/skills/ に置く想定
define('HERO_IMAGE_BASE_PATH', 'images/heroes/');
define('SKILL_IMAGE_BASE_PATH', 'images/skills/');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>所持英雄スキル登録画面</title>
    <style>
        /* ここにCSSを記述してレイアウトを調整 */
        body { font-family: sans-serif; margin: 0; padding: 10px; display: flex; flex-direction: column; align-items: center; background-color: #f4f4f4; color: #333; }
        h1 { color: #0056b3; margin-bottom: 20px; }
        h2, h3 { color: #0056b3; border-bottom: 1px solid #eee; padding-bottom: 5px; margin-top: 20px; }
        .container { display: flex; width: 100%; max-width: 960px; flex-wrap: wrap; justify-content: center; gap: 10px; }
        
        /* 左右のパネル */
        .selection-panel, .party-panel {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }

        .selection-panel {
            flex: 2; /* 選択エリアは広めに */
            min-width: 320px; /* スマホでの見やすさ調整 */
        }
        .party-panel {
            flex: 1; /* 選択されたアイテム表示エリア */
            min-width: 320px; /* スマホでの見やすさ調整 */
            min-height: 250px; /* ある程度の高さを確保 */
        }

        /* アイテムリスト */
        .item-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px; /* アイテム間のスペース */
            margin-top: 10px;
        }
        .item-list-hero .item-card, .item-list-skill .item-card {
            width: 70px; /* 英傑/スキル画像のサイズ */
            height: 70px;
            cursor: pointer;
            border: 2px solid transparent;
            border-radius: 5px;
            overflow: hidden;
            background-color: #e0e0e0; /* 背景色 */
            display: flex;
            align-items: center;
            justify-content: center;
            transition: border-color 0.2s ease-in-out;
        }
        .item-list-hero .item-card:hover, .item-list-skill .item-card:hover {
            border-color: #007bff; /* ホバー時のボーダー色 */
        }
        .item-list-hero .item-card img, .item-list-skill .item-card img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* 画像のアスペクト比を維持して表示 */
            display: block;
        }

        /* 選択されたアイテムの表示 */
        .selected-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            border: 1px dashed #ccc;
            min-height: 100px; /* 選択エリアの最小高 */
            padding: 10px;
            margin-top: 10px;
            box-sizing: border-box; /* パディングを幅に含める */
        }
        .selected-item {
            position: relative;
            display: inline-block;
            border: 1px solid #28a745; /* 緑色のボーダー */
            border-radius: 5px;
            overflow: hidden;
            width: 70px; /* 選択後の英傑/スキル画像のサイズ */
            height: 70px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .selected-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .remove-btn {
            position: absolute;
            top: -5px; /* 角に配置 */
            right: -5px; /* 角に配置 */
            background: #dc3545; /* 赤色 */
            color: white;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            line-height: 22px;
            text-align: center;
            cursor: pointer;
            font-size: 0.8em;
            font-weight: bold;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
            transition: background-color 0.2s;
        }
        .remove-btn:hover {
            background-color: #c82333;
        }

        /* フォームエリア */
        .input-form-area {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 960px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group textarea {
            width: calc(100% - 20px); /* パディング考慮 */
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group textarea {
            resize: vertical; /* 縦方向のみリサイズ可能に */
        }
        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            margin-top: 10px;
            transition: background-color 0.2s;
        }
        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        /* スマホ特化のためのメディアクエリ */
        @media (max-width: 768px) {
            .container {
                flex-direction: column; /* 縦並びにする */
                align-items: center;
            }
            .selection-panel, .party-panel, .input-form-area {
                width: calc(100% - 20px); /* 左右のパディング考慮 */
                max-width: none; /* 最大幅を解除 */
            }
            .party-panel {
                order: -1; /* スマホでは選択済みエリアを上部に表示 */
            }
        }
    </style>
</head>
<body>
    <h1>所持英雄スキル登録</h1>

    <form id="registrationForm" action="register.php" method="POST">
        <div class="container">
            <div class="selection-panel">
                <h2>英傑一覧</h2>
                <div id="availableHeroes" class="item-list item-list-hero">
                    <?php foreach ($heroes as $hero): ?>
                        <div class="item-card"
                             data-id="<?php echo htmlspecialchars($hero['hero_id']); ?>"
                             data-name="<?php echo htmlspecialchars($hero['name']); ?>"
                             data-image="<?php echo htmlspecialchars($hero['image_filename']); ?>"
                             data-type="hero">
                            <img src="<?php echo HERO_IMAGE_BASE_PATH . htmlspecialchars($hero['image_filename']); ?>"
                                 alt="<?php echo htmlspecialchars($hero['name']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <h2>スキル一覧</h2>
                <div id="availableSkills" class="item-list item-list-skill">
                    <?php foreach ($skills as $skill): ?>
                        <div class="item-card"
                             data-id="<?php echo htmlspecialchars($skill['skill_id']); ?>"
                             data-name="<?php echo htmlspecialchars($skill['name']); ?>"
                             data-image="<?php echo htmlspecialchars($skill['image_filename']); ?>"
                             data-type="skill">
                            <img src="<?php echo SKILL_IMAGE_BASE_PATH . htmlspecialchars($skill['image_filename']); ?>"
                                 alt="<?php echo htmlspecialchars($skill['name']); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="party-panel">
                <h2>選択された編成</h2>
                <h3>英傑</h3>
                <div id="selectedHeroes" class="selected-list">
                    </div>
                <h3>スキル</h3>
                <div id="selectedSkills" class="selected-list">
                    </div>
                <input type="hidden" name="selected_hero_ids" id="selectedHeroIdsInput">
                <input type="hidden" name="selected_skill_ids" id="selectedSkillIdsInput">
            </div>
        </div>

        <div class="input-form-area">
            <h2>その他の情報</h2>
            <div class="form-group">
                <label for="warrantsCount">委任状の数:</label>
                <input type="number" id="warrantsCount" name="warrants_count" min="0" value="0">
            </div>
            <div class="form-group">
                <label for="userName">あなたの名前（任意）:</label>
                <input type="text" id="userName" name="user_name" maxlength="100">
            </div>
            <div class="form-group">
                <label for="memo">メモ（任意）:</label>
                <textarea id="memo" name="memo" rows="5"></textarea>
            </div>
            <button type="submit">この内容で登録する</button>
        </div>
    </form>

    <script>
        // PHPから取得したデータをJavaScript変数に代入
        // json_encode() でPHP配列をJavaScriptで使えるJSON形式に変換
        const initialHeroes = <?php echo json_encode($heroes); ?>;
        const initialSkills = <?php echo json_encode($skills); ?>;

        const HERO_IMAGE_BASE_PATH = '<?php echo HERO_IMAGE_BASE_PATH; ?>';
        const SKILL_IMAGE_BASE_PATH = '<?php echo SKILL_IMAGE_BASE_PATH; ?>';

        const selectedHeroes = []; // 選択された英傑のIDを格納する配列
        const selectedSkills = []; // 選択されたスキルのIDを格納する配列

        const availableHeroesDiv = document.getElementById('availableHeroes');
        const availableSkillsDiv = document.getElementById('availableSkills');
        const selectedHeroesDiv = document.getElementById('selectedHeroes');
        const selectedSkillsDiv = document.getElementById('selectedSkills');
        const selectedHeroIdsInput = document.getElementById('selectedHeroIdsInput');
        const selectedSkillIdsInput = document.getElementById('selectedSkillIdsInput');
        const registrationForm = document.getElementById('registrationForm');

        // --- 英傑・スキル一覧のレンダリング（DBから取得したデータを使用） ---
        // この関数は、PHPでHTMLに直接埋め込む方法に変更したため、
        // 今後の動的なフィルタリングなどで必要になった場合に参照する程度でOKです。
        // 現状はPHPが生成したHTMLがそのまま表示されます。
        // function renderAvailableItems(items, container, type) {
        //     container.innerHTML = ''; // 既存の内容をクリア
        //     items.forEach(item => {
        //         const itemDiv = document.createElement('div');
        //         itemDiv.classList.add('item-card');
        //         itemDiv.dataset.id = item.id; // hero_id または skill_id
        //         itemDiv.dataset.name = item.name;
        //         itemDiv.dataset.image = item.image_filename;
        //         itemDiv.dataset.type = type; // hero or skill

        //         const imagePath = (type === 'hero' ? HERO_IMAGE_BASE_PATH : SKILL_IMAGE_BASE_PATH) + item.image_filename;
        //         itemDiv.innerHTML = `<img src="${imagePath}" alt="${item.name}">`;
        //         itemDiv.addEventListener('click', () => addItemToSelection(item, type));
        //         container.appendChild(itemDiv);
        //     });
        // }
        // renderAvailableItems(initialHeroes, availableHeroesDiv, 'hero');
        // renderAvailableItems(initialSkills, availableSkillsDiv, 'skill');


        // --- クリックで追加するロジック ---
        // itemCardElement: クリックされた.item-card要素
        function addItemToSelection(itemCardElement) {
            const id = itemCardElement.dataset.id;
            const name = itemCardElement.dataset.name;
            const imageFilename = itemCardElement.dataset.image;
            const type = itemCardElement.dataset.type; // 'hero' or 'skill'

            const selectedArray = type === 'hero' ? selectedHeroes : selectedSkills;
            const selectedContainer = type === 'hero' ? selectedHeroesDiv : selectedSkillsDiv;
            const imageBasePath = type === 'hero' ? HERO_IMAGE_BASE_PATH : SKILL_IMAGE_BASE_PATH;

            selectedArray.push(id); // IDを配列に追加

            // 選択されたアイテムを画面に表示
            const selectedItemDiv = document.createElement('div');
            selectedItemDiv.classList.add('selected-item');
            selectedItemDiv.dataset.id = id;
            selectedItemDiv.dataset.type = type; // 削除時に必要
            selectedItemDiv.innerHTML = `
                <img src="${imageBasePath}${imageFilename}" alt="${name}">
                <button class="remove-btn">x</button>
            `;
            selectedContainer.appendChild(selectedItemDiv);

            // 削除ボタンのイベントリスナーを設定
            selectedItemDiv.querySelector('.remove-btn').addEventListener('click', (event) => {
                event.stopPropagation(); // 親要素のクリックイベントが発火しないように
                removeItemFromSelection(selectedItemDiv, id, type);
            });

            updateHiddenInputs(); // 隠しフィールドの値を更新
        }

        // --- 削除ロジック ---
        function removeItemFromSelection(elementToRemove, id, type) {
            const selectedArray = type === 'hero' ? selectedHeroes : selectedSkills;

            // 配列からIDを削除（最初の1つだけ削除する）
            const index = selectedArray.indexOf(id);
            if (index > -1) {
                selectedArray.splice(index, 1);
            }

            // 画面から要素を削除
            elementToRemove.remove();

            updateHiddenInputs(); // 隠しフィールドの値を更新
        }

        // --- フォーム送信前に隠しフィールドの値を更新するロジック ---
        function updateHiddenInputs() {
            // 配列をJSON文字列に変換して隠しフィールドにセット
            selectedHeroIdsInput.value = JSON.stringify(selectedHeroes);
            selectedSkillIdsInput.value = JSON.stringify(selectedSkills);
        }

        // --- イベントリスナーのセットアップ ---
        // 英傑アイテムのクリックイベントを設定
        document.querySelectorAll('#availableHeroes .item-card').forEach(itemCard => {
            itemCard.addEventListener('click', () => addItemToSelection(itemCard));
        });

        // スキルアイテムのクリックイベントを設定
        document.querySelectorAll('#availableSkills .item-card').forEach(itemCard => {
            itemCard.addEventListener('click', () => addItemToSelection(itemCard));
        });

        // フォームが送信される直前にも隠しフィールドを更新する（念のため）
        registrationForm.addEventListener('submit', function(event) {
            updateHiddenInputs();
        });

    </script>
</body>
</html>