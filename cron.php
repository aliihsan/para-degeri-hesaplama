<?php
require_once 'Currency.php';

$currency = new Currency();
$currency->getData();
echo date('Y-m-d') . " finished";