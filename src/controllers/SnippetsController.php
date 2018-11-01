<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Previous hardcoded template snippets
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2018 Kurious Agency
 */

namespace kuriousagency\globalsnippets\controllers;

use kuriousagency\globalsnippets\GlobalSnippets;
use kuriousagency\globalsnippets\models\Snippet;
use kuriousagency\globalsnippets\models\SnippetGroup;

use Craft;
use craft\web\Controller;
use yii\web\Response;

/**
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     1.0.0
 */
class SnippetsController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    /**
     * @return mixed
     */
    public function actionIndex(): Response
    {
        $variables = [];
        $variables['snippets'] = GlobalSnippets::$plugin->snippets->getAllSnippets();

        return $this->renderTemplate('global-snippets/index', $variables);
    }
    /**
     * @return mixed
     */
    public function actionSettings($snippetGroup = null): Response
    {
        $variables = [];
        if ($snippetGroup == null){
            $variables['snippets'] = GlobalSnippets::$plugin->snippets->getAllSnippets();
        } else {
            $variables['snippets'] = GlobalSnippets::$plugin->snippets->getSnippetsByGroup($snippetGroup);
        }
        $variables['groups'] = GlobalSnippets::$plugin->snippets->getAllSnippetGroups();

        return $this->renderTemplate('global-snippets/settings', $variables);
    }

    /**
     * @return mixed
     */
    public function actionSave()
    {
        $this->requirePostRequest();
        $request = Craft::$app->getRequest();
        $id = $request->getBodyParam('snippetId');
        if ($id) {
            $snippet = GlobalSnippets::$plugin->snippets->getSnippetById($id);
        } else {
            $snippet = new Snippet();
        }
        // Shared attributes
        $snippet->name = $request->getBodyParam('name', $snippet->name);
        $snippet->handle = $request->getBodyParam('name',$snippet->handle);
        $snippet->snippetGroup = $request->getBodyParam('snippetGroup', $snippet->snippetGroup);
        $snippet->instruction = $request->getBodyParam('instruction', $snippet->instruction);
        $snippet->content = $request->getBodyParam('content', $snippet->content);
        // Save it
        if (GlobalSnippets::$plugin->snippets->saveSnippet($snippet)) {
            Craft::$app->getSession()->setNotice('Snippet saved.');
            return $this->redirectToPostedUrl($snippet);
        } else {
            Craft::$app->getSession()->setError('Couldn’t save snippet.');
        }
        Craft::$app->getUrlManager()->setRouteParams(['snippet' => $snippet]);
        return $result;
    }

    /**
     * edit/create snippets
     */
    public function actionEdit($id = null)
    {
        $variables = [];
        $variables['groups'] = GlobalSnippets::$plugin->snippets->getAllSnippetGroups();
        if ($id) {
            $variables['snippet'] = GlobalSnippets::$plugin->snippets->getSnippetById($id);
        } else {
            $variables['snippet'] = new Snippet;
        }
        return $this->renderTemplate('global-snippets/_edit', $variables);
    }

    public function actionSaveGroup()
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $group = new SnippetGroup;
        $group->id = Craft::$app->getRequest()->getBodyParam('id');
        $group->name = Craft::$app->getRequest()->getBodyParam('name');
        $group->handle = strtolower($group->name);

        $isNewGroup = empty($group->id);

        if (GlobalSnippets::$plugin->snippets->saveSnippetGroup($group)){
            if ($isNewGroup) {
                Craft::$app->getSession()->setNotice('Group Added');
            }
            return $this->asJson([
                'success' => true,
                'group' => $group->getAttributes(),
            ]);
        }
        
        return $this->asJson([
            'errors' => $group->getErrors(),
        ]);
    }

    public function actionDeleteSnippet($id = null)
    {
        $this->requireLogin();

        if ($id == null){
            $this->requirePostRequest();
            $this->requireAcceptsJson();
            $id = Craft::$app->getRequest()->getRequiredBodyParam('id');
        }
        if (GlobalSnippets::$plugin->snippets->deleteSnippetById($id)) {
            Craft::$app->getSession()->setNotice('Snippet Deleted.');
            //return $this->redirectToPostedUrl($email);
        } else {
            Craft::$app->getSession()->setError('Couldn’t delete snippet.');
        }
        return $this->asJson(['success' => true]);
    }

    public function actionDeleteSnippetGroup($id = null)
    {
        $this->requireLogin();


        if ($id == null){
            $this->requirePostRequest();
            $this->requireAcceptsJson();
            $id = Craft::$app->getRequest()->getRequiredBodyParam('id');
        }
        if (GlobalSnippets::$plugin->snippets->deleteSnippetGroupById($id)){
            Craft::$app->getSession()->setNotice('Snippet Group Deleted.');
        } else {
            Craft::$app->getSession()->setError('Couldn\'t delete group.');
        }
        return $this->asJson(['success'=>true]);
    }
}
