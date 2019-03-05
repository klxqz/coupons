<?php

class shopCouponsHelper
{

    public static function getCategories($id = 0)
    {
        $category_model = new shopCategoryModel();
        $route = null;

        $depth = 1;
        if ($id) {
            $category = $category_model->getById($id);
            if ($category) {
                $depth = $category['depth'] + 2;
            }
        }


        $cats = $category_model->getTree($id, $depth);
        unset($cats[$id]);


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

}
