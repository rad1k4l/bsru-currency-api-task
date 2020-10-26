<?php


namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\Link;

class Currency extends ActiveRecord
{

    public static function tableName()
    {
        return "currency";
    }

    public function fields()
    {
        return [
            'id',
            'name',
            'rate'
        ];
    }

//    public function getLinks()
//    {
//        return [
//            Link::REL_SELF => Url::to(['currency/view', 'id' => 12], true),
//        ];
//    }
}