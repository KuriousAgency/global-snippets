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

use Craft;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class GlobalSnippetsVariable
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
}
