<?php
header('Content-type:application/json;charset=utf-8');
$output = array(
        "slug"=>"plugin_test",
        "name"=>"Plugin test",
        "plugin_name"=>"plugin_test",
        "new_version"=>"0.1.0",
        "url"=>"http://www.xxxxxxxxxxxx.com/",
        "package"=>"http://www.xxxxxxxxxxxx.com/plugin_test.zip"
        );
echo json_encode($output);
