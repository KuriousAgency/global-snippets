<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets\services;

// use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\elements\Snippet;
use kuriousagency\globalsnippets\models\SnippetGroup;
use kuriousagency\globalsnippets\records\Snippet as SnippetRecord;
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
        if (!$id) {
            return null;
        }
        $query = Snippet::find();
        $query->id($id);

        return $query->one();
    }
        /**
     * Get a snippet by its Handle.
     *
     * @param int $id
     */
    public function getSnippetByHandle(string $handle)
    {
        if (!$id) {
            return null;
        }
        $query = Snippet::find();
        $query->handle($handle);

        return $query->one();
    }
    /**
     * Get all snipepts.
     *
     */
    public function getAllSnippets(): array
    {
        $snippetQuery = Snippet::find();
        $snippets = $snippetQuery->orderBy('name')->all();
        return $snippets;
    }
    /**
     * Get snippets by group Id.
     *
     */
    public function getSnippetsByGroup(int $groupId): array
    {
        $snippetQuery = Snippet::find();
        $snippets = $snippetQuery->snippetGroupId($groupId);
        return $snippets->orderBY('name')->all();
    }
    /**
     * Get all snippet groups.
     *
     */
    public function getAllSnippetGroups(): array
    {
        $query = $this->_createGroupQuery()
            ->orderBy('name')
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
     *
     *   public function saveSnippet(Snippet $model, bool $runValidation = true)
     *   {
     *       if ($model->id) {
     *           $record = SnippetRecord::findOne($model->id);
     *           if (!$record) {
     *               throw new Exception(Craft::t('global-snippets', 'No snippet exists with the ID “{id}”', ['id' => $model->id]));
     *           }
     *       } else {
     *           $record = new SnippetRecord();
     *           if ($this->getSnippetByHandle($model->handle)){
     *               return Craft::t('global-snippets', 'A snippet already exists with the handle: “{handle}”', ['handle' => $model->handle]);
     *           }
     *       }
     *       $record->name = $model->name;
     *       $record->snippetGroupId = $model->snippetGroupId;
     *       $record->content = $model->content;
     *       $record->handle = $model->handle;
     *       $record->instruction = $model->instruction;
     *       $record->save(false);
     *       $model->id = $record->id;
     *       return true;
     *   }
     */
    /*public function saveSnippet(Snippet $model, bool $runValidation = true)
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
    }*/

    /**
     * Delete a snippet by Id
     * 
     */
    public function deleteSnippetById($id)
    {
        $snippet = Snippet::findOne($id);

        if ($snippet) {
			Craft::$app->getProjectConfig()->remove(Snippet::CONFIG_SNIPPET_KEY . '.' . $snippet->uid);
            return true;//$snippet->delete();
            //Craft::$app->getElements()->deleteElement($snippet);
            //return true;
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
			//$record = $this->_getSnippetRecord($uid);
			//$id = $record->id;
			//Craft::dd($uid);
			$record = SnippetRecord::findOne(['uid' => $uid]);
			//Craft::dd($record);

			if (!$record) {
				$record = new SnippetRecord();
				$record->name = $data['name'];
				$record->handle = $data['handle'];
				$record->instruction = $data['instruction'];
				$record->snippetGroupId = $data['snippetGroupId'];

				$snippet = new Snippet($record);
				$snippet->pcuid = $uid;
				Craft::$app->getElements()->saveElement($snippet);
			} else {

				$record->name = $data['name'];
				$record->handle = $data['handle'];
				$record->instruction = $data['instruction'];
				$record->snippetGroupId = $data['snippetGroupId'];
				$record->uid = $uid;
				$record->save(false);
			}
			
			//Craft::dd($snippet);

			

			//Craft::$app->getElements()->saveElement($snippet);

			//$record->save(false);
			$transaction->commit();

		} catch (\Throwable $e) {
			$transaction->rollBack();
			throw $e;
		}
	}

	public function handleDeleteSnippet(ConfigEvent $event)
	{
		$uid = $event->tokenMatches[0];
		$record = SnippetRecord::findOne(['uid' => $uid]);

		if (!$record) {
			return;
		}

		$snippet = new Snippet($record);
		//Craft::dd($snippet);

		$db = Craft::$app->getDb();
		$transaction = $db->beginTransaction();

		try {
			Craft::$app->getElements()->deleteElement($snippet);
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
			$snippets = SnippetRecord::findAll(['snippetGroupId' => $record->id]);
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
     * @return SnippetGroupRecord
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

	private function _getSnippetGroupRecord(string $uid): SnippetGroupRecord
	{
		return SnippetGroupRecord::findOne(['uid' => $uid]) ?? new SnippetGroupRecord();
	}
}

