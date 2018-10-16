<?php

return array(
    'shop_coupons_plugin' => array(
        'id' => array('int', 10, 'unsigned' => 1, 'null' => 0, 'autoincrement' => 1),
        'code' => array('varchar', 32, 'null' => 0),
        'type' => array('varchar', 3, 'null' => 0),
        'order_total' => array('decimal', "15,4"),
        'limit' => array('int', 11),
        'used' => array('int', 11, 'null' => 0, 'default' => '0'),
        'value' => array('decimal', "15,4"),
        'comment' => array('text'),
        'expire_datetime' => array('datetime'),
        'create_datetime' => array('datetime', 'null' => 0),
        'create_contact_id' => array('int', 11, 'unsigned' => 1, 'null' => 0, 'default' => '0'),
        ':keys' => array(
            'PRIMARY' => 'id',
            'code' => array('code', 'unique' => 1),
        ),
    ),
    'shop_coupons_products_plugin' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'coupon_id' => array('int', 11, 'null' => 0),
        'type' => array('enum', "'product','set','category','feature'", 'null' => 0, 'default' => 'product'),
        'value' => array('varchar', 64, 'null' => 0, 'default' => ''),
        ':keys' => array(
            'PRIMARY' => array('id'),
            'coupon_id' => 'coupon_id',
        ),
    ),
);
