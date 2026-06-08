<?php

namespace app\models\search;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\User;

class UserSearch extends User
{
    public $created_at_from;
    public $created_at_to;

    public function rules()
    {
        return [
            [['id', 'status', 'created_at', 'updated_at'], 'integer'],
            [['username', 'email', 'avatar'], 'safe'],
            [['created_at_from', 'created_at_to'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = User::find()->orderBy(['created_at' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 20],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'username', $this->username])
              ->andFilterWhere(['like', 'email', $this->email])
              ->andFilterWhere(['like', 'avatar', $this->avatar]);

        if ($this->created_at_from) {
            $query->andFilterWhere(['>=', 'created_at', strtotime($this->created_at_from)]);
        }
        if ($this->created_at_to) {
            $query->andFilterWhere(['<=', 'created_at', strtotime($this->created_at_to . ' 23:59:59')]);
        }

        return $dataProvider;
    }
}