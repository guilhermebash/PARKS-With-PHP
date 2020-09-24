<?php

    include __DIR__ . '/Parks.php';

    $parks = new Parks('xxx.xxx.xx.x', 'admin', 'parks');

    $onu_info = $parks->getOnuInfo($name_or_alias);

    var_dump($onu_info);
    exit;