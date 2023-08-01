<?php

namespace ThemeHouse\UIX\XF;

/**
 * Class CssRenderer
 * @package ThemeHouse\UIX\XF
 */
class CssRenderer extends XFCP_CssRenderer
{
    /**
     * @param array $templates
     * @param array $cached
     * @param array|null $errors
     * @return string
     */
    protected function renderTemplates(array $templates, array $cached = [], array &$errors = null)
    {
        if ($extraTemplates = $this->style->getProperty('uix_additionalCss')) {
            $extraTemplates = explode(',', $extraTemplates);
            $extraTemplates[] = 'extra.less';

            $extraTemplates = array_map(function ($template) {
                return 'public:' . $template;
            }, $extraTemplates);

            usort($templates, function($a, $b) use ($extraTemplates) {
                if(in_array($a, $extraTemplates)) {
                    if(in_array($b, $extraTemplates)) {
                        return array_search($a, $extraTemplates) >= array_search($b, $extraTemplates) ? 1 : -1;
                    }

                    return 1;
                }

                return -1;
            });
        }

        return parent::renderTemplates($templates, $cached, $errors);
    }
}
