<?php

class shopCouponsPluginSettingsDialogAction extends waViewAction {

    private $models = array();

    public function execute() {
        $id = waRequest::get('id', 0, waRequest::TYPE_INT);
        $coupons_model = new shopCouponsPluginModel();
        if ($id) {
            $coupon = $coupons_model->getById($id);
        } else {
            $coupon = $coupons_model->getEmptyRow();
            $coupon['code'] = self::generateCode();
        }
        $this->view->assign('coupon', $coupon);

        $curm = new shopCurrencyModel();
        $currencies = $curm->getAll('code');
        $types = self::getTypes($currencies);
        $this->view->assign('types', $types);

        $sm = new shopSetModel();
        $this->view->assign('sets', $sm->getAll());
        $this->view->assign('categories', $this->getCategories());
        $this->view->assign('features_filter', $this->getFeaturesFilter());


        $coupons_products_model = new shopCouponsProductsPluginModel();
        $coupons_products = $coupons_products_model->getByField('coupon_id', $id, true);

        if ($coupons_products) {
            $coupons_products = $this->prepareCouponsProducts($coupons_products);
        }
        $this->view->assign('coupons_products', $coupons_products);
        $this->view->assign('currency', wa()->getConfig()->getCurrency());
    }

    private function prepareCouponsProducts($coupons_products) {
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

    private function getModel($type) {
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

    public static function getTypes($currencies) {
        $result = array(
            '%' => _w('% Discount'),
        );
        foreach ($currencies as $c) {
            $info = waCurrency::getInfo($c['code']);
            $result[$c['code']] = $info['sign'] . ' ' . $c['code'];
        }
        return $result;
    }

    public static function generateCode() {
        $alphabet = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890";
        $result = '';
        while (strlen($result) < 8) {
            $result .= $alphabet{mt_rand(0, strlen($alphabet) - 1)};
        }
        return $result;
    }

    private function getCategories() {

        $category_model = new shopCategoryModel();
        $route = null;
        $cats = $category_model->getTree(null, null, false, $route);


        $stack = array();
        $result = array();
        foreach ($cats as $c) {
            $c['childs'] = array();

            // Number of stack items
            $l = count($stack);

            // Check if we're dealing with different levels
            while ($l > 0 && $stack[$l - 1]['depth'] >= $c['depth']) {
                array_pop($stack);
                $l--;
            }

            // Stack is empty (we are inspecting the root)
            if ($l == 0) {
                // Assigning the root node
                $i = count($result);
                $result[$i] = $c;
                $stack[] = &$result[$i];
            } else {
                // Add node to parent
                $i = count($stack[$l - 1]['childs']);
                $stack[$l - 1]['childs'][$i] = $c;
                $stack[] = &$stack[$l - 1]['childs'][$i];
            }
        }
        return $result;
    }

    protected function getFeaturesFilter() {
        $feature_model = new shopFeatureModel();
        $features = $feature_model->getFeatures(true, null, 'id');

        $collection = new shopProductsCollection();

        $filter_ids = array();
        foreach ($features as $feature) {
            $filter_ids[] = $feature['id'];
        }

        $feature_model = new shopFeatureModel();
        $features = $feature_model->getById(array_filter($filter_ids, 'is_numeric'));
        if ($features) {
            $features = $feature_model->getValues($features);
        }
        $category_value_ids = $collection->getFeatureValueIds();

        $filters = array();
        foreach ($filter_ids as $fid) {
            if ($fid == 'price') {
                $range = $collection->getPriceRange();
                if ($range['min'] != $range['max']) {
                    $filters['price'] = array(
                        'min' => shop_currency($range['min'], null, null, false),
                        'max' => shop_currency($range['max'], null, null, false),
                    );
                }
            } elseif (isset($features[$fid]) && isset($category_value_ids[$fid])) {
                $filters[$fid] = $features[$fid];
                $min = $max = $unit = null;
                foreach ($filters[$fid]['values'] as $v_id => $v) {
                    if (!in_array($v_id, $category_value_ids[$fid])) {
                        unset($filters[$fid]['values'][$v_id]);
                    } else {
                        if ($v instanceof shopRangeValue) {
                            $begin = $this->getFeatureValue($v->begin);
                            if ($min === null || $begin < $min) {
                                $min = $begin;
                            }
                            $end = $this->getFeatureValue($v->end);
                            if ($max === null || $end > $max) {
                                $max = $end;
                                if ($v->end instanceof shopDimensionValue) {
                                    $unit = $v->end->unit;
                                }
                            }
                        } else {
                            $tmp_v = $this->getFeatureValue($v);
                            if ($min === null || $tmp_v < $min) {
                                $min = $tmp_v;
                            }
                            if ($max === null || $tmp_v > $max) {
                                $max = $tmp_v;
                                if ($v instanceof shopDimensionValue) {
                                    $unit = $v->unit;
                                }
                            }
                        }
                    }
                }
                if (!$filters[$fid]['selectable'] && ($filters[$fid]['type'] == 'double' ||
                        substr($filters[$fid]['type'], 0, 6) == 'range.' ||
                        substr($filters[$fid]['type'], 0, 10) == 'dimension.')) {
                    if ($min == $max) {
                        unset($filters[$fid]);
                    } else {
                        $type = preg_replace('/^[^\.]*\./', '', $filters[$fid]['type']);
                        if ($type != 'double') {
                            $filters[$fid]['base_unit'] = shopDimension::getBaseUnit($type);
                            $filters[$fid]['unit'] = shopDimension::getUnit($type, $unit);
                            if ($filters[$fid]['base_unit']['value'] != $filters[$fid]['unit']['value']) {
                                $dimension = shopDimension::getInstance();
                                $min = $dimension->convert($min, $type, $filters[$fid]['unit']['value']);
                                $max = $dimension->convert($max, $type, $filters[$fid]['unit']['value']);
                            }
                        }
                        $filters[$fid]['min'] = $min;
                        $filters[$fid]['max'] = $max;
                    }
                }
            }
        }

        return $filters;
    }

    protected function getFeatureValue($v) {
        if ($v instanceof shopDimensionValue) {
            return $v->value_base_unit;
        }
        if (is_object($v)) {
            return $v->value;
        }
        return $v;
    }

}
