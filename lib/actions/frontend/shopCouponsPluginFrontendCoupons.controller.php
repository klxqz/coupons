<?php

class shopCouponsPluginFrontendCouponsController extends waJsonController {

    public function execute() {
        try {
            if (waRequest::post('cancel')) {
                wa()->getStorage()->set('shop/coupon_code', '');
            } else {
                if ($coupon_code = trim(waRequest::post('coupon_code'))) {
                    if (shopCouponsPlugin::checkCoupon($coupon_code)) {
                        wa()->getStorage()->set('shop/coupon_code', $coupon_code);
                    } else {
                        throw new waException('Купон не найден');
                    }
                } else {
                    throw new waException('Укажите код купона');
                }
            }
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

}
