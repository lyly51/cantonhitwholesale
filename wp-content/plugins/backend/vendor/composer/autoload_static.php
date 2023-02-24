<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit24dcf488ed7a0bee6eca2d95490eea6b
{
    public static $prefixLengthsPsr4 = array (
        'C' => 
        array (
            'CTHWP\\Api\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'CTHWP\\Api\\' => 
        array (
            0 => __DIR__ . '/../..' . '/api',
        ),
    );

    public static $classMap = array (
        'CTHWP\\Api\\Models\\Sellers' => __DIR__ . '/../..' . '/api/Models/Sellers.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit24dcf488ed7a0bee6eca2d95490eea6b::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit24dcf488ed7a0bee6eca2d95490eea6b::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit24dcf488ed7a0bee6eca2d95490eea6b::$classMap;

        }, null, ClassLoader::class);
    }
}
