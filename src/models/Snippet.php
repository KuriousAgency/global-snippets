<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Previous hardcoded template snippets
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\globalsnippets\models;

use kuriousagency\globalsnippets\GlobalSnippets;

use Craft;
use craft\base\Model;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class Snippet extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $id = null;
    public $name = '';
    public $handle = '';
    public $snippetGroup = '';
    public $instruction = '';
    public $content = '';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['someAttribute', 'string'],
            ['someAttribute', 'default', 'value' => 'Some Default'],
        ];
    }
}