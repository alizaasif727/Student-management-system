<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\models\Attendance $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="attendance-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'student_id')->dropDownList(
    ArrayHelper::map(\app\models\Student::find()->all(), 'id', 'name'),
    ['prompt' => 'Select Student']
) ?>

    <?= $form->field($model, 'attendance_date')->textInput() ?>

    <?= $form->field($model, 'status')->dropDownList([
    'Present' => 'Present',
    'Absent' => 'Absent',
], ['prompt' => 'Select Status']) ?>

    <?= $form->field($model, 'remarks')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
