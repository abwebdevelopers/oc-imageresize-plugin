<?php

Route::get('test2', function() {
    $resizer = new ABWebDevelopers\ImageResize\Classes\Resizer('oh no, doesnt exist');
    $resizer->resize(800, 250);
    $resizer->render();
});