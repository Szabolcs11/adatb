<?php
require_once './../db/connection.php';

// Select all szócikk IDs
$query = "SELECT ID FROM Szocikk";
$stmt = oci_parse($conn, $query);
oci_execute($stmt);

$ids = [];
while ($row = oci_fetch_assoc($stmt)) {
    $ids[] = $row['ID'];
}

oci_free_statement($stmt);
oci_close($conn);

if (!empty($ids)) {
    $randomId = $ids[array_rand($ids)];
    header("Location: ./szocikk.php?id=" . $randomId);
    exit;
} else {
    echo "Nincs elérhető szócikk.";
}
