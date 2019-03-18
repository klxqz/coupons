<?php

class shopCouponsPluginSettingsDialogAction extends waViewAction
{

    private $models = array();

    public function execute()
    {
        $id = waRequest::get('id', 0, waRequest::TYPE_INT);
        $coupons_model = new shopCouponsPluginModel();
        if ($id) {
            $coupon = $coupons_model->getById($id);
        } else {
            $coupon = $coupons_model->getEmptyRow();
            $coupon['code'] = self::generateCode();
        }

        $curm = new shopCurrencyModel();
        $currencies = $curm->getAll('code');
        $types = self::getTypes($currencies);

        $sm = new shopSetModel();

        $feature_model = new shopFeatureModel();
        $features = $feature_model->select('*')->where("`count` > 0")->fetchAll('id');

        $coupons_products_model = new shopCouponsProductsPluginModel();
        $coupons_products = $coupons_products_model->getByField('coupon_id', $id, true);

        $feature_values = array();
        if ($coupons_products) {
            $coupons_products = $this->prepareCouponsProducts($coupons_products);
            foreach ($coupons_products as $coupons_product) {
                if ($coupons_product['type'] == 'feature') {
                    $val = explode(':', $coupons_product['value']);
                    $feature_value_model = $feature_model::getValuesModel($features[$val[0]]['type']);
                    $feature_values[$val[1]] = $feature_value_model->getFeatureValue($val[1]);
                }
            }
        }

        $this->view->assign(array(
            'coupon' => $coupon,
            'types' => $types,
            'sets' => $sm->getAll(),
            'categories' => shopCouponsHelper::getCategories(),
            'features' => $features,
            'coupons_products' => $coupons_products,
            'feature_values' => $feature_values,
            'currency' => wa()->getConfig()->getCurrency(),
        ));
    }

    private function prepareCouponsProducts($coupons_products)
    {
        foreach ($coupons_products as &$coupons_product) {
            $model = $this->getModel($coupons_product['type']);
            $item = $model->getById($coupons_product['value']);
            if ($item) {
                $coupons_product['name'] = $item['name'];
            }
        }

        unset($coupons_product);
        return $coupons_products;
    }

    private function getModel($type)
    {
        $model_name = '';
        switch ($type) {
            case 'product':
                $model_name = 'shopProductModel';
                break;
            case 'set':
                $model_name = 'shopSetModel';
                break;
            case 'category':
                $model_name = 'shopCategoryModel';
                break;
            case 'feature':
                $model_name = 'shopFeatureModel';
                break;
        }

        if ($model_name && class_exists($model_name)) {
            if (empty($this->models[$model_name])) {
                $this->models[$model_name] = new $model_name();
            }
            return $this->models[$model_name];
        } else {
            throw new Exception('Не определена модель');
        }
    }

    public static function getTypes($currencies)
    {
        $result = array(
            '%' => _w('% Discount'),
        );
        foreach ($currencies as $c) {
            $info = waCurrency::getInfo($c['code']);
            $result[$c['code']] = $info['sign'] . ' ' . $c['code'];
        }
        return $result;
    }

    public static function generateCode()
    {
        $alphabet = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
        $result = '';
        while (strlen($result) < 8) {
            $result .= $alphabet{mt_rand(0, strlen($alphabet) - 1)};
        }
        return $result;
    }


}
