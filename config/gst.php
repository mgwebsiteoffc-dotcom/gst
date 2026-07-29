<?php

return [
    'slabs' => [0, 0.25, 3, 5, 12, 18, 28, 40],
    'default_slab' => 18,
    'uqc' => ['NOS' => 'Numbers', 'PCS' => 'Pieces', 'KGS' => 'Kilograms', 'LTR' => 'Litre', 'MTR' => 'Metre',
        'BOX' => 'Box', 'BAG' => 'Bags', 'SET' => 'Set', 'PAC' => 'Packs', 'UNT' => 'Units', 'OTH' => 'Others'],
    'einvoice_cancel_reasons' => ['1' => 'Duplicate', '2' => 'Data entry mistake', '3' => 'Order cancelled', '4' => 'Others'],
    'transport_modes' => ['1' => 'Road', '2' => 'Rail', '3' => 'Air', '4' => 'Ship'],
    // e-Way bill is required when consignment value exceeds this amount.
    'ewaybill_threshold' => 50000,
];
