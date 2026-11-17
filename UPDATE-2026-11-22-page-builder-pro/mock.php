<?php
header('Content-Type: application/json');
[, $to] = array_pad(explode('|', $_GET['langpair'] ?? 'en|xx'), 2, 'xx');
echo json_encode(['responseStatus' => 200, 'responseData' => ['translatedText' => "[$to] ".($_GET['q'] ?? '')]]);
