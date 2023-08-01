<?php

namespace ThemeHouse\UIX\Listener\Pub;

use XF\Template\Templater;

class TemplaterTemplatePreRender
{
    public static function pageContainer(Templater $templater, &$type, &$template, array &$params)
    {
        if (!\XF::visitor()->user_id && (\XF::app()->style(\XF::visitor()->style_id)->getProperty('uix_loginStyle') == 'slidingPanel')) {
            $conAccProvider = \XF::repository('XF:ConnectedAccount')->getUsableProviders(false);
            $params['uixConAccProviders'] = $conAccProvider;
        }
    }
}
