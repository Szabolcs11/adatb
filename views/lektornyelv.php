<?php
require_once '../db/connection.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: views/login_form.php");
    exit;
}
if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1) {
    $isAdmin = true;
} else {
    $isAdmin = false;
}

$_SESSION['admin'] = true;

if (isset($_POST['add'])) {
    $sql = "BEGIN 
              INSERT INTO LektorNyelv (lektor_id, nyelv, szint) 
              VALUES (:lektor_id, :nyelv, :szint); 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':lektor_id', $_POST['lektor_id']);
    oci_bind_by_name($stmt, ':nyelv', $_POST['nyelv']);
    oci_bind_by_name($stmt, ':szint', $_POST['szint']);
    oci_execute($stmt);
}

if (isset($_POST['update']) && $_SESSION['admin']) {
    $sql = "BEGIN 
              UPDATE LektorNyelv 
              SET lektor_id = :lektor_id, nyelv = :nyelv, szint = :szint 
              WHERE id = :id; 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_bind_by_name($stmt, ':lektor_id', $_POST['lektor_id']);
    oci_bind_by_name($stmt, ':nyelv', $_POST['nyelv']);
    oci_bind_by_name($stmt, ':szint', $_POST['szint']);
    oci_execute($stmt);
}

if (isset($_POST['delete']) && $_SESSION['admin']) {
    $sql = "BEGIN 
              DELETE FROM LektorNyelv WHERE id = :id; 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_execute($stmt);
}

$lektornyelvek = [];
$sql = "SELECT * FROM LektorNyelv";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $lektornyelvek[] = $row;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Lektor</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <h1>WikiClone</h1>
        <nav>
            <a href="./../index.php">Főoldal</a>
            <a href="./randomszocikk.php">Véletlenszerű szócikk</a>
            <a href="./hibajelentes.php">Hibajelentés</a>
            <?php if ($isAdmin) : ?>
                <a href="./admin.php">Admin</a>
            <?php endif; ?>
            <a href="./../actions/logout.php">Kijelentkezés</a>
        </nav>
    </header>
    <h2>LektorNyelv kezelése</h2>

    <form method="post">
        ID (módosításhoz/törléshez): <input type="number" name="id"><br>
        Lektor ID: <input type="number" name="lektor_id"><br>
        Nyelv: <input type="text" name="nyelv"><br>
        Szint: <input type="text" name="szint"><br>
        <button type="submit" name="add">Új Nyelv Hozzáadása</button>
        <?php if ($_SESSION['admin']): ?>
            <button type="submit" name="update">Módosítás</button>
            <button type="submit" name="delete">Törlés</button>
        <?php endif; ?>
    </form>

    <h3>Jelenlegi lektor-nyelvek:</h3>
    <ul>
        <?php foreach ($lektornyelvek as $lny): ?>
            <li>
                <?php echo "Lektor ID: " . $lny['LEKTOR_ID'] . ", Nyelv: " . $lny['NYELV'] . ", Szint: " . $lny['SZINT']; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>

</html>