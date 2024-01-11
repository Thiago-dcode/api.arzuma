<?php

namespace App\Intranet\Utils;



class TreeBuilder {

   private static function setBranch($item, $name, $id, $level = 0)
    {
        $sticks = '';
        for ($i = 0; $i > $level; $i--) {
            $sticks .= '|';
        }
        $sticks .= '-';

        return [
            'name' => $sticks . $item[$name],
            'id' => $item[$id],
            'order' => isset($item['ORDEN'])?? $item['ORDEN'],
            'children' => []
        ];
    }
    private static function recursive($level, $tree, $id, $parentId, $item, $name)
    {

        $_tree = $tree;
        foreach ($tree as $key => $branch) {

            if (isset($branch['id']) && $branch['id'] === $item[$parentId]) {

                array_push($_tree[$key]['children'], self::setBranch($item, $name, $id, $level -1));
                continue;
            }
            if (!isset($branch['children'])  || !$branch['children']) {

                continue;
            } else {



                $_tree[$key]['children'] = self::recursive($level - 1, $_tree[$key]['children'], $id, $parentId, $item, $name);
            }
        }
        return $_tree;
    }

    public static function build($data, $id = 'id', $parentId = 'parentId', $name = 'name', $valueOfParentId = null)
    {
       
        $tree = [];

        foreach ($data as $key => $item) {

            if (!$item[$parentId] || $item[$parentId] === $valueOfParentId) {

                array_push($tree, self::setBranch($item, $name, $id,-1));
                continue;
            }

            $tree = self::recursive(-1, $tree, $id, $parentId, $item, $name);
        }
        return $tree;
    }


}

