<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\ArrayHelper;

/** @var yii\web\View $this */
/** @var app\models\Mark $model */
/** @var yii\widgets\ActiveForm $form */
?>

<div class="mark-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'student_id')->dropDownList(
    ArrayHelper::map(\app\models\Student::find()->all(), 'id', 'name'),
    ['prompt' => 'Select Student']
) ?>

    <?= $form->field($model, 'course_id')->dropDownList(
    ArrayHelper::map(\app\models\Course::find()->all(), 'id', 'course_name'),
    ['prompt' => 'Select Course']
) ?>

    <?= $form->field($model, 'marks')->textInput() ?>

    <?= $form->field($model, 'total_marks')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
