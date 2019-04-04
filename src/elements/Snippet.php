<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets\elements;

use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\elements\db\SnippetQuery;
use kuriousagency\globalsnippets\records\Snippet as SnippetRecord;


use Craft;
use craft\base\Element;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\UrlHelper;
use craft\helpers\Json;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     2.0.0
 */
class Snippet extends Element
{
	const CONFIG_SNIPPET_KEY = 'snippets.snippet';

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $id;
    public $name = '';
    public $handle = '';
    public $snippetGroupId;
	public $instruction = '';
	public $pcuid;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('global-snippets', 'Snippet');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public static function find(): ElementQueryInterface
    {
        return new SnippetQuery(static::class);
    }

    /**
     * Defines the sources that elements of this type may belong to.
     *
     * @param string|null $context The context ('index' or 'modal').
     *
     * @return array The sources.
     * @see sources()
     */
    protected static function defineSources(string $context = null): array
    {
        $groups = GlobalSnippets::$plugin->snippets->getAllSnippetGroups();
        $sources = [];
        foreach ($groups as $group) {
            $sources[] = [
                'key' => $group->handle,
                'label' => $group->name,
                'criteria' => [
                    'snippetGroupId' => $group->id,
                ]
            ];
        }
        
        return $sources;
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getIsEditable(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldLayout()
    {
        return Craft::$app->fields->getLayoutByType(Snippet::class.'\\'.$this->handle);
    }

    /**
     * @inheritdoc
     */
    public function getGroup()
    {
        if ($this->groupId === null) {
            throw new InvalidConfigException('Tag is missing its group ID');
        }

        if (($group = Craft::$app->getTags()->getTagGroupById($this->groupId)) === null) {
            throw new InvalidConfigException('Invalid tag group ID: '.$this->groupId);
        }

        return $group;
    }

    // Indexes, etc.
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function getEditorHtml(): string
    {
        $html = Craft::$app->getView()->renderTemplateMacro('_includes/forms', 'textField', [
            [
                'label' => Craft::t('app', 'Title'),
                'siteId' => $this->siteId,
                'id' => 'title',
                'name' => 'title',
                'value' => $this->title,
                'errors' => $this->getErrors('title'),
                'first' => true,
                'autofocus' => true,
                'required' => true
            ]
        ]);

        $html .= parent::getEditorHtml();

        return $html;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function beforeSave(bool $isNew): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
		if (!$isNew) {
            $record = SnippetRecord::findOne($this->id);
            if (!$record) {
                throw new Exception('Invalid Snippet ID: ' . $this->id);
            }
        } else {
            $record = new SnippetRecord();
            $record->id = $this->id;
		}
		//Craft::dd($this->pcuid);
        $record->name = $this->name;
        $record->handle = $this->handle;
        $record->instruction = $this->instruction;
		$record->snippetGroupId = $this->snippetGroupId;
		$record->uid = $this->pcuid ?? $this->uid;
        $record->save(false);
		$this->id = $record->id;
		//Craft::dd($record->uid);

		$projectConfig = Craft::$app->getProjectConfig();
		$configData = [
			'name' => $record->name,
			'handle' => $record->handle,
			'instruction' => $record->instruction,
			'snippetGroupId' => $record->snippetGroupId,
		];
		$configPath = self::CONFIG_SNIPPET_KEY . '.' . $record->uid;
		$currentConfig = $projectConfig->get($configPath);
		if (Json::encode($currentConfig) != Json::encode($configData)) {
			$projectConfig->set($configPath, $configData);
		}

        return parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function beforeDelete(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
    }

    /**
     * @inheritdoc
     */
    public function beforeMoveInStructure(int $structureId): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function afterMoveInStructure(int $structureId)
    {
    }

    

    /**
     * Defines the column titles on element listing page.
     *
     * @return array The column titles
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'name' => 'Name',
            'handle' => 'Handle'         
        ];
    }

    protected function tableAttributeHtml(string $attribute): string
    {
        $user = Craft::$app->getUser();
        $admin = $user->isAdmin;
        // switch ($attribute) {
        //     case 'test': {
        //         $id = $this->id;
        //         $url = 'email-editor/test/'.$id;
        //         $html = '<div class="buttons">
        //                     <a href="'.$url.'" class="btn icon submit">Test</a>
        //                 </div>';

        //         return $url ? $html : '';
        //     }
        // }
        return parent::tableAttributeHtml($attribute);
    }

    public function getCpEditUrl()
    {
        // The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
        $url = UrlHelper::cpUrl('global-snippets/settings/snippet/' . $this->id);

        if (Craft::$app->getIsMultiSite()) {
            $url .= '/' . $this->getSite()->handle;
        }

        return $url;
    }
}
