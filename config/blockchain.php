<?php

return [
    'default' => env('BLOCKCHAIN_PROVIDER', 'mock'),

    'providers' => [
        'infura' => [
            'project_id' => env('INFURA_PROJECT_ID'),
            'project_secret' => env('INFURA_PROJECT_SECRET'),
            'network' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
            'endpoint' => 'https://{network}.infura.io/v3/{project_id}',
        ],

        'metamask' => [
            'api_key' => env('METAMASK_API_KEY'),
            'network' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
            'endpoint' => 'https://{network}.infura.io/v3/{api_key}',
        ],

        'alchemy' => [
            'api_key' => env('ALCHEMY_API_KEY'),
            'network' => env('BLOCKCHAIN_NETWORK', 'eth-sepolia'),
            'endpoint' => 'https://{network}.g.alchemy.com/v2/{api_key}',
        ],
    ],

    'networks' => [
        'ethereum' => [
            'mainnet' => 'mainnet',
            'sepolia' => 'sepolia',
            'alchemy_mainnet' => 'eth-mainnet',
            'alchemy_sepolia' => 'eth-sepolia',
        ],
    ],

    'confirmations' => [
        'btc' => 6,
        'eth' => 12,
        'usdt' => 12,
        'sol' => 32,
        'xrp' => 1,
    ],
];
