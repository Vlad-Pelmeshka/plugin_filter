<?php namespace Premmerce\Filter\Seo;

class WPMLHelper
{
    public static function getCurrentLanguage()
    {
        return defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : null;
    }

    public static function joinTermWithWPMLCurrentTranslation($termIdColumn = 'r.term_id', $joinType = 'INNER')
    {
        $condition = '';
        if (self::getCurrentLanguage()) {
            global $wpdb;
            $translations    = $wpdb->prefix . 'icl_translations';
            $currentLanguage = self::getCurrentLanguage();
            $condition       = "{$joinType} JOIN $translations as t ON $termIdColumn = t.element_id AND t.language_code = '{$currentLanguage}' AND t.element_type like 'tax_%'";
        }

        return $condition;
    }
}
