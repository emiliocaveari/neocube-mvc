<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit82a81c6ce286b8580551469111b59d13
{
    public static $prefixLengthsPsr4 = array (
        'N' => 
        array (
            'NeoCube\\' => 8,
        ),
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'NeoCube\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/NeoCube',
        ),
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src/App',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit82a81c6ce286b8580551469111b59d13::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit82a81c6ce286b8580551469111b59d13::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit82a81c6ce286b8580551469111b59d13::$classMap;

        }, null, ClassLoader::class);
    }
}
