<?php
    require_once(__DIR__ . '/globals.php');
    require_once(__DIR__ . '/includes/classes/Template.php');

    $view = new Template('default');

    $view->title = 'title';
    $view->stylePath = $view->getStylePath();

    $view->render('index.php');
?>