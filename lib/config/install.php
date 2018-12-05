<?php
$plugin_id = array('shop', 'coupons');
$app_settings_model = new waAppSettingsModel();
$app_settings_model->set($plugin_id, 'status', '1');
$app_settings_model->set($plugin_id, 'shop_script_coupons', '1');