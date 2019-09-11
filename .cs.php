<?php

return PhpCsFixer\Config::create()
  ->setUsingCache(false)
  ->setRiskyAllowed(true)
  ->setRules(array(
    '@PSR1' => true,
    '@PSR2' => true,
  ))
  ->setFinder(PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true));
