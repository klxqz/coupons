<?php

class shopCouponsPluginSettingsGetCategoriesController extends waJsonController
{

    public function execute()
    {
        try {
            $category_id = waRequest::post('category_id', 0, waRequest::TYPE_INT);
            $view = wa()->getView();
            $view->assign(array(
                'categories' => shopCouponsHelper::getCategories($category_id),
            ));
            $this->response['html'] = $view->fetch(wa()->getAppPath('/plugins/coupons/templates/actions/settings/include.categories.html', 'shop'));
        } catch (Exception $ex) {
            $this->setError($ex->getMessage());
        }
    }

}
