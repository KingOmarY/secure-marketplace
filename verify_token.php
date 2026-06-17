<?php
$token = $_POST['token'] ?? '';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret' => '6LdgwRstAAAAAFV9NF2t7RkdRsefVQf_VWFT_T0f',
    'response' => $token
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $result;
?>