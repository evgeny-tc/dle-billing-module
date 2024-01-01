<?php

if( intval($post_id) )
{
    $_Post = $db->super_query( "SELECT xfields FROM " . USERPREFIX . "_post WHERE id = '{$post_id}'" );

    $paykeysXF = xfieldsdataload( $_Post['xfields'] );

    if( $paykeysXF['paypost_price'] )
    {
        if( str_contains($paykeysXF['paypost_price'], '|') )
        {
            $priceEx1 = explode("\n", $paykeysXF['paypost_price']);

            $priceEx1 = explode("|", $priceEx1[0]);

            $price = intval($priceEx1[2]);
        }
        else
        {
            $price = $paykeysXF['paypost_price'];
        }
    }

    echo $price;
}