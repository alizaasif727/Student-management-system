<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;

$items = [
    [
        'label' => 'Home',
        'url' => ['/site/index'],
    ],
    [
        'label' => 'Students',
        'url' => ['/student/index'],
    ],
    [
        'label' => 'Courses',
        'url' => ['/course/index'],
    ],
    [
        'label' => 'Marks',
        'url' => ['/mark/index'],
    ],
    [
        'label' => 'Attendance',
        'url' => ['/attendance/index'],
    ],
     [
        'label' => 'AI Assistant',
        'url' => ['/ai/index'],
    ],
];

?>
<header id="header">
    <?php NavBar::begin(
        [
            'brandLabel' => 'Student Management System',
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top']
        ],
    ) ?>
    <?= Nav::widget(
        [
            'options' => ['class' => 'navbar-nav me-auto'],
            'encodeLabels' => false,
            'items' => $items,
        ],
    ) ?>
    <?= Html::button(
        '&#127769;',
        [
            'id' => 'theme-toggle',
            'class' => 'btn btn-link nav-link fs-5',
            'aria-label' => 'Switch to dark mode',
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>
