<?php
$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/config')
    ->in(__DIR__ . '/tests')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules(array(
        '@Symfony' => true,
        'align_multiline_comment' => true,
        'array_indentation' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_superfluous_phpdoc_tags' => false,
    ))
    ->setFinder($finder);
