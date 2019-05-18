<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 03.06.14
 * Time: 2:36
 */





    require_once('../app/bootstrap.php');

    $checker = new ImportChecker($dbh);
    $checker->check();

    respond($checker->getStatusCode(), $checker->getStatusDescription(), $checker->getStatusDetails());