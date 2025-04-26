<?php
require_once '../db/connection.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT 
    s.ID,
    s.CIM,
    TO_CHAR(s.LETREHOZAS_DATUM, 'YYYY-MM-DD') AS LETREHOZAS_DATUM,
    TO_CHAR(s.MODOSITAS_DATUM, 'YYYY-MM-DD') AS MODOSITAS_DATUM,
    s.STATUSZ,
    s.SZERZO_ID,
    f.NEV AS SZERZO_NEV,
    s.TARTALOM,

    -- Kulcsszavak
    (SELECT LISTAGG(k.SZO, ', ') WITHIN GROUP (ORDER BY k.SZO)
     FROM SZOCIKKKULCSSZO sk
     JOIN KULCSSZO k ON sk.KULCSSZO_ID = k.ID
     WHERE sk.SZOCIKK_ID = s.ID
    ) AS KULCSSZAVAK,

    -- Kategóriák
    (SELECT LISTAGG(kat.NEV, ', ') WITHIN GROUP (ORDER BY kat.NEV)
     FROM SZOCIKKKATEGORIA sk
     JOIN KATEGORIA kat ON sk.KATEGORIA_ID = kat.ID
     WHERE sk.SZOCIKK_ID = s.ID
    ) AS KATEGORIAK,

    -- Témakörök
    (SELECT LISTAGG(t.ID || ':' || t.NEV, ', ') WITHIN GROUP (ORDER BY t.NEV)
     FROM SZOCIKKTEMAKOR st
     JOIN TEMAKOR t ON st.TEMAKOR_ID = t.ID
     WHERE st.SZOCIKK_ID = s.ID
    ) AS TEMAKOROK

FROM SZOCIKK s
LEFT JOIN FELHASZNALO f ON s.SZERZO_ID = f.ID
WHERE s.ID = :id
";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id", $id);
oci_execute($stmt);
$szocikk = oci_fetch_assoc($stmt);

oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Szócikk megjelenítése</title>
    <link rel="stylesheet" href="./style.css">
    <style>
        .container {
            max-width: 800px;
            margin: auto;
        }

        .meta {
            color: gray;
            font-size: 0.9em;
        }
    </style>
</head>

<body>
    <header>
        <h1>WikiClone</h1>
        <nav>
            <a href="./../index.php">Főoldal</a>
            <a href="./randomszocikk.php">Véletlenszerű szócikk</a>
            <a href="./../views/register_form.php">Regisztráció</a>
        </nav>
    </header>
    <!-- Debuggolás segédlet -->
    <!-- <p>
        <?php
        echo "<pre>";
        echo json_encode($szocikk, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        echo "</pre>";
        ?>
    </p> -->
    <main>
        <div class="container">
            <?php if ($szocikk): ?>
                <h1><?= htmlspecialchars($szocikk['CIM'], ENT_QUOTES, 'UTF-8') ?></h1>
                <div class="meta">
                    <p>Írta: <?= htmlspecialchars($szocikk['SZERZO_NEV'] ?? 'Ismeretlen', ENT_QUOTES, 'UTF-8') ?></p>
                    <p>Létrehozva: <?= htmlspecialchars($szocikk['LETREHOZAS_DATUM'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p>Utoljára módosítva: <?= htmlspecialchars($szocikk['MODOSITAS_DATUM'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p>Státusz: <?= htmlspecialchars($szocikk['STATUSZ'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Kulcsszavak:</strong> <?= htmlspecialchars($szocikk['KULCSSZAVAK'] ?? 'Nincs', ENT_QUOTES, 'UTF-8') ?></p>
                    <p><strong>Kategóriák:</strong> <?= htmlspecialchars($szocikk['KATEGORIAK'] ?? 'Nincs', ENT_QUOTES, 'UTF-8') ?></p>
                    <?php
                    $temakorData = [];
                    if (!empty($szocikk['TEMAKOROK'])) {
                        $temakorPairs = explode(',', $szocikk['TEMAKOROK']);
                        foreach ($temakorPairs as $pair) {
                            list($id, $name) = explode(':', $pair);
                            $temakorData[] = ['id' => (int)$id, 'name' => $name];
                        }
                    }

                    $szocikk['TEMAKOROK'] = $temakorData;
                    ?>
                    <p><strong>Témakörök:</strong>
                        <?php if (!empty($szocikk['TEMAKOROK'])): ?>
                            <?php foreach ($szocikk['TEMAKOROK'] as $temakor): ?>
                                <a href="./../index.php?temakor=<?= $temakor['id'] ?>" class="temakor-link"><?= htmlspecialchars($temakor['name'], ENT_QUOTES, 'UTF-8') ?></a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            Nincs
                        <?php endif; ?>
                    </p>
                </div>
                <hr>
                <div class="content">
                    <p><?= nl2br(htmlspecialchars($szocikk['TARTALOM'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php else: ?>
                <p>Nem található ilyen szócikk.</p>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>