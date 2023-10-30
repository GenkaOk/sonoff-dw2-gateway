<?php
const HA_API_URL = "http://homeassistant:8123/api/states/";
const HA_TOKEN = '';

$listDevices = [
    '************' => [
        'id'     => 'entry_door',
        'name'   => 'Входная дверь',
        'v_name' => 'Входная дверь (Напряжение)',
        's_name' => 'Входная дверь (Сигнал)',
    ],
];

$data = json_decode(file_get_contents('php://input'), true);

$currentDevice = $listDevices[$data['deviceid']];

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
            'Content-Type: application/json', // if the content type is json
            'Authorization: Bearer ' . HA_TOKEN, // if you need token in header
        ]
    );
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $result = curl_exec($ch);
    curl_close($ch);
}
