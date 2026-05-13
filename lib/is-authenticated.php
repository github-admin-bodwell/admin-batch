<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

function IsAuthenticated() {
    session_start();
    $staffId = $_SESSION['staffId'];
    return isset($staffId) && strlen($staffId) > 0;
}

function IsRequestLogout() {
    $command = isset($_GET['cmd']) ? $_GET['cmd'] : '';
    return isset($command) && $command == 'logout';
}
