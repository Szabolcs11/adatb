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

// Alapértelmezetten OCI kapcsolatot feltételezek
try {
    // Regisztrált felhasználók száma
    $stmtUsers = oci_parse($conn, "SELECT COUNT(*) AS COUNT FROM FELHASZNALO");
    oci_execute($stmtUsers);
    $rowUsers = oci_fetch_assoc($stmtUsers);
    $userCount = $rowUsers['COUNT'];

    // Szócikkek száma
    $stmtSzocikk = oci_parse($conn, "SELECT COUNT(*) AS COUNT FROM SZOCIKK");
    oci_execute($stmtSzocikk);
    $rowSzocikk = oci_fetch_assoc($stmtSzocikk);
    $szocikkCount = $rowSzocikk['COUNT'];

    // Témakörök száma
    $stmtTemakor = oci_parse($conn, "SELECT COUNT(*) AS COUNT FROM TEMAKOR");
    oci_execute($stmtTemakor);
    $rowTemakor = oci_fetch_assoc($stmtTemakor);
    $temakorCount = $rowTemakor['COUNT'];
} catch (Exception $e) {
    echo "Hiba: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <title>Admin felület</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin.css">
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
    <div class="container">
        <h1>Admin Statisztikák</h1>

        <div class="stats">
            <div class="stat-card">
                <h2>Felhasználók</h2>
                <p><?php echo $userCount; ?></p>
            </div>
            <div class="stat-card">
                <h2>Szócikkek</h2>
                <p><?php echo $szocikkCount; ?></p>
            </div>
            <div class="stat-card">
                <h2>Témakörök</h2>
                <p><?php echo $temakorCount; ?></p>
            </div>
        </div>

        <div class="buttons">
            <a href="new_szocikk.php" class="btn">Új Szócikk</a>
            <a href="modify_datas.php" class="btn">Meglévő adatok kezelése</a>
            <a href="lektor.php" class="btn">Lektorok kezelése</a>
            <a href="lektornyelv.php" class="btn">Lektornyelvek kezelése</a>
            <a href="hiba_modositas_torles.php" class="btn">Híbák módosítása törlése</a>
        </div>
    </div>
</body>

</html>