<?php

// The ONLY source of truth for prices and delivery fees. The frontend
// never sends a price — only a product id and quantity — so nothing
// from the browser can change what an order costs. Fill in a number
// (Naira, no commas) to make a product orderable online; leave it null
// and that item stays request-a-quote-only, same as the current site.
//
// Only the four fixed-price items from the WixWood catalog are listed
// here. Everything else (dining tables, epoxy tables, custom interiors,
// etc.) is genuinely made-to-order and intentionally excluded — the API
// rejects an order for any product id not in this list.

return [

    'currency' => 'NGN',

    'products' => [
        'hardwood-stool' => ['name' => 'Hardwood Stool', 'price' => 80000],
        'handled-cup' => ['name' => 'Handled Hardwood Cup', 'price' => 20000],
        'serving-bowl' => ['name' => 'Hardwood Serving Bowl', 'price' => 15000],
        'serving-board' => ['name' => 'Hardwood Serving & Cutting Board', 'price' => 10000],
    ],

    // id => [name, fee]. The id must match what the site's checkout
    // form sends as the delivery zone.
    'delivery_zones' => [
        'lagos-mainland' => ['name' => 'Lagos Mainland', 'fee' => 0],
        'lagos-island' => ['name' => 'Lagos Island', 'fee' => 0],
        'abuja' => ['name' => 'Abuja', 'fee' => 0],
        'pickup' => ['name' => 'Workshop pickup (Port-Harcourt)', 'fee' => 0],
    ],

];
