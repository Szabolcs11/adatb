<?php
$conn = oci_connect("system", "Szabolcs11", "localhost", "AL32UTF8");
putenv('NLS_LANG=AMERICAN_AMERICA.AL32UTF8');
if (!$conn) {
    $e = oci_error();
    die("Connection failed: " . $e['message']);
}
