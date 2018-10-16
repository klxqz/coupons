<?php

class shopCouponsPluginSettingsDeleteCouponController extends waJsonController {

    public function execute() {
        try {
            $id = waRequest::post('id', 0, waRequest::TYPE_INT);
            if ($id) {
                $coupons_model = new shopCouponsPluginModel();
                $coupons_model->deleteById($id);
            }
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

}
