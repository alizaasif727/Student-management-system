<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "students".
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $age
 * @property string|null $department
 */
class Student extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'students';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['age', 'department'], 'default', 'value' => null],
            [['name', 'email'], 'required'],
            [['age'], 'integer'],
            [['name', 'email', 'department'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'age' => 'Age',
            'department' => 'Department',
        ];
    }

}
