<?php
session_start();

define('ADMIN_PASS_HASH', password_hash('admin123', PASSWORD_DEFAULT)); 
// ⚠️ IMPORTANT : remplace une fois par ton hash réel (voir plus bas)

if (!file_exists("links.json")) {
    file_put_contents("links.json", json_encode([]));
}

$uploadBase = "uploads/";

if (!is_dir($uploadBase)) {
    mkdir($uploadBase, 0755, true);
}

/* ===================== LOGIN ===================== */
if (isset($_POST['login_password'])) {
    if (password_verify($_POST['login_password'], ADMIN_PASS_HASH)) {
        $_SESSION['admin'] = true;
        session_regenerate_id(true);
    } else {
        $error = "Mot de passe incorrect";
    }
    header("Location: index.php");
    exit;
}

/* ===================== LOGOUT ===================== */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$isAdmin = $_SESSION['admin'] ?? false;

/* ===================== LOAD DATA ===================== */
$links = json_decode(file_get_contents("links.json"), true);

/* ===================== ADD FILE ===================== */
if ($isAdmin && isset($_POST['action']) && $_POST['action'] === "upload") {

    $category = $_POST['category'];

    if (!empty($_FILES['file']['name'])) {

        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));

        $allowed = ["pdf","docx","xlsx","zip"];

        if (!in_array($ext, $allowed)) {
            die("Format non autorisé");
        }

        $dir = $uploadBase . $category . "/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = time() . "_" . basename($_FILES['file']['name']);

        move_uploaded_file($_FILES['file']['tmp_name'], $dir . $filename);

        $links[$category][] = [
            "name" => $_FILES['file']['name'],
            "url" => $dir . $filename,
            "type" => "file"
        ];

        file_put_contents("links.json", json_encode($links, JSON_PRETTY_PRINT));

        header("Location: index.php");
        exit;
    }
}

/* ===================== ADD LINK ===================== */
if ($isAdmin && isset($_POST['action']) && $_POST['action'] === "add") {

    $category = $_POST['category'];

    $links[$category][] = [
        "name" => $_POST['name'],
        "url" => $_POST['url'],
        "type" => "link"
    ];

    file_put_contents("links.json", json_encode($links, JSON_PRETTY_PRINT));

    header("Location: index.php");
    exit;
}

/* ===================== DELETE ===================== */
if ($isAdmin && isset($_POST['action']) && $_POST['action'] === "delete") {

    $category = $_POST['category'];
    $index = (int)$_POST['index'];

    unset($links[$category][$index]);
    $links[$category] = array_values($links[$category]);

    file_put_contents("links.json", json_encode($links, JSON_PRETTY_PRINT));

    header("Location: index.php");
    exit;
}

/* ===================== CATEGORIES ===================== */
$categories = [
    "Béton armé" => "beton",
    "Bois" => "bois",
    "Métal" => "metal",
    "Géotechnique" => "geo",
    "Projets BA" => "projets_ba",
    "Logiciels" => "logiciels"
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Docs</title>
<style>
body{font-family:Arial;background:#f4f4f4;padding:20px}
.container{background:white;padding:20px;border-radius:10px;max-width:900px;margin:auto}
h2{border-bottom:2px solid #3498db}
.cat{margin-top:20px;padding:10px;background:#eee;border-radius:5px}
a{color:#2980b9;text-decoration:none}
form{margin-top:10px}
input,button{padding:5px;margin:3px}
.admin{background:#ddd;padding:10px;margin-bottom:10px}
</style>
</head>
<body>

<div class="container">

<h1>📁 Mes documents</h1>

<div class="admin">

<?php if (!$isAdmin): ?>
<form method="post">
<input type="password" name="login_password" placeholder="Admin password">
<button>Login</button>
</form>
<?php else: ?>
<a href="?logout=1">Logout</a>
<?php endif; ?>

</div>

<?php foreach ($categories as $name => $key): ?>
<div class="cat">

<h2><?= $name ?></h2>

<?php
$items = $links[$key] ?? [];
foreach ($items as $i => $item):
?>
<p>
<a href="<?= htmlspecialchars($item['url']) ?>" target="_blank">
<?= htmlspecialchars($item['name']) ?>
</a>

<?php if ($isAdmin): ?>
<form method="post" style="display:inline">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="category" value="<?= $key ?>">
<input type="hidden" name="index" value="<?= $i ?>">
<button>🗑</button>
</form>
<?php endif; ?>

</p>
<?php endforeach; ?>

<?php if ($isAdmin): ?>

<!-- UPLOAD FILE -->
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload">
<input type="hidden" name="category" value="<?= $key ?>">
<input type="file" name="file" required>
<button>Upload</button>
</form>

<!-- ADD LINK -->
<form method="post">
<input type="hidden" name="action" value="add">
<input type="hidden" name="category" value="<?= $key ?>">
<input type="text" name="name" placeholder="Nom">
<input type="url" name="url" placeholder="Lien">
<button>Ajouter lien</button>
</form>

<?php endif; ?>

</div>
<?php endforeach; ?>

</div>

</body>
</html>
