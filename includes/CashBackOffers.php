<?php

/**
 * Copyright 2015: Hotshopper B.V.
 * User: eboin
 * Date: 19-7-16
 * Time: 12:48
 */
class CashBackOffers
{


    public static function showOffers($content){
        global $post;
        $option = get_option( 'c247_show_offers' );
        $disableOffers = get_post_meta( $post->ID, 'c247_disable_offers', true );
        $post_id = $post->ID;
        $html = "";
        if($option == true && !is_front_page() && $disableOffers != '1'){
            $call = CashBackApi::get( 'getLatestOffers',array('limit' => 4,'token' => get_option('c247_token')));
            $offers = json_decode($call,true);
            if(!empty($offers) && !empty($post_id)){
                $html = "<div class='c247-offer-wrapper'><div class='offer-header'>Deals:</div>";
                foreach($offers AS $offer){
                    $html .= "<div class='c247-offer-item'>";
                    $html .= "<div class='c247-offer-item-image'><img class='c247-clickout-link' data-id='{$offer['id']}' data-href='https://www.247discount.nl/{$offer['url']}/' src='{$offer['image']}' alt=' '/><br/><span class='c247-offer-retailer'>{$offer['retailer']}</span></div>";
                    $html .= "<div class='c247-offer-item-title'><a target='_blank' data-id='{$offer['id']}' data-type='offer' class='c247-clickout-link' href='https://www.247discount.nl/{$offer['url']}/'>{$offer['title']}</a></div>";
                    $html .= "</div>";
                }
                $html .= "<div class='clear'></div></div>";
            }
        }

        return $content = $content . $html;
    }
}