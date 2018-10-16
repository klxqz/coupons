<?php

class shopCouponsPluginSettingsAction extends waViewAction {

    public function execute() {
        $curm = new shopCurrencyModel();
        $currencies = $curm->getAll('code');

        $coupons_model = new shopCouponsPluginModel();
        $coupons = $coupons_model->getAll();
        $sku_model = new shopProductSkusModel();
        $product_model = new shopProductModel();
        foreach ($coupons as &$c) {
            $c['enabled'] = shopCouponsPlugin::isEnabled($c);
            $c['value'] = shopCouponsPlugin::formatValue($c, $currencies);
        }
        unset($c);


        $this->view->assign(array(
            'settings' => wa()->getPlugin('coupons')->getSettings(),
            'coupons' => $coupons
        ));
    }

}
