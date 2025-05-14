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

$felhasznalo_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$statusz = "Nyitott";

$sql = "SELECT ID, CIM FROM SZOCIKK ORDER BY CIM";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $szocikk_id = isset($_POST['szocikk_id']) ? (int)$_POST['szocikk_id'] : 0;
    $szoveg = isset($_POST['szoveg']) ? trim($_POST['szoveg']) : '';

    if ($szocikk_id && $felhasznalo_id && !empty($szoveg)) {
        $sql = "BEGIN BEJELENT_HIBA(:szoveg, :statusz, :felhasznalo_id, :szocikk_id); END;";
        // $sql = "INSERT INTO HIBA (ID, SZOVEG, STATUSZ, FELHASZNALO_ID, SZOCIKK_ID)
        //         VALUES (HIBA_SEQ.NEXTVAL, :szoveg, :statusz, :felhasznalo_id, :szocikk_id)";

        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ":szoveg", $szoveg);
        oci_bind_by_name($stmt, ":felhasznalo_id", $felhasznalo_id);
        oci_bind_by_name($stmt, ":szocikk_id", $szocikk_id);
        oci_bind_by_name($stmt, ":statusz", $statusz);

        if (oci_execute($stmt)) {
            echo "<p>Hiba sikeresen be lett jelentve!</p>";
            header("Location: hibajelentes.php?success=created");
        } else {
            echo "<p>Hiba történt a bejelentés során!</p>";
        }

        oci_free_statement($stmt);
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Szócikk Hiba Bejelentése</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
        }
    </style>
    <link rel="stylesheet" href="./style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <main>
        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <?php
                    switch ($_GET['success']) {
                        case 'created':
                            echo 'Sikeresen be lett jelentve a hiba!';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>
            <h1>Hiba bejelentése</h1>
            <form action="hibajelentes.php" method="POST">
                <label for="szocikk_id">Szócikk:</label><br>
                <select id="szocikk_id" name="szocikk_id" required>
                    <option value="">-- Válasszon egy szócikket --</option>
                    <?php
                    while ($szocikk = oci_fetch_assoc($stmt)): ?>
                        <option value="<?= $szocikk['ID'] ?>"><?= htmlspecialchars($szocikk['CIM']) ?></option>
                    <?php endwhile; ?>
                </select><br><br>

                <label for="szoveg">Hiba leírása:</label><br>
                <textarea id="szoveg" name="szoveg" rows="5" required></textarea><br><br>

                <button type="submit">Bejelentés</button>
            </form>
        </div>
    </main>
</body>

</html>

<?php
oci_free_statement($stmt); // Don't free the statement before the loop
oci_close($conn); // Close the connection at the end
?>