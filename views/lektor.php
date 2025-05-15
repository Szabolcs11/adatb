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

$_SESSION['admin'] = true;

if (isset($_POST['add'])) {
    $sql = "BEGIN 
              INSERT INTO Lektor (tudomanyos_fokozat, intezet, szakterulet) 
              VALUES (:tudomanyos_fokozat, :intezet, :szakterulet); 
            END;";
    $stmt = oci_parse($conn, $sql);
    // oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_bind_by_name($stmt, ':tudomanyos_fokozat', $_POST['tudomanyos_fokozat']);
    oci_bind_by_name($stmt, ':intezet', $_POST['intezet']);
    oci_bind_by_name($stmt, ':szakterulet', $_POST['szakterulet']);
    oci_execute($stmt);
}

if (isset($_POST['update']) && $_SESSION['admin']) {
    $sql = "BEGIN 
              UPDATE Lektor 
              SET tudomanyos_fokozat = :tudomanyos_fokozat, intezet = :intezet, szakterulet = :szakterulet 
              WHERE id = :id; 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_bind_by_name($stmt, ':tudomanyos_fokozat', $_POST['tudomanyos_fokozat']);
    oci_bind_by_name($stmt, ':intezet', $_POST['intezet']);
    oci_bind_by_name($stmt, ':szakterulet', $_POST['szakterulet']);
    oci_execute($stmt);
}

if (isset($_POST['delete']) && $_SESSION['admin']) {
    $sql = "BEGIN 
              DELETE FROM Lektor WHERE id = :id; 
            END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $_POST['id']);
    oci_execute($stmt);
}

$lektorok = [];
$sql = "SELECT * FROM Lektor";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
while ($row = oci_fetch_assoc($stmt)) {
    $lektorok[] = $row;
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
    <h2>Lektor kezelése</h2>

    <form method="post">
        ID (Felhasználó ID): <input type="number" name="id"><br>
        Tudományos fokozat: <input type="text" name="tudomanyos_fokozat"><br>
        Intézet: <input type="text" name="intezet"><br>
        Szakterület: <input type="text" name="szakterulet"><br>
        <button type="submit" name="add">Új Lektor Hozzáadása</button>
        <?php if ($_SESSION['admin']): ?>
            <button type="submit" name="update">Módosítás</button>
            <button type="submit" name="delete">Törlés</button>
        <?php endif; ?>
    </form>

    <h3>Jelenlegi lektorok:</h3>
    <ul>
        <?php foreach ($lektorok as $lektor): ?>
            <li>
                <?php echo "ID: " . $lektor['ID'] . ", Fokozat: " . $lektor['TUDOMANYOS_FOKOZAT'] . ", Intézet: " . $lektor['INTEZET'] . ", Szakterület: " . $lektor['SZAKTERULET']; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <h3>Statisztikák:</h3>

    <h4>Legalább 2 PhD fokozatú és középfokkal rendelkező lektorok száma intézetenként</h4>
    <ul>
        <?php
        $sql = "SELECT lektorok.Intezet, COUNT(*) AS phd_lektorok_szama
FROM (
    SELECT DISTINCT l.id, l.Intezet
    FROM Lektor l
    JOIN Lektornyelv ln ON l.id = ln.Lektor_Id
    WHERE l.tudomanyos_fokozat = 'PhD'
      AND ln.Szint = 'Kozepfok'
) lektorok
GROUP BY lektorok.Intezet
HAVING COUNT(*) >= 2
ORDER BY phd_lektorok_szama DESC";
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            echo "<li>Intézet: {$row['INTEZET']} – Lektorok: {$row['PHD_LEKTOROK_SZAMA']}</li>";
        }
        ?>
    </ul>

    <h4>Fizikai szakterületű lektorok nyelvi szintjeinek száma</h4>
    <ul>
        <?php
        $sql = 'SELECT ln.Szint, COUNT(*) AS nyelvek_szama
        FROM Lektornyelv ln
        JOIN (
            SELECT id FROM Lektor WHERE SZAKTERULET = \'Fizika\'
        ) fizikai ON ln.Lektor_Id = fizikai.id
        GROUP BY ln.Szint
        ORDER BY nyelvek_szama DESC';

        $stid = oci_parse($conn, $sql);
        oci_execute($stid);

        while ($row = oci_fetch_assoc($stid)) {
            echo $row['SZINT'] . ": " . $row['NYELVEK_SZAMA'] . "<br>";
        }
        ?>
    </ul>

    <h4>3. Lektorok, akik legalább 2 nyelvet beszélnek</h4>
    <ul>
        <?php
        $sql = "BEGIN Lektorok_Tobb_Nyelven_proc(:cursor); END;";
        $stid = oci_parse($conn, $sql);

        $cursor = oci_new_cursor($conn);
        oci_bind_by_name($stid, ':cursor', $cursor, -1, OCI_B_CURSOR);

        oci_execute($stid);
        oci_execute($cursor);

        while ($row = oci_fetch_assoc($cursor)) {
            echo "<li>ID: {$row['ID']} – Fokozat: {$row['TUDOMANYOS_FOKOZAT']} – Nyelvek: {$row['NYELVDB']}</li>";
        }

        oci_free_statement($stid);
        oci_free_statement($cursor);
        ?>
    </ul>

    <h4>4. Lektorok által jelentett hibák száma</h4>
    <ul>
        <?php
        $sql = "SELECT l.id AS lektor_id, COUNT(h.id) AS hiba_db
        FROM Lektor l
        LEFT JOIN Hiba h ON l.id = h.felhasznalo_id
        GROUP BY l.id";
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        while ($row = oci_fetch_assoc($stmt)) {
            echo "<li>Lektor ID: {$row['LEKTOR_ID']} – Hibák száma: {$row['HIBA_DB']}</li>";
        }
        ?>
    </ul>


</body>

</html>