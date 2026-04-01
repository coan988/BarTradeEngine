<?php
class Test{
    function __construct(){
    }

    function domath(){
        $result = 15;
        return $result;
    }

    function run(){
        $new_result = $this->domath() + 5;
        return $new_result;
    }
}

echo("test");
$test = new Test;
echo($test->run());