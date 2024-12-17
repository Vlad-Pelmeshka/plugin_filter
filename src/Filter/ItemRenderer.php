<?php namespace Premmerce\Filter\Filter;

use Premmerce\Filter\FilterPlugin;
use Premmerce\SDK\V2\FileManager\FileManager;
use Premmerce\Filter\Filter\Items\Types\TaxonomyFilter;

class ItemRenderer
{
    /**
     * File Manager
     *
     * @var FileManager
     */
    private $fileManager;

    /**
     * ItemRenderer constructor.
     *
     * @param FileManager $fileManager
     */
    public function __construct($fileManager)
    {
        $this->fileManager = $fileManager;

        add_action('premmerce_filter_render_item_checkbox', array($this, 'renderCheckbox'), 10, 2);
        add_action('premmerce_filter_render_item_radio', array($this, 'renderRadio'), 10, 2);
        add_action('premmerce_filter_render_item_select', array($this, 'renderSelect'), 10, 2);
        add_action('premmerce_filter_render_item_slider', array($this, 'renderSlider'), 10, 2);

        //This IF block will be auto removed from the Free version.
        if (premmerce_pwpf_fs()->is__premium_only()) {
            if (premmerce_pwpf_fs()->can_use_premium_code()) {
                add_action('premmerce_filter_render_item_label', array($this, 'renderLabel__premium_only'), 10, 2);
                add_action('premmerce_filter_render_item_color', array($this, 'renderColor__premium_only'), 10, 2);
                add_action('premmerce_filter_render_item_image', array($this, 'renderImage__premium_only'), 10, 2);
            }
        }

        add_action('premmerce_filter_render_item_after_title', array($this, 'renderAfterTitle'), 10);
    }

    /**
     * Render Checkbox
     *
     * @param TaxonomyFilter $attribute
     */
    public function renderCheckbox($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/checkbox.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render Radio
     *
     * @param $attribute
     */
    public function renderRadio($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/radio.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render Select
     *
     * @param $attribute
     */
    public function renderSelect($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/select.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render Color
     *
     * @param $attribute
     */
    public function renderColor__premium_only($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/color__premium_only.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render Image
     *
     * @param $attribute
     */
    public function renderImage__premium_only($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/image__premium_only.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render Slider
     *
     * @param $attribute
     */
    public function renderSlider($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/slider.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render Label
     *
     * @param $attribute
     */
    public function renderLabel__premium_only($attribute)
    {
        $this->fileManager->includeTemplate(
            'frontend/types/label__premium_only.php',
            array(
            'attribute' => $attribute
            )
        );
    }

    /**
     * Render After Title
     *
     * @param $attribute
     */
    public function renderAfterTitle($attribute)
    {
        $this->fileManager->includeTemplate('frontend/parts/dropdown-handle.php', array('attribute' => $attribute));
    }

    /**
     * Render Recursive Children
     *
     * @param FileManager    $fileManager
     * @param \WP_Term       $term
     * @param TaxonomyFilter $attribute
     * @param $isRootChecked
     * @param $rootId
     * @param bool           $isParentChecked
     */
    public static function renderRecursiveChildren(
        FileManager $fileManager,
        $term,
        $attribute,
        $isRootChecked,
        $rootId,
        $isParentChecked = false
    ) {
        if (!empty($term->children) && is_array($term->children) && in_array(
            $attribute->getType(),
            array('checkbox', 'radio')
        )
        ) {
            $settings                = get_option(FilterPlugin::OPTION_SETTINGS, array());
            $expandCategoryHierarchy = !empty($settings['expand_category_hierarchy']);
            foreach ($term->children as $child) {
                if (0 === $child->count && !empty($settings['hide_empty'])) {
                    continue;
                }

                $fileManager->includeTemplate(
                    "frontend/types/parts/{$attribute->getType()}.php",
                    array(
                        'term' => $child,
                        'attribute' => $attribute,
                        'expandCategoryHierarchy' => $expandCategoryHierarchy,
                        'isExpanded' => $expandCategoryHierarchy || $isRootChecked || $isParentChecked,
                        'isRootChecked' => $isRootChecked,
                        'isParentChecked' => $isParentChecked,
                        'rootId' => $rootId
                    )
                );
            }
        }
    }
}
