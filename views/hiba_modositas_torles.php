<?php
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
require_once '../db/connection.php';


if (isset($_POST['update']) && $_SESSION['admin']) {
    $sql = "BEGIN 
              UPDATE Hiba 
              SET szoveg = :szoveg, statusz = :statusz, felhasznalo_id = :felhasznalo_id, szocikk_id = :szocikk_id 
              WHERE id = :id; 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':szoveg', $_POST['szoveg']);
    oci_bind_by_name($stmt, ':statusz', $_POST['statusz']);
    oci_bind_by_name($stmt, ':felhasznalo_id', $_POST['felhasznalo_id']);
    oci_bind_by_name($stmt, ':szocikk_id', $_POST['szocikk_id']);
    oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_execute($stmt);
}

if (isset($_POST['delete']) && $_SESSION['admin']) {
    $sql = "BEGIN 
              DELETE FROM Hiba WHERE id = :id; 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_execute($stmt);
}

$hibak = [];
$sql = "SELECT * FROM Hiba";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    if (is_object($row['SZOVEG']) && $row['SZOVEG'] instanceof OCILob) {
        $row['SZOVEG'] = $row['SZOVEG']->read($row['SZOVEG']->size());
    }
    $hibak[] = $row;
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Hibakezelés</title>
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
    <h2>Hiba kezelése</h2>

    <form method="post">
        ID (módosításhoz/törléshez): <input type="number" name="id"><br>
        Szöveg: <input type="text" name="szoveg"><br>
        Státusz: <input type="text" name="statusz"><br>
        Felhasználó ID: <input type="number" name="felhasznalo_id"><br>
        Szócikk ID: <input type="number" name="szocikk_id"><br>
        <?php if ($_SESSION['admin']): ?>
            <button type="submit" name="update">Módosítás</button>
            <button type="submit" name="delete">Törlés</button>
        <?php endif; ?>
    </form>

    <h3>Jelenlegi hibák:</h3>
    <ul>
        <?php foreach ($hibak as $hiba): ?>
            <li>
                <?php echo "ID: " . $hiba['ID'] . ", Szöveg: " . $hiba['SZOVEG'] . ", Státusz: " . $hiba['STATUSZ'] . ", Felhasználó ID: " . $hiba['FELHASZNALO_ID'] . ", Szócikk ID: " . $hiba['SZOCIKK_ID']; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>

</html>