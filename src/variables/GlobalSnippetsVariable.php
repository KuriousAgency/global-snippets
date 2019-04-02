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
     * Provides the snippets to the templates in the format:
     * 
     * {{ craft.GlobalSnippets.[group handle].[snippet handle] }} 
     */
	public function __call($handle, $args)
	{
        $snippets =  GlobalSnippets::$plugin->snippets->getSnippetGroup($handle)->getGroupSnippets();
        $variables = [];
        foreach ($snippets as $snippet){
            $variables[$snippet->handle] = $snippet->content;
        }
        return $variables;
	}
}
