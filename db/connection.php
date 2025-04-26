<?php
$conn = oci_connect("system", "Szabolcs11", "127.0.0.1/freepdb1", "AL32UTF8");
putenv('NLS_LANG=AMERICAN_AMERICA.AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}

?>