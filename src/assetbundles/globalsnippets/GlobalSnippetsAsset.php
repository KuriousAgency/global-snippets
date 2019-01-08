<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets\assetbundles\GlobalSnippets;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     2.0.0
 */
class GlobalSnippetsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@kuriousagency/globalsnippets/assetbundles/globalsnippets/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/GlobalSnippets.js',
        ];

        $this->css = [
            'css/GlobalSnippets.css',
        ];

        parent::init();
    }
}
