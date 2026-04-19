<?php


class Clock {
    function run(){
        $c = 12;
        $result = (new Test())->test($c);
        return $result;
    }
}

class Test {
    function test(int $c){
        $a = 1234;
        $b = $a + $c;
        return $b;
    }
}
$test = new Clock();
var_dump($test->run());