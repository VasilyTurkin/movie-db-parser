<?php

namespace Src;

class Test
{
    public function test(): string
    {
        return "Test";
    }
}

$string = new Test();
echo $string->test();
