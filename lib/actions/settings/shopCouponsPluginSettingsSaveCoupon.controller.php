<?php

class shopCouponsPluginSettingsSaveCouponController extends waJsonController {

    public function execute() {
        try {
            $coupon = waRequest::post('coupon');

            $coupons_model = new shopCouponsPluginModel();
            if (empty($coupon['id'])) {
                $coupon['create_contact_id'] = wa()->getUser()->getId();
                $coupon['create_datetime'] = date('Y-m-d H:i:s');
                $coupon['id'] = $coupons_model->insert($coupon);
            } else {
                $coupons_model->updateById($coupon['id'], $coupon);
            }
            $coupon = $coupons_model->getById($coupon['id']);
            $coupon['enabled'] = shopCouponsPlugin::isEnabled($coupon);
            $coupon['value'] = shopCouponsPlugin::formatValue($coupon);

            $coupons_products = waRequest::post('coupons_products');
            if (!empty($coupons_products['value'])) {
                $coupons_products_model = new shopCouponsProductsPluginModel();
                foreach ($coupons_products['value'] as $index => $value) {
                    if (empty($coupons_products['id'][$index])) {
                        $data = array(
                            'coupon_id' => $coupon['id'],
                            'type' => $coupons_products['type'][$index],
                            'value' => $value,
                        );
                        $coupons_products_model->insert($data);
                    }
                }
            }



            $view = wa()->getView();
            $view->assign('coupon', $coupon);
            $template_path = wa()->getAppPath('plugins/coupons/templates/actions/settings/include.tr.html', 'shop');
            $html = $view->fetch($template_path);
            $this->response['id'] = $coupon['id'];
            $this->response['html'] = $html;
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

}
