<?php
/**
 * DLE Billing
 *
 * @link          https://github.com/evgeny-tc/dle-billing-module
 * @author        dle-billing.ru <evgeny.tc@gmail.com>
 * @copyright     Copyright (c) 2012-2023
 */

function genCode()
{
    $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
    $numChars = strlen($chars);
    $string = '';

    for ($i = 0; $i < 10; $i++)
    {
        $string .= substr($chars, rand(1, $numChars) - 1, 1);
    }

    return $string;
}

function copy_folder($d1, $d2, $upd = true, $force = true)
{
    if ( is_dir( $d1 ) )
    {
        $d2 = mkdir_safe( $d2, $force );

        $d = dir( $d1 );

        while ( false !== ( $entry = $d->read() ) )
        {
            if ( $entry != '.' && $entry != '..' )
                copy_folder( "$d1/$entry", "$d2/$entry", $upd, $force );
        }
        $d->close();
    }
    else
    {
        if( ! copy_safe( $d1, $d2, $upd ) )
            return false;
    }

    return true;
}

function mkdir_safe( $dir, $force )
{
    if (file_exists($dir))
    {
        if (is_dir($dir)) return $dir;
        else if (!$force) return false;
        unlink($dir);
    }
    return (mkdir($dir, 0777, true)) ? $dir : false;
}

function copy_safe ($f1, $f2, $upd)
{
    $time1 = filemtime($f1);

    if (file_exists($f2))
    {
        $time2 = filemtime($f2);

        if ($time2 >= $time1 && $upd) return false;
    }

    $ok = copy($f1, $f2);

    if ($ok)
    {
        touch($f2, $time1);
    }

    return $ok;
}
