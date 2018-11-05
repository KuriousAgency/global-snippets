<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Previous hardcoded template snippets
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\globalsnippets\variables;

use kuriousagency\globalsnippets\GlobalSnippets;
use yii\di\ServiceLocator;

use Craft;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class GlobalSnippetsVariable extends ServiceLocator
{
    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
		$components = GlobalSnippets::$plugin->components;
		unset($components['migrator']);
        $config['components'] = $components;
        parent::__construct($config);
    }
    
    /**
     * Call {{ craft.globalSnippets.groups.[group].[snippet] }} to return
     * the related snippet content.
     */
    public function groups()
    {
        $service = GlobalSnippets::$plugin->snippets;
        $groups = $service->getAllSnippetGroups();
        $return_array = [];
        foreach ($groups as $group){
            $snippets = $service->getSnippetsByGroup($group['id']);
            $snippet_array = [];
            foreach ($snippets as $snippet){
                $snippet_array[$snippet['handle']] = $snippet['content'];
            }
            $return_array[$group['handle']] = $snippet_array;
        }
        return $return_array;
    }
}
