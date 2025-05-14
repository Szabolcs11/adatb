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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $cim = $_POST['cim'];
        $tartalom = $_POST['tartalom'];
        $kep = null;
        $nyelv = $_POST['nyelv'];
        $statusz = 'Lektorálásra vár';
        $szerzo_id = $_SESSION['user_id'];

        if (isset($_SESSION) && $_SESSION['admin'] == 1) {
            $statusz = $_POST['statusz'];
            $szerzo_id = $_POST['szerzo_id'];
        }

        if ($_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $foto_temp = $_FILES['foto']['tmp_name'];
            $foto_type = $_FILES['foto']['type'];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (!in_array($foto_type, $allowed_types)) {
                $kep = null;
            } else {
                $kep = file_get_contents($foto_temp);
            }
        }

        $sql = "SELECT SZOCIKK_SEQ.NEXTVAL AS NEXT_ID FROM DUAL";
        $stmt = oci_parse($conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        $next_id = $row['NEXT_ID'];

        $sql = "INSERT INTO SZOCIKK (ID, CIM, LETREHOZAS_DATUM, MODOSITAS_DATUM, NYELV, STATUSZ, SZERZO_ID, TARTALOM)
                VALUES (:id, :cim, SYSDATE, SYSDATE, :nyelv, :statusz, :szerzo_id, :tartalom)";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':id', $next_id);
        oci_bind_by_name($stmt, ':cim', $cim);
        oci_bind_by_name($stmt, ':nyelv', $nyelv);
        oci_bind_by_name($stmt, ':statusz', $statusz);
        oci_bind_by_name($stmt, ':szerzo_id', $szerzo_id);
        oci_bind_by_name($stmt, ':tartalom', $tartalom);

        if (oci_execute($stmt)) {
            $szocikk_id = $next_id; // Store the ID for later use

            if ($kep != null) {
                $blob = oci_new_descriptor($conn, OCI_D_LOB);
                $sql = "UPDATE SZOCIKK SET KEP_BINARIS = EMPTY_BLOB() WHERE ID = :id RETURNING KEP_BINARIS INTO :kep";
                $stmt = oci_parse($conn, $sql);

                oci_bind_by_name($stmt, ':id', $next_id);
                oci_bind_by_name($stmt, ':kep', $blob, -1, OCI_B_BLOB);

                if (oci_execute($stmt, OCI_DEFAULT)) {
                    if ($blob->save($kep)) {
                        oci_commit($conn);
                        $blob->free();
                    }
                }
            }

            // Handle categories
            if (isset($_POST['kategoriak'])) {
                foreach ($_POST['kategoriak'] as $kategoria_id) {
                    $sql = "INSERT INTO SZOCIKKKATEGORIA (SZOCIKK_ID, KATEGORIA_ID) VALUES (:szocikk_id, :kategoria_id)";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':szocikk_id', $szocikk_id);
                    oci_bind_by_name($stmt, ':kategoria_id', $kategoria_id);
                    oci_execute($stmt);
                }
            }

            // Handle topics
            if (isset($_POST['temakorok'])) {
                foreach ($_POST['temakorok'] as $temakor_id) {
                    $sql = "INSERT INTO SZOCIKKTEMAKOR (SZOCIKK_ID, TEMAKOR_ID) VALUES (:szocikk_id, :temakor_id)";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':szocikk_id', $szocikk_id);
                    oci_bind_by_name($stmt, ':temakor_id', $temakor_id);
                    oci_execute($stmt);
                }
            }

            // Handle keywords
            if (isset($_POST['kulcsszavak'])) {
                foreach ($_POST['kulcsszavak'] as $kulcsszo_id) {
                    $sql = "INSERT INTO SZOCIKKKULCSSZO (SZOCIKK_ID, KULCSSZO_ID) VALUES (:szocikk_id, :kulcsszo_id)";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':szocikk_id', $szocikk_id);
                    oci_bind_by_name($stmt, ':kulcsszo_id', $kulcsszo_id);
                    oci_execute($stmt);
                }
            }

            header("Location: new_szocikk.php?success=created");
            exit();
        } else {
            $error = oci_error($stmt);
        }
    } elseif (isset($_POST['update'])) {
        // Update existing SZOCIKK
        $id = $_POST['id'];
        $cim = $_POST['cim'];
        $kep = null;
        $nyelv = $_POST['nyelv'];
        $tartalom = $_POST['tartalom'];
        $szerzo_id = $_SESSION['user_id'];
        // $statusz = 'Lektorálásra vár';
        $statusz = $_POST['statusz'];

        if (isset($_SESSION) && $_SESSION['admin'] == 1) {
            $statusz = $_POST['statusz'];
            $szerzo_id = $_POST['szerzo_id'];
        }

        $stmt = null;

        if ($_FILES['foto']['error'] == UPLOAD_ERR_OK) {
            $foto_temp = $_FILES['foto']['tmp_name'];
            $foto_type = $_FILES['foto']['type'];

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (!in_array($foto_type, $allowed_types)) {
                $kep = null;
            } else {
                $kep = file_get_contents($foto_temp);
            }
        }

        $sql = "UPDATE SZOCIKK SET
                CIM = :cim,
                NYELV = :nyelv,
                SZERZO_ID = :szerzo_id,
                TARTALOM = :tartalom,
                STATUSZ = :statusz
                WHERE ID = :id";
        $stmt = oci_parse($conn, $sql);
        oci_bind_by_name($stmt, ':id', $id);
        oci_bind_by_name($stmt, ':cim', $cim);
        oci_bind_by_name($stmt, ':nyelv', $nyelv);
        oci_bind_by_name($stmt, ':szerzo_id', $szerzo_id);
        oci_bind_by_name($stmt, ':tartalom', $tartalom);
        oci_bind_by_name($stmt, ':statusz', $statusz);

        if (oci_execute($stmt)) {
            if ($kep != null) {
                $blob = oci_new_descriptor($conn, OCI_D_LOB);
                $sql = "UPDATE SZOCIKK SET KEP_BINARIS = EMPTY_BLOB() WHERE ID = :id RETURNING KEP_BINARIS INTO :kep";
                $stmt = oci_parse($conn, $sql);

                oci_bind_by_name($stmt, ':id', $id);
                oci_bind_by_name($stmt, ':kep', $blob, -1, OCI_B_BLOB);

                if (oci_execute($stmt, OCI_DEFAULT)) {
                    if ($blob->save($kep)) {
                        oci_commit($conn);
                        $blob->free();
                    }
                }
            }

            // Delete existing relationships
            $sql = "DELETE FROM SZOCIKKKATEGORIA WHERE SZOCIKK_ID = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':id', $id);
            oci_execute($stmt);

            $sql = "DELETE FROM SZOCIKKTEMAKOR WHERE SZOCIKK_ID = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':id', $id);
            oci_execute($stmt);

            $sql = "DELETE FROM SZOCIKKKULCSSZO WHERE SZOCIKK_ID = :id";
            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':id', $id);
            oci_execute($stmt);

            // Add new relationships
            if (isset($_POST['kategoriak'])) {
                foreach ($_POST['kategoriak'] as $kategoria_id) {
                    $sql = "INSERT INTO SZOCIKKKATEGORIA (SZOCIKK_ID, KATEGORIA_ID) VALUES (:id, :kategoria_id)";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':id', $id);
                    oci_bind_by_name($stmt, ':kategoria_id', $kategoria_id);
                    oci_execute($stmt);
                }
            }

            if (isset($_POST['temakorok'])) {
                foreach ($_POST['temakorok'] as $temakor_id) {
                    $sql = "INSERT INTO SZOCIKKTEMAKOR (SZOCIKK_ID, TEMAKOR_ID) VALUES (:id, :temakor_id)";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':id', $id);
                    oci_bind_by_name($stmt, ':temakor_id', $temakor_id);
                    oci_execute($stmt);
                }
            }

            if (isset($_POST['kulcsszavak'])) {
                foreach ($_POST['kulcsszavak'] as $kulcsszo_id) {
                    $sql = "INSERT INTO SZOCIKKKULCSSZO (SZOCIKK_ID, KULCSSZO_ID) VALUES (:id, :kulcsszo_id)";
                    $stmt = oci_parse($conn, $sql);
                    oci_bind_by_name($stmt, ':id', $id);
                    oci_bind_by_name($stmt, ':kulcsszo_id', $kulcsszo_id);
                    oci_execute($stmt);
                }
            }

            header("Location: new_szocikk.php?success=updated");
            exit();
        } else {
            $error = oci_error($stmt);
        }
    }
} elseif (isset($_GET['delete'])) {
    // Delete SZOCIKK
    $id = $_GET['delete'];

    // First delete relationships
    $sql = "DELETE FROM SZOCIKKKATEGORIA WHERE SZOCIKK_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);

    $sql = "DELETE FROM SZOCIKKTEMAKOR WHERE SZOCIKK_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);

    $sql = "DELETE FROM SZOCIKKKULCSSZO WHERE SZOCIKK_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);

    // Then delete the article
    $sql = "BEGIN proc_torol_szocikk(:id); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);

    if (oci_execute($stmt)) {
        header("Location: new_szocikk.php?success=deleted");
        exit();
    } else {
        $error = oci_error($stmt);
    }
}

// Fetch all SZOCIKK for listing
$sql = "SELECT s.*, 
       (SELECT LISTAGG(k.NEV, ', ') WITHIN GROUP (ORDER BY k.NEV) 
        FROM KATEGORIA k JOIN SZOCIKKKATEGORIA sk ON k.ID = sk.KATEGORIA_ID 
        WHERE sk.SZOCIKK_ID = s.ID) AS KATEGORIAK,
       (SELECT LISTAGG(t.NEV, ', ') WITHIN GROUP (ORDER BY t.NEV) 
        FROM TEMAKOR t JOIN SZOCIKKTEMAKOR st ON t.ID = st.TEMAKOR_ID
        WHERE st.SZOCIKK_ID = s.ID) AS TEMAKOROK,
       (SELECT LISTAGG(ks.SZO, ', ') WITHIN GROUP (ORDER BY ks.SZO) 
        FROM KULCSSZO ks JOIN SZOCIKKKULCSSZO sk ON ks.ID = sk.KULCSSZO_ID 
        WHERE sk.SZOCIKK_ID = s.ID) AS KULCSSZAVAK
       FROM SZOCIKK s
       ORDER BY s.LETREHOZAS_DATUM DESC";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$szocikkek = [];
while ($row = oci_fetch_assoc($stmt)) {
    $szocikkek[] = $row;
}

// Fetch all categories, topics, and keywords for forms
$sql = "SELECT * FROM KATEGORIA ORDER BY NEV";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$kategoriak = [];
while ($row = oci_fetch_assoc($stmt)) {
    $kategoriak[] = $row;
}

$sql = "SELECT * FROM TEMAKOR ORDER BY NEV";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$temakorok = [];
while ($row = oci_fetch_assoc($stmt)) {
    $temakorok[] = $row;
}

$sql = "SELECT * FROM KULCSSZO ORDER BY SZO";
$stmt = oci_parse($conn, $sql);
oci_execute($stmt);
$kulcsszavak = [];
while ($row = oci_fetch_assoc($stmt)) {
    $kulcsszavak[] = $row;
}

// Fetch article to edit if requested
$edit_szocikk = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    $sql = "SELECT * FROM SZOCIKK WHERE ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $edit_szocikk = oci_fetch_assoc($stmt);

    // Fetch related categories
    $sql = "SELECT KATEGORIA_ID FROM SZOCIKKKATEGORIA WHERE SZOCIKK_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $edit_kategoriak = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $edit_kategoriak[] = $row['KATEGORIA_ID'];
    }

    // Fetch related topics
    $sql = "SELECT TEMAKOR_ID FROM SZOCIKKTEMAKOR WHERE SZOCIKK_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $edit_temakorok = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $edit_temakorok[] = $row['TEMAKOR_ID'];
    }

    // Fetch related keywords
    $sql = "SELECT KULCSSZO_ID FROM SZOCIKKKULCSSZO WHERE SZOCIKK_ID = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $id);
    oci_execute($stmt);
    $edit_kulcsszavak = [];
    while ($row = oci_fetch_assoc($stmt)) {
        $edit_kulcsszavak[] = $row['KULCSSZO_ID'];
    }
}

function fetchAll($conn, $query)
{
    $stid = oci_parse($conn, $query);
    oci_execute($stid);
    $result = [];
    while ($row = oci_fetch_assoc($stid)) {
        $result[] = $row;
    }
    return $result;
}

$ajanlottlektorok = fetchAll($conn, "SELECT sz.ID, (SELECT l.ID
                                     FROM LEKTORNYELV lny
                                     INNER JOIN LEKTOR l ON lny.LEKTOR_ID = l.ID
                                     WHERE lny.NYELV = sz.NYELV
                                     FETCH FIRST 1 ROWS ONLY) AS L_ID
                                     FROM SZOCIKK sz");

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szócikk kezelés</title>
    <link rel="stylesheet" href="./style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .szocikk-card {
            margin-bottom: 15px;
            border-left: 4px solid #0d6efd;
        }

        .lektoralas-card {
            margin-bottom: 15px;
            border-left: 4px solid #F6AB28;
        }

        .szocikk-actions {
            opacity: 0;
            transition: opacity 0.3s;
        }

        .szocikk-card:hover .szocikk-actions {
            opacity: 1;
        }

        .lektoralas-card:hover .szocikk-actions {
            opacity: 1;
        }
    </style>
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
    <div class="container mt-4">
        <h1 class="mb-4">Szócikk kezelés</h1>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">Hiba történt: <?= htmlspecialchars($error['message']) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'created':
                        echo 'SZOCIKK sikeresen létrehozva!';
                        break;
                    case 'updated':
                        echo 'SZOCIKK sikeresen frissítve!';
                        break;
                    case 'deleted':
                        echo 'SZOCIKK sikeresen törölve!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?= isset($edit_szocikk) ? 'SZOCIKK Szerkesztése' : 'Új SZOCIKK Létrehozása' ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <?php if (isset($edit_szocikk)): ?>
                    <input type="hidden" name="id" value="<?= htmlspecialchars($edit_szocikk['ID']) ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="cim" class="form-label">Cím</label>
                    <input type="text" class="form-control" id="cim" name="cim" required
                        value="<?= isset($edit_szocikk) ? htmlspecialchars($edit_szocikk['CIM']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label for="nyelv" class="form-label">Nyelv</label>
                    <input type="text" class="form-control" id="nyelv" name="nyelv" required
                        value="<?= isset($edit_szocikk) ? htmlspecialchars($edit_szocikk['NYELV']) : '' ?>">
                </div>

                <div class="mb-3">
                    <label for="tartalom" class="form-label">Tartalom</label>
                    <textarea class="form-control" id="tartalom" name="tartalom" rows="5" required><?= isset($edit_szocikk) ? htmlspecialchars($edit_szocikk['TARTALOM']) : '' ?></textarea>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="szerzo_id" class="form-label">Szerző ID</label>
                        <?php if (isset($_SESSION) && $_SESSION['admin'] == 1): ?>
                            <input type="number" class="form-control" id="szerzo_id" name="szerzo_id" required
                                value="<?= isset($edit_szocikk) ? htmlspecialchars($edit_szocikk['SZERZO_ID']) : '' ?>">
                        <?php endif; ?>
                    </div>
                    <?php if (isset($_SESSION) && $_SESSION['admin'] == 1): ?>
                        <div class="col-md-6">
                            <label for="statusz" class="form-label">Státusz</label>
                            <select class="form-select" id="statusz" name="statusz" required>
                                <option value="Piszkozat" <?= isset($edit_szocikk) && $edit_szocikk['STATUSZ'] == 'Piszkozat' ? 'selected' : '' ?>>Piszkozat</option>
                                <option value="Lektorálásra vár" <?= isset($edit_szocikk) && $edit_szocikk['STATUSZ'] == 'Lektorálásra vár' ? 'selected' : '' ?>>Lektorálásra vár</option>
                                <option value="Publikált" <?= isset($edit_szocikk) && $edit_szocikk['STATUSZ'] == 'Publikált' ? 'selected' : '' ?>>Publikált</option>
                                <option value="Archivált" <?= isset($edit_szocikk) && $edit_szocikk['STATUSZ'] == 'Archivált' ? 'selected' : '' ?>>Archivált</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategóriák</label>
                    <div class="row">
                        <?php foreach ($kategoriak as $kategoria): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kategoriak[]"
                                        id="kategoria_<?= $kategoria['ID'] ?>" value="<?= $kategoria['ID'] ?>"
                                        <?= isset($edit_kategoriak) && in_array($kategoria['ID'], $edit_kategoriak) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="kategoria_<?= $kategoria['ID'] ?>">
                                        <?= htmlspecialchars($kategoria['NEV']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Témakörök</label>
                    <div class="row">
                        <?php foreach ($temakorok as $temakor): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="temakorok[]"
                                        id="temakor_<?= $temakor['ID'] ?>" value="<?= $temakor['ID'] ?>"
                                        <?= isset($edit_temakorok) && in_array($temakor['ID'], $edit_temakorok) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="temakor_<?= $temakor['ID'] ?>">
                                        <?= htmlspecialchars($temakor['NEV']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Kulcsszavak</label>
                    <div class="row">
                        <?php foreach ($kulcsszavak as $kulcsszo): ?>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="kulcsszavak[]"
                                        id="kulcsszo_<?= $kulcsszo['ID'] ?>" value="<?= $kulcsszo['ID'] ?>"
                                        <?= isset($edit_kulcsszavak) && in_array($kulcsszo['ID'], $edit_kulcsszavak) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="kulcsszo_<?= $kulcsszo['ID'] ?>">
                                        <?= htmlspecialchars($kulcsszo['SZO']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kép</label>
                    <div class="row">
                        <input type="file" id="foto" name="foto" accept="image/*" style="display: none;">
                        <button type="button" id="fileButton">Fájl kiválasztása</button>
                        <span id="file-name">Nincs fájl kiválasztva</span>
                        <script>
                            const fileInput = document.getElementById('foto');
                            const fileButton = document.getElementById('fileButton');
                            const fileNameSpan = document.getElementById('file-name');

                            fileButton.addEventListener('click', () => {
                                fileInput.click();
                            });

                            fileInput.addEventListener('change', () => {
                                if (fileInput.files.length > 0) {
                                    fileNameSpan.textContent = fileInput.files[0].name;
                                } else {
                                    fileNameSpan.textContent = 'Nincs fájl kiválasztva';
                                }
                            });
                        </script>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <?php if (isset($edit_szocikk)): ?>
                        <button type="submit" name="update" class="btn btn-primary">Frissítés</button>
                        <a href="new_szocikk.php" class="btn btn-secondary">Mégsem</a>
                    <?php else: ?>
                        <button type="submit" name="create" id="create" class="btn btn-success">Létrehozás</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if (isset($_SESSION) && $_SESSION['admin'] == 1): ?>
            <h2 class="mt-5">Lektorálásra váró szócikkek</h2>

            <?php if (empty(array_filter($szocikkek, function ($item) {
                return isset($item['STATUSZ']) && $item['STATUSZ'] === 'Lektorálásra vár';
            }))): ?>
                <div class="alert alert-info">Nincsen lektorálásra váró szócikk.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($szocikkek as $szocikk): ?>
                        <?php if ($szocikk['STATUSZ'] == 'Lektorálásra vár'): ?>
                            <div class="col-md-6">
                                <div class="card lektoralas-card mb-3">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($szocikk['CIM']) ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?= date('Y.m.d H:i', strtotime($szocikk['LETREHOZAS_DATUM'])) ?> |
                                            Státusz: <?= htmlspecialchars($szocikk['STATUSZ']) ?> |
                                            Szerző: <?= htmlspecialchars($szocikk['SZERZO_ID']) ?> |
                                            Nyelv: <?= htmlspecialchars($szocikk['NYELV']) ?>
                                        </h6>
                                        <p class="card-text"><?= substr(htmlspecialchars($szocikk['TARTALOM']), 0, 100) ?><?php if (strlen($szocikk['TARTALOM']) > 100): ?>...<?php endif; ?></p>

                                        <?php if (!empty($szocikk['KATEGORIAK'])): ?>
                                            <p class="card-text"><small>Kategóriák: <?= htmlspecialchars($szocikk['KATEGORIAK']) ?></small></p>
                                        <?php endif; ?>

                                        <?php if (!empty($szocikk['TEMAKOROK'])): ?>
                                            <p class="card-text"><small>Témakörök: <?= htmlspecialchars($szocikk['TEMAKOROK']) ?></small></p>
                                        <?php endif; ?>

                                        <?php if (!empty($szocikk['KULCSSZAVAK'])): ?>
                                            <p class="card-text"><small>Kulcsszavak: <?= htmlspecialchars($szocikk['KULCSSZAVAK']) ?></small></p>
                                        <?php endif; ?>

                                        <?php if (!empty($ajanlottlektorok)): ?>
                                            <?php $db = 0; ?>
                                            <p class="card-text"><small>Ajánlott lektorok azonosítója:
                                                    <?php foreach ($ajanlottlektorok as $ajanlottlektor):
                                                        if ($szocikk['ID'] == $ajanlottlektor['ID']):
                                                            echo $ajanlottlektor['L_ID'] . ' ';
                                                            $db++;
                                                        endif;
                                                    endforeach; ?>
                                                </small></p>
                                        <?php endif; ?>

                                        <div class="szocikk-actions">
                                            <a href="new_szocikk.php?edit=<?= $szocikk['ID'] ?>" class="btn btn-sm btn-warning">Szerkesztés</a>
                                            <?php if (isset($_SESSION) && $_SESSION['admin'] == 1): ?>
                                                <a href="new_szocikk.php?delete=<?= $szocikk['ID'] ?>" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Biztosan törölni szeretné ezt a szócikket?')">Törlés</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <h2 class="mt-5">Szócikk lista</h2>

        <?php if (empty($szocikkek)): ?>
            <div class="alert alert-info">Nincsenek SZOCIKK-ek a rendszerben.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($szocikkek as $szocikk): ?>
                    <?php if ($szocikk['STATUSZ'] != 'Lektorálásra vár'): ?>
                        <div class="col-md-6">
                            <div class="card szocikk-card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($szocikk['CIM']) ?></h5>
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        <?= date('Y.m.d H:i', strtotime($szocikk['LETREHOZAS_DATUM'])) ?> |
                                        Státusz: <?= htmlspecialchars($szocikk['STATUSZ']) ?> |
                                        Szerző: <?= htmlspecialchars($szocikk['SZERZO_ID']) ?> |
                                        Nyelv: <?= htmlspecialchars($szocikk['NYELV']) ?>
                                    </h6>
                                    <p class="card-text"><?= substr(htmlspecialchars($szocikk['TARTALOM']), 0, 100) ?><?php if (strlen($szocikk['TARTALOM']) > 100): ?>...<?php endif; ?></p>

                                    <?php if (!empty($szocikk['KATEGORIAK'])): ?>
                                        <p class="card-text"><small>Kategóriák: <?= htmlspecialchars($szocikk['KATEGORIAK']) ?></small></p>
                                    <?php endif; ?>

                                    <?php if (!empty($szocikk['TEMAKOROK'])): ?>
                                        <p class="card-text"><small>Témakörök: <?= htmlspecialchars($szocikk['TEMAKOROK']) ?></small></p>
                                    <?php endif; ?>

                                    <?php if (!empty($szocikk['KULCSSZAVAK'])): ?>
                                        <p class="card-text"><small>Kulcsszavak: <?= htmlspecialchars($szocikk['KULCSSZAVAK']) ?></small></p>
                                    <?php endif; ?>

                                    <div class="szocikk-actions">
                                        <a href="new_szocikk.php?edit=<?= $szocikk['ID'] ?>" class="btn btn-sm btn-warning">Szerkesztés</a>
                                        <?php if (isset($_SESSION) && $_SESSION['admin'] == 1): ?>
                                            <a href="new_szocikk.php?delete=<?= $szocikk['ID'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Biztosan törölni szeretné ezt a szócikket?')">Törlés</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>