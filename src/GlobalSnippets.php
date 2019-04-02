<?php
/**
 * Global Snippets plugin for Craft CMS 3.x
 *
 * Create re-usable chunks of content for templates
 *
 * @link      https://kurious.agency
 * @copyright Copyright (c) 2019 Kurious Agency
 */

namespace kuriousagency\globalsnippets;

use kuriousagency\globalsnippets\services\Snippets as SnippetsService;
use kuriousagency\globalsnippets\variables\GlobalSnippetsVariable;
use kuriousagency\globalsnippets\models\Settings;
use kuriousagency\globalsnippets\elements\Snippet as SnippetElement;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\services\Elements;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;

use yii\base\Event;

/**
 * Class GlobalSnippets
 *
 * @author    Kurious Agency
 * @package   GlobalSnippets
 * @since     2.0.0
 *
 * @property  SnippetsService $snippets
 */
class GlobalSnippets extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var GlobalSnippets
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '2.0.0';
    public $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['siteActionTrigger1'] = 'global-snippets/snippets';
            }
        );

        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['global-snippets'] = 'global-snippets/snippets/index';
                $event->rules['global-snippets/<snippetGroupId:\d+>'] = 'global-snippets/snippets/index';
                $event->rules['global-snippets/settings'] = 'global-snippets/snippets/settings';
                $event->rules['global-snippets/settings/<snippetGroupId:\d+>'] = 'global-snippets/snippets/settings';
                $event->rules['global-snippets/settings/snippet/<id:\d+>'] = 'global-snippets/snippets/edit';
                $event->rules['global-snippets/settings/snippet/new'] = 'global-snippets/snippets/edit';
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = SnippetElement::class;
            }
        );

        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('globalSnippets', GlobalSnippetsVariable::class);
            }
        );

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                }
            }
		);
		
		$projectConfigService = Craft::$app->getProjectConfig();

		$snippetService = $this->snippets;
		$projectConfigService->onAdd(SnippetsService::CONFIG_SNIPPET_KEY . '.{uid}', [$snippetService, 'handleChangedSnippet'])
			->onUpdate(SnippetsService::CONFIG_SNIPPET_KEY . '.{uid}', [$snippetService, 'handleChangedSnippet'])
			->onRemove(SnippetsService::CONFIG_SNIPPET_KEY . '.{uid}', [$snippetService, 'handleDeleteSnippet']);

		$projectConfigService->onAdd(SnippetsService::CONFIG_SNIPPET_GROUP_KEY . '.{uid}', [$snippetService, 'handleChangedSnippetGroup'])
			->onUpdate(SnippetsService::CONFIG_SNIPPET_GROUP_KEY . '.{uid}', [$snippetService, 'handleChangedSnippetGroup'])
			->onRemove(SnippetsService::CONFIG_SNIPPET_GROUP_KEY . '.{uid}', [$snippetService, 'handleDeleteSnippetGroup']);


        Craft::info(
            Craft::t(
                'global-snippets',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    public function getCpNavItem(): array
    {
        $user = Craft::$app->getUser();
        $admin = $user->isAdmin;
        $item = parent::getCpNavItem();
        $item['subnav']['snippets'] = ['label' => 'Snippets', 'url' => 'global-snippets'];
        if ($admin){
            $item['subnav']['settings'] = ['label' => 'Settings', 'url' => 'global-snippets/settings/'];
        }
        return $item;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     */
    public function getSettingsResponse()
    {   
        return Craft::$app->controller->redirect('global-snippets/settings/');
    }
}
