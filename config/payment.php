<?php

return [
    'default' => env('PAYMENT_GATEWAY', 'vnpay'),

    'refund_cutoff_hours' => (float) env('PAYMENT_REFUND_CUTOFF_HOURS', 0),

    'gateways' => [
        'mock' => [
            'label' => 'Cổng mô phỏng (demo)',
            'enabled' => (bool) env('PAYMENT_MOCK_ENABLED', false),
            'secret' => env('PAYMENT_MOCK_SECRET'),
            'methods' => [
                'mock' => 'Mô phỏng',
            ],
        ],

        'vnpay' => [
            'label' => 'VNPay',
            'enabled' => (bool) env('VNPAY_ENABLED', false),
            'tmn_code' => env('VNPAY_TMN_CODE'),
            'hash_secret' => env('VNPAY_HASH_SECRET'),
            'pay_url' => env('VNPAY_PAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
            'api_url' => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),
            'methods' => [
                'vnpay_qr' => 'QR VNPay',
                'vnpay_card' => 'Thẻ / ATM / Ví',
            ],
        ],

        'momo' => [
            'label' => 'MoMo',
            'enabled' => (bool) env('MOMO_ENABLED', false),
            'partner_code' => env('MOMO_PARTNER_CODE'),
            'access_key' => env('MOMO_ACCESS_KEY'),
            'secret_key' => env('MOMO_SECRET_KEY'),
            'partner_name' => env('MOMO_PARTNER_NAME', 'YUNFLIMS'),
            'store_id' => env('MOMO_STORE_ID', 'YUNFLIMS'),
            'create_url' => env('MOMO_CREATE_URL', 'https://test-payment.momo.vn/v2/gateway/api/create'),
            'query_url' => env('MOMO_QUERY_URL', 'https://test-payment.momo.vn/v2/gateway/api/query'),
            'methods' => [
                'momo_wallet' => 'Ví MoMo',
            ],
        ],
    ],
];