<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "marks".
 *
 * @property int $id
 * @property int $student_id
 * @property int $course_id
 * @property int $marks
 * @property int $total_marks
 */
class Mark extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'marks';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['student_id', 'course_id', 'marks', 'total_marks'], 'required'],
            [['student_id', 'course_id', 'marks', 'total_marks'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'student_id' => 'Student ID',
            'course_id' => 'Course ID',
            'marks' => 'Marks',
            'total_marks' => 'Total Marks',
        ];
    }
    public function getStudent()
{
    return $this->hasOne(Student::class, ['id' => 'student_id']);
}

public function getCourse()
{
    return $this->hasOne(Course::class, ['id' => 'course_id']);
}

}
