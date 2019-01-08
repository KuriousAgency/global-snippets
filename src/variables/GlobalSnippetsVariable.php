<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets\variables;

use kuriousagency\globalsnippets\GlobalSnippets;

use Craft;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     2.0.0
 */
class GlobalSnippetsVariable
{
    // Public Methods
    // =========================================================================

    /**
     * @param null $optional
     * @return string
     */
    public function exampleVariable($optional = null)
    {
        $result = "And away we go to the Twig template...";
        if ($optional) {
            $result = "I'm feeling optional today...";
        }
        return $result;
    }
}
