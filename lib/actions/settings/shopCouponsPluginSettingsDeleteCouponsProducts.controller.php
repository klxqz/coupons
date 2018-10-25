<?php

class shopCouponsPluginSettingsDeleteCouponsProductsController extends waJsonController {

    public function execute() {
        try {
            $id = waRequest::post('id', 0, waRequest::TYPE_INT);
            if ($id) {
                $coupons_products_model = new shopCouponsProductsPluginModel();
                $coupons_products_model->deleteById($id);
            }
        } catch (Exception $e) {
            $this->setError($e->getMessage());
        }
    }

}
