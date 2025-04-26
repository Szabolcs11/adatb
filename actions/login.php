<?php
session_start();
require_once '../db/connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $jelszo = $_POST['password'] ?? '';

    $query = "SELECT ID, NEV, JELSZO, ADMIN FROM Felhasznalo WHERE NEV = :username";
    $stmt = oci_parse($conn, $query);
    oci_bind_by_name($stmt, ":username", $username);
    oci_execute($stmt);

    $user = oci_fetch_assoc($stmt);


    if ($user && password_verify($jelszo, $user['JELSZO'])) {
        $_SESSION['user_id'] = $user['ID'];
        $_SESSION['user_name'] = $user['NEV'];
        $_SESSION['admin'] = $user['ADMIN'] == 1 ? true : false;
        header("Location: ../index.php");
        exit;
    } else {
        header("Location: ./../views/login_form.php?error=wrongpworemail");
    }

    oci_free_statement($stmt);
    oci_close($conn);
}
