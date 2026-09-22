<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "attendance".
 *
 * @property int $id
 * @property int $student_id
 * @property string $attendance_date
 * @property string $status
 * @property string|null $remarks
 *
 * @property Students $student
 */
class Attendance extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'attendance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['remarks'], 'default', 'value' => null],
            [['student_id', 'attendance_date', 'status'], 'required'],
            [['student_id'], 'integer'],
            [['attendance_date'], 'safe'],
            [['status'], 'string', 'max' => 20],
            [['remarks'], 'string', 'max' => 255],
            [['student_id'], 'exist', 'skipOnError' => true, 'targetClass' => Students::class, 'targetAttribute' => ['student_id' => 'id']],
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
            'attendance_date' => 'Attendance Date',
            'status' => 'Status',
            'remarks' => 'Remarks',
        ];
    }

    /**
     * Gets query for [[Student]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStudent()
    {
        return $this->hasOne(Student::class, ['id' => 'student_id']);
    }

}
