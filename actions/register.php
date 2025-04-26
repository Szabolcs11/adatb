<?php
require_once '../db/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nev = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $jelszo = $_POST['password'] ?? '';
    $jelszoCheck = $_POST['password-check'] ?? '';


    if (empty($nev) || empty($email) || empty($jelszo) || empty($jelszoCheck)) {
        header("Location: ./../views/register_form.php?error=fields_empty");
    }

    if ($jelszo !== $jelszoCheck) {
        header("Location: ./../views/register_form.php?error=password_mismatch");
        exit;
    }

    $checkQuery = "SELECT COUNT(*) AS count FROM Felhasznalo WHERE NEV = :nev OR EMAIL = :email";
    $checkStmt = oci_parse($conn, $checkQuery);
    oci_bind_by_name($checkStmt, ":nev", $nev);
    oci_bind_by_name($checkStmt, ":email", $email);
    oci_execute($checkStmt);
    $row = oci_fetch_assoc($checkStmt);
    $count = $row['COUNT'];
    oci_free_statement($checkStmt);

    if ($count > 0) {
        header("Location: ./../views/register_form.php?error=username_or_email_exists");
        oci_close($conn);
        exit;
    }

    $hashedPassword = password_hash($jelszo, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Felhasznalo (NEV, EMAIL, JELSZO) VALUES (:nev, :email, :jelszo)";
    $stmt = oci_parse($conn, $sql);

    oci_bind_by_name($stmt, ":nev", $nev);
    oci_bind_by_name($stmt, ":email", $email);
    oci_bind_by_name($stmt, ":jelszo", $hashedPassword);

    $success = oci_execute($stmt);

    if ($success) {
        header("Location: ./../views/login_form.php?msg=success");
    } else {
        $e = oci_error($stmt);
        header("Location: ./../views/register_form.php?error=unexpected_error");
        echo "Hiba: " . $e['message'];
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
?>