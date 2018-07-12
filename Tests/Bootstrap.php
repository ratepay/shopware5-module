<?php
/**
 * Created by PhpStorm.
 * User: eiriarte-mendez
 * Date: 11.07.18
 * Time: 10:16
 */

$env = getenv('TEST_ENV');
if ('functional' === $env) {
    require __DIR__ . '/Functional/Bootstrap.php';
}

