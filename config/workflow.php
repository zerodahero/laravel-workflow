<?php

return [
    'straight' => [
        'type' => 'state_machine',
        'supports' => ['stdClass'],
        'places' => ['a', 'b', 'c'],
        'transitions' => [
            't1' => [
                'from' => 'a',
                'to' => 'b',
            ],
            't2' => [
                'from' => 'b',
                'to' => 'c',
            ],
        ],
    ],
];
