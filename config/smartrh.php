<?php

return [
    'product_name' => env('SMARTRH_PRODUCT_NAME', 'SmartRH Maroc'),
    'support_email' => env('SMARTRH_SUPPORT_EMAIL', 'support@smartrh.test'),
    'support_phone' => env('SMARTRH_SUPPORT_PHONE', ''),
    'whatsapp_number' => env('SMARTRH_WHATSAPP_NUMBER', ''),
    'brand_legal_name' => env('SMARTRH_BRAND_LEGAL_NAME', 'SmartRH Maroc'),
    'brand_address' => env('SMARTRH_BRAND_ADDRESS', 'Casablanca, Maroc'),
    'brand_tax_id' => env('SMARTRH_BRAND_TAX_ID', ''),
    'brand_ice' => env('SMARTRH_BRAND_ICE', ''),
    'brand_primary_color' => env('SMARTRH_BRAND_PRIMARY_COLOR', 'indigo'),
    'brand_accent_color' => env('SMARTRH_BRAND_ACCENT_COLOR', 'emerald'),
    'primary_color' => env('SMARTRH_PRIMARY_COLOR', 'indigo'),
    'accent_color' => env('SMARTRH_ACCENT_COLOR', 'emerald'),
    'default_currency' => env('SMARTRH_DEFAULT_CURRENCY', 'MAD'),
    'timezone' => env('SMARTRH_TIMEZONE', 'Africa/Casablanca'),
    'payroll_disclaimer_enabled' => env('SMARTRH_PAYROLL_DISCLAIMER_ENABLED', true),
    'demo_mode_enabled' => env('SMARTRH_DEMO_MODE_ENABLED', true),
    'payroll_disclaimer' => 'Les règles de paie doivent être vérifiées par un expert-comptable marocain avant utilisation en production.',
];
