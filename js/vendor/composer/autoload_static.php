<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4883198092b0881fd5e00525176f83c3
{
    public static $classMap = array (
        'GoogleClosureCompiler\\Bridges\\Nette\\GoogleClosureCompilerExtension' => __DIR__ . '/..' . '/machy8/google-closure-compiler/src/Bridges/Nette/GoogleClosureCompilerExtension.php',
        'GoogleClosureCompiler\\CompileException' => __DIR__ . '/..' . '/machy8/google-closure-compiler/src/Compiler/exceptions.php',
        'GoogleClosureCompiler\\Compiler' => __DIR__ . '/..' . '/machy8/google-closure-compiler/src/Compiler/Compiler.php',
        'GoogleClosureCompiler\\Response' => __DIR__ . '/..' . '/machy8/google-closure-compiler/src/Compiler/Response.php',
        'GoogleClosureCompiler\\SetupException' => __DIR__ . '/..' . '/machy8/google-closure-compiler/src/Compiler/exceptions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit4883198092b0881fd5e00525176f83c3::$classMap;

        }, null, ClassLoader::class);
    }
}
