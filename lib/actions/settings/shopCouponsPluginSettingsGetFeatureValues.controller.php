<?php

class shopCouponsPluginSettingsGetFeatureValuesController extends waJsonController
{

    public function execute()
    {
        try {
            $feature_id = waRequest::post('feature_id', 0, waRequest::TYPE_INT);

            $feature_model = new shopFeatureModel();
            $features = $feature_model->getByField('id', $feature_id, 'id');
            $feature = array();
            if ($features) {
                $features = $feature_model->getValues($features);
                $feature = array_shift($features);
            }

            $view = wa()->getView();
            $view->assign(array(
                'feature' => $feature,
            ));
            $this->response['html'] = $view->fetch(wa()->getAppPath('/plugins/coupons/templates/actions/settings/include.featureValues.html', 'shop'));
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

}
