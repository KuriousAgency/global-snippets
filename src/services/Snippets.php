<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Previous hardcoded template snippets
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\globalsnippets\services;

use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\models\Snippet;
use kuriousagency\globalsnippets\models\SnippetGroup;
use kuriousagency\globalsnippets\records\Snippets as SnippetRecord;
use kuriousagency\globalsnippets\records\SnippetGroup as SnippetGroupRecord;


use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\db\Command;
use yii\base\Exception;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class Snippets extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Get a snippet by its ID.
     *
     * @param int $id
     */
    public function getSnippetById($id)
    {
        $result = $this->_createSnippetQuery()
            ->where(['id' => $id])
            ->one();

        return $result ? new Snippet($result) : null;
    }
        /**
     * Get a snippet by its Handle.
     *
     * @param int $id
     */
    public function getSnippetByHandle($handle)
    {
        $result = $this->_createSnippetQuery()
            ->where(['handle' => $handle])
            ->one();

        return $result ? new Snippet($result) : null;
    }
    /**
     * Get all snipepts.
     *
     */
    public function getAllSnippets(): array
    {
        $rows = $this->_createSnippetQuery()->all();

        $snippets = [];
        foreach ($rows as $row) {
            $snippets[] = new Snippet($row);
        }

        return $snippets;
    }
    /**
     * Get snippets by group.
     *
     */
    public function getSnippetsByGroup($groupId): array
    {
        $rows = $this->_createSnippetQuery()
            ->where(['snippetGroup' => $groupId])
            ->all();

        $snippets = [];
        foreach ($rows as $row) {
            $snippets[] = new Snippet($row);
        }

        return $snippets;
    }
    /**
     * Get all snippet groups.
     *
     */
    public function getAllSnippetGroups(): array
    {
        $query = (new Query())
            ->select([
                'groups.id',
                'groups.name',
                'groups.handle',
            ])
            ->orderBy('name')
            ->from(['{{%globalsnippets_groups}} groups'])
            ->all();

        $groups = [];
        foreach ($query as $row) {
            $groups[$row['id']] = array(
                'id' => $row['id'],
                'name' => $row['name'],
                'handle' => $row['handle']
            );
        }
        // Craft::dd($groups);
        return $groups;
    }

    public function saveSnippetGroup(SnippetGroup $model)
    {
        $isNewGroup = !$model->id;
        if ($model->id) {
            $record = SnippetGroupRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce', 'No snippet exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new SnippetGroupRecord();
        }
        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->save(false);

        if ($isNewGroup) {
            $model->id = $record->id;
        }
        return true;
    }
            
    /**
     * Save a snippet.
     *
     * @param Snippet $model
     * @return bool
     * @throws \Exception
     */
    public function saveSnippet(Snippet $model, bool $runValidation = true): bool
    {
        if ($model->id) {
            $record = SnippetRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('commerce', 'No snippet exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new SnippetRecord();
        }

        $record->name = $model->name;
        $record->snippetGroup = $model->snippetGroup;
        $record->content = $model->content;
        $record->handle = $model->handle;
        $record->instruction = $model->instruction;

        $record->save(false);

        // Now that we have a record ID, save it on the model
        $model->id = $record->id;

        return true;
    }

    public function deleteSnippetById($id)
    {
        $snippet = SnippetRecord::findOne($id);

        if ($snippet) {
            return $snippet->delete();
        }

        return false;
    }
    public function deleteSnippetsByGroup($groupId)
    {
        $snippets = $this->getSnippetsByGroup($groupId);
        foreach ($snippets as $model){
            if ($model){
                $record = SnippetRecord::findOne($model->id);
                $record->delete();
            }
        }
    }

    public function deleteSnippetGroupById($id)
    {
        $group = SnippetGroupRecord::findOne($id);
        if ($group) {
            $this->deleteSnippetsByGroup($id);
            return $group->delete();
        }
        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving Emails.
     *
     * @return Query
     */
    private function _createSnippetQuery(): Query
    {
        return (new Query())
            ->select([
                'snippets.id',
                'snippets.name',
                'snippets.content',
                'snippets.handle',
                'snippets.snippetGroup',
                'snippets.instruction',
            ])
            ->orderBy('name')
            ->from(['{{%globalsnippets_snippets}} snippets']);
    }

    /**
     * Gets a snippet group record or creates a new one.
     * 
     * @param SnippetGroup $group
     * @return SnippetGroupREcord
     */
    private function _getGroupRecord(SnippetGroup $group)
    {
        if ($group->id) {
            $groupRecord = SnippetGroupRecord::findOne($group->id);

            if (!$groupRecord) {
                // throw new Exception("No field group exists with the ID '{$group->id}'");
            }
        } else {
            $groupRecord = new SnippetGroupRecord();
        }

        return $groupRecord;
    }
}
