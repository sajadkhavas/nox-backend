<?php

return [
    'slots' => [
        'top' => ['label' => 'بالاپوش', 'preset' => 'top-default'],
        'outerwear' => ['label' => 'رویه / کت', 'preset' => 'outerwear-default'],
        'bottom' => ['label' => 'پایین‌تنه', 'preset' => 'bottom-default'],
        'full' => ['label' => 'استایل کامل', 'preset' => 'full-default'],
        'footwear' => ['label' => 'کفش', 'preset' => 'footwear-default'],
        'accessory' => ['label' => 'اکسسوری', 'preset' => 'accessory-default'],
    ],

    'presets' => [
        'top-default' => [
            'label' => 'بالاپوش استاندارد',
            'offset_x' => 0.0,
            'offset_y' => -4.0,
            'scale' => 1.0,
            'layer' => 30,
        ],
        'outerwear-default' => [
            'label' => 'رویه استاندارد',
            'offset_x' => 0.0,
            'offset_y' => -2.0,
            'scale' => 1.04,
            'layer' => 40,
        ],
        'bottom-default' => [
            'label' => 'پایین‌تنه استاندارد',
            'offset_x' => 0.0,
            'offset_y' => 16.0,
            'scale' => 1.0,
            'layer' => 25,
        ],
        'full-default' => [
            'label' => 'استایل کامل استاندارد',
            'offset_x' => 0.0,
            'offset_y' => 0.0,
            'scale' => 1.0,
            'layer' => 35,
        ],
        'footwear-default' => [
            'label' => 'کفش استاندارد',
            'offset_x' => 0.0,
            'offset_y' => 33.0,
            'scale' => 0.88,
            'layer' => 45,
        ],
        'accessory-default' => [
            'label' => 'اکسسوری استاندارد',
            'offset_x' => 0.0,
            'offset_y' => -8.0,
            'scale' => 0.82,
            'layer' => 50,
        ],
    ],

    'bounds' => [
        'offset_x' => [-50.0, 50.0],
        'offset_y' => [-50.0, 50.0],
        'scale' => [0.5, 2.0],
        'layer' => [1, 100],
    ],

    'model3d' => [
        'max_bytes' => 12 * 1024 * 1024,
        'extensions' => ['glb'],
        'mime_types' => [
            'model/gltf-binary',
            'application/gltf-buffer',
            'application/octet-stream',
        ],
    ],
];
