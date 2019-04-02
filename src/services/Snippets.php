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
use craft\events\ConfigEvent;
use craft\helpers\Db;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class Snippets extends Component
{
	const CONFIG_SNIPPET_KEY = 'snippets.snippet';
	const CONFIG_SNIPPET_GROUP_KEY = 'snippets.group';

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
		
		$projectConfig = Craft::$app->getProjectConfig();
		$configData = [
			'name' => $record->name,
			'handle' => $record->handle,
		];
		$configPath = self::CONFIG_SNIPPET_GROUP_KEY . '.' . $record->uid;
		$projectConfig->set($configPath, $configData);

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

        // Now that we have a record ID, save it on the model
		$model->id = $record->id;
		
		$projectConfig = Craft::$app->getProjectConfig();
		$configData = [
			'name' => $record->name,
			'snippetGroup' => $record->snippetGroup,
			'content' => $record->content,
			'handle' => $record->handle,
			'instruction' => $record->instruction,
		];
		$configPath = self::CONFIG_SNIPPET_KEY . '.' . $record->uid;
		$projectConfig->set($configPath, $configData);

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
			Craft::$app->getProjectConfig()->remove(self::CONFIG_SNIPPET_KEY . '.' . $snippet->uid);
            return true;//$snippet->delete();
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
			Craft::$app->getProjectConfig()->remove(self::CONFIG_SNIPPET_GROUP_KEY . '.' . $group->uid);
            //$this->deleteSnippetsByGroup($id);
            return true;//$group->delete();
            //Delete all the snippets in the group
            /*$snippets = $this->getSnippetsByGroup($id);
            foreach ($snippets as $model){
                if ($model){
                    $record = SnippetRecord::findOne($model->id);
                    $record->delete();
                }
            }
            //Delete the group itself
            return $group->delete();*/
        }
        return false;
	}
	


	public function handleChangedSnippet(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$data = $event->newValue;

		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			$record = $this->_getSnippetRecord($uid);

			$record->name = $data['name'];
        	$record->snippetGroup = $data['snippetGroup'];
        	$record->content = $data['content'];
        	$record->handle = $data['handle'];
			$record->instruction = $data['instruction'];
			$record->uid = $uid;

			$record->save(false);
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleDeleteSnippet(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$record = $this->_getSnippetRecord($uid);

		if (!$record->id) {
			return;
		}

		$db = Craft::$app->getDb();
		$transaction = $db->beginTransaction();

		try {
			$record->delete();
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleChangedSnippetGroup(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$data = $event->newValue;

		$transaction = Craft::$app->getDb()->beginTransaction();
		try {
			$record = $this->_getSnippetGroupRecord($uid);

			$record->name = $data['name'];
        	$record->handle = $data['handle'];
			$record->uid = $uid;

			$record->save(false);
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleDeleteSnippetGroup(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$record = $this->_getSnippetGroupRecord($uid);

		if (!$record->id) {
			return;
		}

		$db = Craft::$app->getDb();
		$transaction = $db->beginTransaction();
		
		try {
			$snippets = SnippetRecord::find(['snippetGroup' => $record->handle])->limit(null)->all();
			foreach ($snippets as $snippet)
			{
				$this->deleteSnippetById($snippet->id);
			}

			$record->delete();
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
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
	
	private function _getSnippetRecord(string $uid): SnippetRecord
	{
		return SnippetRecord::findOne(['uid' => $uid]) ?? new SnippetRecord();
	}

	private function _getSnippetGroupRecord(string $uid): SnippetRecord
	{
		return SnippetGroupRecord::findOne(['uid' => $uid]) ?? new SnippetGroupRecord();
	}
}
