<?php

class shopCouponsPlugin extends shopPlugin
{

    static $orderCalculateDiscountOff = false;

    public function frontendCart()
    {
        if (!$this->getSettings('status')) {
            return;
        }

        if (waRequest::method() == 'post') {
            $checkout_data = wa()->getStorage()->get('shop/checkout', array());
            if ($coupon_code = waRequest::post('coupon_code')) {
                $checkout_data['coupon_code'] = $coupon_code;
            } elseif (isset($checkout_data['coupon_code'])) {
                unset($checkout_data['coupon_code']);
            }
            wa()->getStorage()->set('shop/checkout', $checkout_data);
        }

        $checkout_data = wa()->getStorage()->get('shop/checkout', array());
        if (empty($checkout_data['coupon_code'])) {
            return;
        }
        $coupon_code = $checkout_data['coupon_code'];

        $coupon_discount = 0;
        if ($discount = $this->getCouponDiscount($coupon_code)) {
            if (!empty($discount['items'])) {
                foreach ($discount['items'] as $item) {
                    $coupon_discount += $item['discount'];
                }
            } elseif (!empty($discount['discount'])) {
                $coupon_discount += $discount['discount'];
            }
        }
        if ($coupon_discount) {
            $view = wa()->getView();
            $view->assign('coupon_discount', $coupon_discount);
        } else {
            unset($checkout_data['coupon_code']);
            wa()->getStorage()->set('shop/checkout', $checkout_data);
        }
    }

    public function getCouponDiscount($coupon_code, $items = null, $currency = null)
    {
        if (!($coupon = self::checkCoupon($coupon_code))) {
            return false;
        }

        if (!$items) {
            $cart = new shopCart();
            $items = $cart->items();
        }

        if (!$currency) {
            $currency = wa('shop')->getConfig()->getCurrency(false);
        }

        $total = 0;
        foreach ($items as $item) {
            $total += shop_currency($item['price'] * $item['quantity'], $item['currency'], wa('shop')->getConfig()->getCurrency(true), false);
        }

        if ($total < $coupon['order_total']) {
            return false;
        }

        $discount = array();
        $product_ids = array();
        $enabled_products = array();
        $coupons_products = array();

        if ($coupon['coupon'] == 'coupons') {
            foreach ($items as $item) {
                $product_ids[] = $item['product_id'];
            }

            $coupons_products_model = new shopCouponsProductsPluginModel();
            $coupons_products = $coupons_products_model->getByField('coupon_id', $coupon['id'], true);

            foreach ($coupons_products as $coupons_product) {
                if ($coupons_product['type'] == 'feature') {
                    $val = explode(':', $coupons_product['value']);
                    $feature_model = new shopFeatureModel();
                    $feature = $feature_model->getById($val[0]);
                    $feature_data = array($feature['code'] => $val[1]);
                    $collection = new shopProductsCollection();
                    $collection->filters($feature_data);
                } elseif ($coupons_product['type'] == 'product') {
                    $collection = new shopProductsCollection('id/' . $coupons_product['value']);
                } else {
                    $collection = new shopProductsCollection($coupons_product['type'] . '/' . $coupons_product['value']);
                }
                $collection->addWhere('`p`.`id` IN (' . implode(',', $product_ids) . ')');

                self::$orderCalculateDiscountOff = true;
                $ids = array_keys($collection->getProducts('*', 0, 99999));
                self::$orderCalculateDiscountOff = false;
                $enabled_products = array_merge($enabled_products, $ids);
            }
        }

        if ($coupon['type'] == '%') {
            foreach ($items as $item_id => $item) {
                if ($item['type'] == 'product' && (in_array($item['product_id'], $enabled_products) || !$coupons_products)) {

                    $discount['items'][$item_id] = array(
                        'discount' => shop_currency($item['price'] * $coupon['value'] / 100.00, $item['currency'], $currency, false) * $item['quantity'],
                        'description' => "Скидка по купону " . $coupon['code'] . " в размере " . (float)$coupon['value'] . "%"
                    );
                }
            }
        } else {
            $discount = array(
                'discount' => shop_currency($coupon['value'], $coupon['type'], $currency, false),
                'description' => "Скидка по купону " . $coupon['code'] . " в размере " . shop_currency($coupon['value'], $coupon['type'], $currency)
            );
        }

        return $discount;
    }

    public function orderCalculateDiscount($params)
    {
        if (!$this->getSettings('status') || self::$orderCalculateDiscountOff) {
            return;
        }
        $checkout_data = wa()->getStorage()->get('shop/checkout', array());
        if (empty($checkout_data['coupon_code'])) {
            return;
        }
        $coupon_code = $checkout_data['coupon_code'];

        if ($discount = $this->getCouponDiscount($coupon_code, $params['order']['items'], $params['order']['currency'])) {
            return $discount;
        } else {
            unset($checkout_data['coupon_code']);
            wa()->getStorage()->set('shop/checkout', $checkout_data);
        }
    }

    public static function formatValue($c, $curr = null)
    {
        static $currencies = null;
        if ($currencies === null) {
            if ($curr) {
                $currencies = $curr;
            } else {
                $curm = new shopCurrencyModel();
                $currencies = $curm->getAll('code');
            }
        }

        if ($c['type'] == '$FS') {
            return _w('Free shipping');
        } else if ($c['type'] === '%') {
            return waCurrency::format('%0', $c['value'], 'USD') . '%';
        } else if (!empty($currencies[$c['type']])) {
            return waCurrency::format('%0{s}', $c['value'], $c['type']);
        } else {
            // Coupon of unknown type. Possibly from a plugin?..
            return '';
        }
    }

    public static function isEnabled($c)
    {
        if ($c == 'coupons') {
            return wa()->getPlugin('coupons')->getSettings('status');
        } else {
            $result = $c['limit'] == 0 || $c['limit'] > $c['used'];
            return $result && ($c['expire_datetime'] == '' || strtotime($c['expire_datetime']) > time());
        }
    }

    public function orderActionCreate($params)
    {
        if (!$this->getSettings('status')) {
            return;
        }
        $checkout_data = wa()->getStorage()->get('shop/checkout', array());
        if (empty($checkout_data['coupon_code'])) {
            return;
        }
        $coupon_code = $checkout_data['coupon_code'];
        if (!($coupon = self::checkCoupon($coupon_code))) {
            return;
        }
        $order_id = $params['order_id'];
        $order_model = new shopOrderModel();
        $order = $order_model->getOrder($order_id);

        $this->useOne($coupon_code);
        if ($this->getSettings('shop_script_coupons') && $coupon['coupon'] == 'shop-script') {
            $order_params_model = new shopOrderParamsModel();
            $order_params_model->insert(array('order_id' => $order['id'], 'name' => 'coupon_id', 'value' => $coupon['id']));
            $order_params_model->insert(array('order_id' => $order['id'], 'name' => 'coupon_discount', 'value' => $coupon['value']));
        }

        wa()->getStorage()->set('shop/coupon_code', '');


        $log_data = array(
            'action_id' => 'comment',
            'order_id' => $order_id,
            'before_state_id' => $order['state_id'],
            'after_state_id' => $order['state_id'],
            'text' => 'Плагин «<a target="_blank" href="?action=plugins#/coupons/">Купоны</a>»: '
                . 'К заказу был применен купон на скидку ' . $coupon_code . ' в размере ' . self::formatValue($coupon),
        );
        $log_model = new shopOrderLogModel();
        $log_model->add($log_data);
    }

    public static function checkCoupon($coupon_code)
    {
        if (!wa()->getPlugin('coupons')->getSettings('status')) {
            return false;
        }
        $coupons_model = new shopCouponsPluginModel();
        $coupon = $coupons_model->getByField('code', $coupon_code);
        if ($coupon && self::isEnabled($coupon)) {
            $coupon['coupon'] = 'coupons';
            return $coupon;
        }

        if (wa()->getPlugin('coupons')->getSettings('shop_script_coupons')) {
            $coupon_model = new shopCouponModel();
            $coupon = $coupon_model->getByField('code', $coupon_code);
            if ($coupon && self::isEnabled($coupon)) {
                $coupon['coupon'] = 'shop-script';
                return $coupon;
            }
        }
        return false;
    }

    private function useOne($coupon_code)
    {
        $coupons_model = new shopCouponsPluginModel();
        $coupon_model = new shopCouponModel();

        if ($coupon = $coupons_model->getByField('code', $coupon_code)) {
            $coupons_model->updateById($coupon['id'], array('used' => $coupon['used'] + 1));
        } elseif (
            wa()->getPlugin('coupons')->getSettings('shop_script_coupons') &&
            ($coupon = $coupon_model->getByField('code', $coupon_code))
        ) {
            $coupon_model->useOne($coupon['id']);
        }
    }

    public static function __callStatic($name, $arguments)
    {
        waLog::log("Метод shopCouponsPlugin::$name() не существует.\nВозможно, данный метод устарел и больше не используется.");
    }

}
