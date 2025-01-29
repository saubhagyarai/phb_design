<?php

namespace App;

// Only for debugging
class DebuggerClass
{
    public function d($data): void
    {
        echo ("<pre>");
        var_dump(mb_convert_encoding($data, "UTF-8", "SJIS"));
        echo ("</pre>");
    }

    public function dd($data): void
    {
        echo ("<pre>");
        var_dump(mb_convert_encoding($data, "UTF-8", "SJIS"));
        echo ("</pre>");
        die;
    }
}
