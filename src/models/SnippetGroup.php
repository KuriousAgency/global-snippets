<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets\models;

use kuriousagency\globalsnippets\GlobalSnippets;

use Craft;
use craft\base\Model;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     2.0.0
 */
class SnippetGroup extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $id = null;
    public $name = '';
    public $handle = '';

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
    /**
     * Use the group name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->name;
    }
    /**
     * Return all snippets in the group.
     *
     * @return array
     */
    public function getGroupSnippets(): array
    {
        return GlobalSnippets::$plugin->snippets->getSnippetsByGroup($this->id);
    }
}
