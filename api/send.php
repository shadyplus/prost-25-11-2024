<?php

function get_user_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function send_lead($api_endpoint, $data)
{
    $headers = getallheaders();

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $api_endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => array_merge($headers, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Bearer _Nkq16Q4ka7MOtfrjuG_nImkALRl1GCt',
            'User-Agent: ' . $_SERVER['HTTP_USER_AGENT']
        ])
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response);
}

function backup_lead($lead)
{
    $log_entry = date('Y-m-d H:i:s') . ' - ' . json_encode($lead) . PHP_EOL;
    $file = fopen('leads_backup.log', 'a');
    fwrite($file, $log_entry);
    fclose($file);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lead = [
        'fullName' => $_POST['fullName'],
        'phoneNumber' => $_POST['phoneNumber'],
        'buyer' => $_COOKIE['buyer'] ?: $_POST['buyer'],
        'offer_id' => $_POST['offer_id'],
        'country' => $_POST['country'],
        'language' => $_POST['language'],
        'subid' => $_COOKIE['subid'] ?: $_POST['subid'],
        'ip' => get_user_ip(),
        'utm_campaign' => $_POST['utm_campaign'],
        'adset_name' => $_POST['adset_name']
    ];

    $api_endpoint = 'https://api.kma.biz/lead/add';

    $data = [
        'channel' => 'tLN0LM', // REPLACE
        'name' => $lead['fullName'],
        'phone' => $lead['phoneNumber'],
        'ip' => $lead['ip'],
        'country' => $lead['country'],
        'referer' => $_SERVER['HTTP_REFERER'],
        'data1' => $_COOKIE['subid'] ?: $lead['subid'],
        'data2' => $lead['buyer'],
        'data3' => $lead['utm_campaign'],
        'data4' => $lead['adset_name'],
        'data5' => $lead['offer_id'],
        'language' => $lead['language'],
        'ua' => $_SERVER['HTTP_USER_AGENT']
    ];

    $response = send_lead($api_endpoint, $data);

    backup_lead($lead);

    function sheetsLogger($lead, $datetime, $theme, $pp)
    {
        $lead['datetime'] = $datetime;
        $lead['theme'] = $theme;
        $lead['partner'] = $pp;

        $curl = curl_init('https://script.google.com/macros/s/AKfycbzjrC4vnKYQWKpHHy06ca9Bi5-D25S--l6hgZ6oAdY3bPFeX4TTi5SLgYIUfgVzDSEaAg/exec');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($lead)
        ]);

        $response = curl_exec($curl);
        curl_close($curl);
    }

    sheetsLogger($lead, date('Y-m-d H:i:s'), 'Prostatitis', 'KMA'); // REPLACE Theme and PP

    setcookie('response', json_encode($response), time() + (86400 * 30), "/");
    if ($response->code == 0) {
        header('Location: ' . $_COOKIE['thanksLink'] . '&status=success');
    } else {
        header('Location: ' . $_COOKIE['thanksLink'] . '&status=error');
    }
    die();
} else {
    $response = ['error' => 'Request Method not POST', 'method' => $_SERVER['REQUEST_METHOD']];
    setcookie('response', json_encode($response), time() + (86400 * 30), "/");
    header('Location: ' . $_COOKIE['thanksLink'] . '&status=error');
    die();
}
