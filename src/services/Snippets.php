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

// use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\models\Snippet;
use kuriousagency\globalsnippets\models\SnippetGroup;
use kuriousagency\globalsnippets\records\Snippets as SnippetRecord;
use kuriousagency\globalsnippets\records\SnippetGroup as SnippetGroupRecord;


use Craft;
use craft\base\Component;
use craft\db\Query;
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
    public function getSnippetById(int $id)
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
    public function getSnippetByHandle(string $handle)
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
     * Get snippets by group Id.
     *
     */
    public function getSnippetsByGroup(int $groupId): array
    {
        $rows = $this->_createSnippetQuery()
            ->where(['snippetGroupId' => $groupId])
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
        $query = $this->_createGroupQuery()
            ->all();
        $groups = [];
        foreach ($query as $row) {
            $groups[$row['id']] = new SnippetGroup($row);
        }
        return $groups;
    }
    
    /**
     * Get Snippet Group Model from handle.
     * @param string
     * @return SnippetGroup
     */
    public function getSnippetGroup(string $handle)
    {
        $query = $this->_createGroupQuery()
        ->where(['handle'=>$handle])
        ->one();

        return $query ? new SnippetGroup($query) : null;
    }

    /**
     * Save a new or rename existing Snippet Group
     */
    public function saveSnippetGroup(SnippetGroup $model)
    {
        $isNewGroup = !$model->id;
        if ($model->id) {
            $record = SnippetGroupRecord::findOne($model->id);

            if (!$record) {
                throw new Exception(Craft::t('global-snippets', 'No group exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new SnippetGroupRecord();
            if ($this->getSnippetGroup($model->handle)){
                return Craft::t('global-snippets', 'A snippet already exists with the handle: “{handle}”', ['handle' => $model->handle]);
            }
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
    public function saveSnippet(Snippet $model, bool $runValidation = true)
    {
        if ($model->id) {
            $record = SnippetRecord::findOne($model->id);
            if (!$record) {
                throw new Exception(Craft::t('global-snippets', 'No snippet exists with the ID “{id}”', ['id' => $model->id]));
            }
        } else {
            $record = new SnippetRecord();
            if ($this->getSnippetByHandle($model->handle)){
                return Craft::t('global-snippets', 'A snippet already exists with the handle: “{handle}”', ['handle' => $model->handle]);
            }
        }
        $record->name = $model->name;
        $record->snippetGroupId = $model->snippetGroupId;
        $record->content = $model->content;
        $record->handle = $model->handle;
        $record->instruction = $model->instruction;
        $record->save(false);
        $model->id = $record->id;
        return true;
    }

    /**
     * Delete a snippet by Id
     * 
     */
    public function deleteSnippetById($id)
    {
        $snippet = SnippetRecord::findOne($id);

        if ($snippet) {
            return $snippet->delete();
        }

        return false;
    }
    
    /**
     * Deletes a snippet group and all associated snippets
     */
    public function deleteSnippetGroupById($id)
    {
        $group = SnippetGroupRecord::findOne($id);
        if ($group) {
            //Delete all the snippets in the group
            $snippets = $this->getSnippetsByGroup($id);
            foreach ($snippets as $model){
                if ($model){
                    $record = SnippetRecord::findOne($model->id);
                    $record->delete();
                }
            }
            //Delete the group itself
            return $group->delete();
        }
        return false;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving Snippets.
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
                'snippets.snippetGroupId',
                'snippets.instruction',
            ])
            ->orderBy('name')
            ->from(['{{%globalsnippets_snippets}} snippets']);
    }

    /**
     * Returns a Query object prepped for retrieving Snippet Groups.
     *
     * @return Query
     */
    private function _createGroupQuery(): Query
    {
        return (new Query())
            ->select([
                'groups.id',
                'groups.name',
                'groups.handle',
            ])
            ->orderBy('name')
            ->from(['{{%globalsnippets_groups}} groups']);
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
                throw new Exception("No field group exists with the ID '{$group->id}'");
            }
        } else {
            $groupRecord = new SnippetGroupRecord();
        }

        return $groupRecord;
    }
}
