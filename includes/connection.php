<?php
    // establish a database connection to your Oracle database.
    $username = 's3844180';
    $password = 'Freeman1!'; //DO NOT enter your RMIT password
    $servername = 'talsprddb01.int.its.rmit.edu.au';
    $servicename = 'CSAMPR1.ITS.RMIT.EDU.AU';
    $connection = $servername."/".$servicename;

    $conn = oci_connect($username, $password, $connection);
    if(!$conn)
    {
        $e = oci_error();
        trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
    }
    else
    {
        //echo "<p>Successfully connected to CSAMPR1.ITS.RMIT.EDU.AU.</p>";
    }

?>