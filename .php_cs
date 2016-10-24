<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in('src')
    ;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(array('-phpdoc_no_empty_return', 'header_comment', 'phpdoc_order'))
    ->finder($finder);