<?php

declare(strict_types=1);

return (function(){
    yield 'echo' => function(){
        echo 'echo';
    };
    yield 'print' => function(){
        print 'print';
    };
})();
