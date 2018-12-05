<?php

try {
    $files = array(
        'plugins/coupons/lib/config/routing.php',
        'plugins/coupons/lib/actions/frontend/shopCouponsPluginFrontendCoupons.controller.php',
        'plugins/coupons/lib/actions/frontend/',
    );

    foreach ($files as $file) {
        waFiles::delete(wa()->getAppPath($file, 'shop'), true);
    }
} catch (Exception $e) {
    
}