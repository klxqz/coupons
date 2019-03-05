<?php

return array(
    'name' => 'Купоны',
    'description' => 'Скидка по купону на товар, список, категорию, характеристику...',
    'vendor' => 985310,
    'version' => '1.1.1',
    'img' => 'img/coupons.png',
    'shop_settings' => true,
    'frontend' => true,
    'handlers' => array(
        'frontend_cart' => 'frontendCart',
        'order_calculate_discount' => 'orderCalculateDiscount',
        'order_action.create' => 'orderActionCreate',
    ),
);
//EOF
