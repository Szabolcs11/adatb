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
?>

<!DOCTYPE html>
<html>

<head>
    <title>Főoldal</title>
    <link rel="stylesheet" href="./views/style.css">
</head>

<body>
    <header>
        <h1>WikiClone</h1>
        <nav>
            <a href="#">Főoldal</a>
            <a href="./views/randomszocikk.php">Véletlenszerű szócikk</a>
            <a href="./views/hibajelentes.php">Hibajelentés</a>
            <?php if ($isAdmin) : ?>
                <a href="./views/admin.php">Admin</a>
            <?php endif; ?>
            <a href="./actions/logout.php">Kijelentkezés</a>
        </nav>
    </header>
    <main>
        <div class="sidebar">
            <h3>Témakörök</h3>
            <ul>
                <?php
                require_once './db/connection.php';
                $sql = "
                    SELECT t.ID, t.NEV, COUNT(st.SZOCIKK_ID) AS szocikkek_szama
                    FROM TEMAKOR t
                    LEFT JOIN SZOCIKKTEMAKOR st ON t.ID = st.TEMAKOR_ID
                    GROUP BY t.ID, t.NEV
                ";
                $stmt = oci_parse($conn, $sql);
                oci_execute($stmt);
                while ($row = oci_fetch_assoc($stmt)) {
                    if (isset($row['SZOCIKKEK_SZAMA'])) {
                        echo '<li><a href="index.php?temakor=' . htmlspecialchars($row['ID']) . '">' . htmlspecialchars($row['NEV']) . '</a>';
                        echo ' (' . htmlspecialchars($row['SZOCIKKEK_SZAMA']) . ' szócikk)</li>';
                    } else {
                        echo '<li><a href="index.php?temakor=' . htmlspecialchars($row['ID']) . '">' . htmlspecialchars($row['NEV']) . '</a>';
                        echo ' (0 szócikk)</li>';
                    }
                }
                oci_free_statement($stmt);
                ?>
            </ul>
        </div>

        <div class="content">
            <h2>Üdvözlünk, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Keresés szócikkek között..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                <button type="submit">Keresés</button>
            </form>

            <?php
            require_once './db/connection.php';

            if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
                // KERESÉS
                $search = trim($_GET['search']);

                $sql = "SELECT ID, CIM FROM SZOCIKK WHERE LOWER(CIM) LIKE :search";
                $stmt = oci_parse($conn, $sql);
                $searchParam = '%' . strtolower($search) . '%';
                oci_bind_by_name($stmt, ":search", $searchParam);
                oci_execute($stmt);

                echo "<h3>Keresési találatok:</h3>";
                echo "<ul>";
                while ($row = oci_fetch_assoc($stmt)) {
                    echo "<li><a href='./views/szocikk.php?id=" . htmlspecialchars($row['ID']) . "'>" . htmlspecialchars($row['CIM']) . "</a></li>";
                }
                echo "</ul>";

                oci_free_statement($stmt);
            } elseif (isset($_GET['temakor'])) {
                // TÉMAKÖR LISTA
                $temakorId = intval($_GET['temakor']); // Make sure it gets converted to an integer

                $sql = "SELECT SZOCIKK.ID, SZOCIKK.CIM 
                        FROM SZOCIKK
                        INNER JOIN SZOCIKKTEMAKOR ON SZOCIKK.ID = SZOCIKKTEMAKOR.SZOCIKK_ID
                        WHERE SZOCIKKTEMAKOR.TEMAKOR_ID = :temakor_id GROUP BY SZOCIKK.ID, SZOCIKK.CIM";

                $stmt = oci_parse($conn, $sql);
                oci_bind_by_name($stmt, ":temakor_id", $temakorId);

                if (!$stmt) {
                    $e = oci_error($conn);
                    echo "OCI error: " . htmlentities($e['message']);
                }

                // Execute the statement before fetching rows
                oci_execute($stmt);

                // Get the number of rows
                $nrows = oci_fetch_all($stmt, $results, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);

                if ($nrows > 0) {
                    echo "<h3>Szócikkek ebben a témakörben:</h3>";
                    echo "<ul>";
                    foreach ($results as $row) {
                        echo "<li><a href='./views/szocikk.php?id=" . htmlspecialchars($row['ID']) . "'>" . htmlspecialchars($row['CIM']) . "</a></li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>Nincs találat ebben a témakörben.</p>";
                }

                oci_free_statement($stmt);
            }
            ?>

        </div>
    </main>
</body>

</html>

<?php
oci_close($conn);
?>