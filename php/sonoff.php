<?php
const HA_API_URL = "http://homeassistant:8123/api/states/";
const HA_TOKEN = '';

ignore_user_abort(true);

$listDevices = [
    '**********' => [
        'id'     => 'entry_door',
        'name'   => 'Входная дверь',
        'v_name' => 'Входная дверь (Напряжение)',
        's_name' => 'Входная дверь (Сигнал)',
    ],
];

$data = json_decode(file_get_contents('php://input'), true);

if (!$currentDevice = $listDevices[$data['deviceid']]) {
    // Неизвестное устройство
    exit;
}

echo json_encode([
    'error'    => 0,
    'errmsg'   => '',
    'deviceid' => $data['deviceid'],
    'd_seq'    => $data['d_seq'],
]);

// Завершаем работу с клиентом и продолжаем отправку данных
fastcgi_finish_request();

// Отправляем запрос на сервер Sonoff
proxyRequest();

sendData(sprintf('binary_sensor.%s', $currentDevice['id']), [
    'state'      => $data['params']['switch'],
    'attributes' => [
        'friendly_name' => $currentDevice['name'],
        'device_class'  => 'door',
    ],
]);

sendData(sprintf('sensor.%s_battery_voltage', $currentDevice['id']), [
    'state'      => $data['params']['battery'],
    'attributes' => [
        'friendly_name'       => $currentDevice['v_name'],
        'state_class'         => 'measurement',
        'unit_of_measurement' => 'V',
        'device_class'        => 'voltage',
    ],
]);

sendData(sprintf('sensor.%s_rssi', $currentDevice['id']), [
    'state'      => $data['params']['rssi'],
    'attributes' => [
        'friendly_name'       => $currentDevice['s_name'],
        'state_class'         => 'measurement',
        'unit_of_measurement' => 'dBm',
        'device_class'        => 'signal_strength',
    ],
]);


function sendData($sensor, $postData)
{
    $url = HA_API_URL . $sensor;

    // for sending data as json type
    $fields = json_encode($postData);

    $ch = curl_init($url);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            'Content-Type: application/json',
            'Authorization: Bearer ' . HA_TOKEN,
        ]
    );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);
    curl_close($ch);
}

function proxyRequest()
{
    $ch = curl_init('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    curl_setopt(
        $ch,
        CURLOPT_HTTPHEADER,
        [
            'Content-Type: application/json',
            'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'],
        ]
    );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));

    if (!$result = curl_exec($ch)) {
        file_put_contents('./error.log', print_r(curl_error($ch), true) . PHP_EOL, FILE_APPEND);
        file_put_contents('./error.log', print_r(curl_getinfo($ch), true) . PHP_EOL, FILE_APPEND);
    }

    curl_close($ch);
}
