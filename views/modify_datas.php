<?php
session_start();

require_once './../db/connection.php';


if ($_SESSION['admin'] == 0) {

    header("Location: ../index.php");
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Felhasználók kezelése
    if (isset($_POST['felhasznalok'])) {
        foreach ($_POST['felhasznalok'] as $f) {
            $id = $f['id'];
            if (isset($f['delete'])) {
                $sql = "DELETE FROM felhasznalo WHERE id = :id";
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ':id', $id);
                oci_execute($stid);
                continue;
            }
            $sql = "UPDATE felhasznalo SET nev = :nev, jelszo = :jelszo, email = :email WHERE id = :id";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':nev', $f['nev']);
            oci_bind_by_name($stid, ':jelszo', $f['jelszo']);
            oci_bind_by_name($stid, ':email', $f['email']);
            oci_bind_by_name($stid, ':id', $id);
            oci_execute($stid);
        }
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    if (!empty($_POST['uj_felhasznalo']['nev'])) {
        $sql = "INSERT INTO felhasznalo (nev, jelszo, email, admin) VALUES (:nev, :jelszo, :email, 0)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':nev', $_POST['uj_felhasznalo']['nev']);
        oci_bind_by_name($stid, ':jelszo', password_hash($_POST['uj_felhasznalo']['jelszo'], PASSWORD_DEFAULT));
        oci_bind_by_name($stid, ':email', $_POST['uj_felhasznalo']['email']);

        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            echo "Error inserting user: " . $e['message'];
        } else {
            echo "User created successfully!";
        }
    }

    // Kategóriák kezelése
    if (isset($_POST['kategoriak'])) {
        foreach ($_POST['kategoriak'] as $k) {
            $id = $k['id'];
            if (isset($k['delete'])) {
                $sql = "DELETE FROM kategoria WHERE id = :id";
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ':id', $id);
                oci_execute($stid);
                continue;
            }
            $sql = "UPDATE kategoria SET nev = :nev WHERE id = :id";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':nev', $k['nev']);
            oci_bind_by_name($stid, ':id', $id);
            oci_execute($stid);
        }
    }
    if (!empty($_POST['uj_kategoria']['nev'])) {
        $sql = "INSERT INTO kategoria (nev) VALUES (:nev)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':nev', $_POST['uj_kategoria']['nev']);
        oci_execute($stid);
    }

    // Kulcsszavak kezelése
    if (isset($_POST['kulcsszavak'])) {
        foreach ($_POST['kulcsszavak'] as $k) {
            $id = $k['id'];
            if (isset($k['delete'])) {
                $sql = "DELETE FROM kulcsszo WHERE id = :id";
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ':id', $id);
                oci_execute($stid);
                continue;
            }
            $sql = "UPDATE kulcsszo SET szo = :szo WHERE id = :id";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':szo', $k['SZO']);
            oci_bind_by_name($stid, ':id', $id);
            oci_execute($stid);
        }
    }
    if (!empty($_POST['uj_kulcsszo']['SZO'])) {
        $sql = "INSERT INTO kulcsszo (szo) VALUES (:szo)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':szo', $_POST['uj_kulcsszo']['SZO']);
        oci_execute($stid);
    }

    // Témakörök kezelése
    if (isset($_POST['temakorok'])) {
        foreach ($_POST['temakorok'] as $t) {
            $id = $t['id'];
            if (isset($t['delete'])) {
                $sql = "DELETE FROM temakor WHERE id = :id";
                $stid = oci_parse($conn, $sql);
                oci_bind_by_name($stid, ':id', $id);
                oci_execute($stid);
                continue;
            }
            $sql = "UPDATE temakor SET nev = :nev, szulo_tema_id = :szulo WHERE id = :id";
            $stid = oci_parse($conn, $sql);
            oci_bind_by_name($stid, ':nev', $t['NEV']);
            oci_bind_by_name($stid, ':szulo', $t['SZULO_TEMA_ID']);
            oci_bind_by_name($stid, ':id', $id);
            oci_execute($stid);
        }
    }
    if (!empty($_POST['uj_temakor']['NEV'])) {
        $sql = "INSERT INTO temakor (nev, szulo_tema_id) VALUES (:nev, :szulo)";
        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ':nev', $_POST['uj_temakor']['NEV']);
        oci_bind_by_name($stid, ':szulo', $_POST['uj_temakor']['SZULO_TEMA_ID']);
        oci_execute($stid);
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Adatok lekérdezése
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

$felhasznalok = fetchAll($conn, "SELECT * FROM felhasznalo");
$kategoriak = fetchAll($conn, "SELECT * FROM kategoria");
$kulcsszavak = fetchAll($conn, "SELECT * FROM kulcsszo");
$temakorok = fetchAll($conn, "SELECT * FROM temakor");
$nullaszocikk = fetchAll($conn, "SELECT f.id, f.nev, f.email
                                 FROM felhasznalo f
                                 WHERE f.id NOT IN (
                                     SELECT DISTINCT sz.szerzo_id
                                     FROM szocikk sz
                                     WHERE sz.szerzo_id IS NOT NULL
                                 )");
$nincshozzalektor = fetchAll($conn, "SELECT sz.ID, sz.CIM, sz.NYELV, k.SZO
                                     FROM SZOCIKK sz
                                     INNER JOIN SZOCIKKKULCSSZO szk ON sz.ID = szk.SZOCIKK_ID
                                     INNER JOIN KULCSSZO k ON szk.KULCSSZO_ID = k.ID
                                     INNER JOIN LEKTOR l ON l.SZAKTERULET = k.SZO
                                     INNER JOIN LEKTORNYELV lny ON l.id = lny.LEKTOR_ID
                                     WHERE sz.ID NOT IN (
                                         SELECT sz.ID
                                         FROM SZOCIKK sz
                                         INNER JOIN SZOCIKKKULCSSZO szk ON sz.ID = szk.SZOCIKK_ID
                                         INNER JOIN KULCSSZO k ON szk.KULCSSZO_ID = k.ID
                                         INNER JOIN LEKTOR l ON l.SZAKTERULET = k.SZO
                                         INNER JOIN LEKTORNYELV lny ON l.id = lny.LEKTOR_ID
                                         WHERE lny.NYELV = sz.NYELV AND l.SZAKTERULET = k.SZO
                                     )");
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <title>Adatkezelés</title>
    <link rel="stylesheet" href="modify_datas.css">
</head>

<body>
    <div class="container">
        <h1>Felhasználók, Kategóriák, Kulcsszavak, Témakörök kezelése</h1>
        <form method="POST">
            <input type="hidden" name="save" value="1">

            <div class="stats">
                <div class="stat-card">
                    <h2>Felhasználók</h2>
                    <table border="1">
                        <tr>
                            <th>ID</th>
                            <th>Név</th>
                            <th>Jelszó</th>
                            <th>Email</th>
                            <th>Törlés</th>
                        </tr>
                        <?php foreach ($felhasznalok as $index => $f): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['ID']) ?><input type="hidden" name="felhasznalok[<?= $index ?>][id]" value="<?= htmlspecialchars($f['ID']) ?>"></td>
                                <td><input type="text" name="felhasznalok[<?= $index ?>][nev]" value="<?= htmlspecialchars($f['NEV']) ?>"></td>
                                <td><input type="text" name="felhasznalok[<?= $index ?>][jelszo]" value="<?= htmlspecialchars($f['JELSZO']) ?>"></td>
                                <td><input type="email" name="felhasznalok[<?= $index ?>][email]" value="<?= htmlspecialchars($f['EMAIL']) ?>"></td>
                                <td><input type="checkbox" name="felhasznalok[<?= $index ?>][delete]"></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td>Új</td>
                            <td><input type="text" name="uj_felhasznalo[nev]"></td>
                            <td><input type="text" name="uj_felhasznalo[jelszo]"></td>
                            <td><input type="email" name="uj_felhasznalo[email]"></td>
                            <td></td>
                        </tr>
                    </table>
                </div>

                <div class="stat-card">
                    <h2>Kategóriák</h2>
                    <table border="1">
                        <tr>
                            <th>ID</th>
                            <th>Név</th>
                            <th>Törlés</th>
                        </tr>
                        <?php foreach ($kategoriak as $index => $k): ?>
                            <tr>
                                <td><?= htmlspecialchars($k['ID']) ?><input type="hidden" name="kategoriak[<?= $index ?>][id]" value="<?= htmlspecialchars($k['ID']) ?>"></td>
                                <td><input type="text" name="kategoriak[<?= $index ?>][nev]" value="<?= htmlspecialchars($k['NEV']) ?>"></td>
                                <td><input type="checkbox" name="kategoriak[<?= $index ?>][delete]"></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td>Új</td>
                            <td><input type="text" name="uj_kategoria[nev]"></td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="stats">
                <div class="stat-card">
                    <h2>Kulcsszavak</h2>
                    <table border="1">
                        <tr>
                            <th>ID</th>
                            <th>Szó</th>
                            <th>Törlés</th>
                        </tr>
                        <?php foreach ($kulcsszavak as $index => $kulcs): ?>
                            <tr>
                                <td><?= htmlspecialchars($kulcs['ID']) ?><input type="hidden" name="kulcsszavak[<?= $index ?>][id]" value="<?= htmlspecialchars($kulcs['ID']) ?>"></td>
                                <td><input type="text" name="kulcsszavak[<?= $index ?>][SZO]" value="<?= htmlspecialchars($kulcs['SZO']) ?>"></td>
                                <td><input type="checkbox" name="kulcsszavak[<?= $index ?>][delete]"></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td>Új</td>
                            <td><input type="text" name="uj_kulcsszo[SZO]"></td>
                            <td></td>
                        </tr>
                    </table>
                </div>

                <div class="stat-card">
                    <h2>Témakörök</h2>
                    <table border="1">
                        <tr>
                            <th>ID</th>
                            <th>Név</th>
                            <th>Szülő téma ID</th>
                            <th>Törlés</th>
                        </tr>
                        <?php foreach ($temakorok as $index => $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['ID']) ?><input type="hidden" name="temakorok[<?= $index ?>][id]" value="<?= htmlspecialchars($t['ID']) ?>"></td>
                                <td><input type="text" name="temakorok[<?= $index ?>][NEV]" value="<?= htmlspecialchars($t['NEV']) ?>"></td>
                                <td><input type="number" name="temakorok[<?= $index ?>][SZULO_TEMA_ID]" value="<?= htmlspecialchars($t['SZULO_TEMA_ID']) ?>"></td>
                                <td><input type="checkbox" name="temakorok[<?= $index ?>][delete]"></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td>Új</td>
                            <td><input type="text" name="uj_temakor[NEV]"></td>
                            <td><input type="number" name="uj_temakor[SZULO_TEMA_ID]"></td>
                            <td></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="btn">Mentés</button>
            </div>
        </form>

        <hr />

        <div class="stat-card">
            <h2>Felhasználók, akik még nem írtak cikket</h2>
            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>Név</th>
                    <th>Email</th>
                </tr>
                <?php if (count($nullaszocikk) == 0): ?> <tr>
                        <td style="text-align:center;" colspan=4>Nincs ilyen felhasználó</td>
                    </tr> <?php endif; ?>
                <?php foreach ($nullaszocikk as $index => $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['ID']) ?></td>
                        <td><?= htmlspecialchars($n['NEV']) ?></td>
                        <td><?= htmlspecialchars($n['EMAIL']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="stat-card">
            <h2>Szócikkek, amikhez nincs megfelelő lektor</h2>
            <table border="1">
                <tr>
                    <th>ID</th>
                    <th>Cím</th>
                    <th>Nyelv</th>
                    <th>Szakterület</th>
                </tr>
                <?php if (count($nincshozzalektor) == 0): ?> <tr>
                        <td style="text-align:center;" colspan=4>Nincs ilyen szócikk</td>
                    </tr> <?php endif; ?>
                <?php foreach ($nincshozzalektor as $index => $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['ID']) ?></td>
                        <td><?= htmlspecialchars($n['CIM']) ?></td>
                        <td><?= htmlspecialchars($n['NYELV']) ?></td>
                        <td><?= htmlspecialchars($n['SZO']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>

</html>