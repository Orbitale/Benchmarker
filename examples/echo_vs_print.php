<?php

declare(strict_types=1);

return (function(){
    yield 'simple' => function(){
        $a = 'quote';
    };
    yield 'double' => function(){
        $a = "quote";
    };
})();
