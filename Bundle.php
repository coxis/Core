<?php
namespace Asgard\Core;

/**
 * Asgard core bundle.
 * @author Michel Hognerud <michel@hognerud.com>
 */
class Bundle extends \Asgard\Core\BundleLoader {
	/**
	 * Register services.
	 * @param \Asgard\Container\ContainerInterface $container
	 */
	public function buildContainer(\Asgard\Container\ContainerInterface $container) {
		#Config
		$container->setParentClass('config', 'Asgard\Config\ConfigInterface');

		#Cache
		$container->setParentClass('cache', 'Asgard\Cache\CacheInterface');
		$container->register('cache', function($container) {
			if($cache = $container['kernel']->getCache())
				return new \Asgard\Cache\Cache($cache);
			else
				return new \Asgard\Cache\Cache;#null cache
		});

		#Db
		$container->setParentClass('schema', 'Asgard\Db\SchemaInterface');
		$container->register('schema', function($container) {
			return new \Asgard\Db\Schema($container['db']);
		});
		$container->setParentClass('db', 'Asgard\Db\DBInterface');
		$container->register('db', function($container) {
			return new \Asgard\Db\DB($container['config']['database']);
		});

		#Email
		$container->setParentClass('email', 'Asgard\Email\DriverInterface');
		$container->register('email', function($container) {
			$emailDriver = '\\'.trim($container['config']['email.driver'], '\\');
			$email = new $emailDriver();
			$email->transport($container['config']['email']);
			return $email;
		});

		#Entity
		$container->setParentClass('entityManager', 'Asgard\Entity\EntityManagerInterface');
		$container->register('entityManager', function($container) {
			$entityManager = new \Asgard\Entity\EntityManager($container);
			$entityManager->setHookManager($container['hooks']);
			$entityManager->setDefaultLocale($container['config']['locale']);
			$entityManager->setValidatorFactory($container['validator_factory']);
			return $entityManager;
		});
		$container->register('Asgard.Entity.PropertyType.file', function($container, $params) {
			$prop = new \Asgard\Entity\Properties\FileProperty($params);
			$prop->setWebDir($container['config']['webdir']);
			if($container['httpKernel']->getRequest())
				$prop->setUrl($container['httpKernel']->getRequest()->url);
			return $prop;
		});

		#FORMInterface
		$container->setParentClass('WidgetManager', 'Asgard\Form\WidgetManagerInterface');
		$container->register('WidgetManager', function() { return new \Asgard\Form\WidgetManager; });
		$container->setParentClass('EntityFieldSolver', 'Asgard\EntityForm\EntityFieldSolverInterface');
		$container->register('EntityFieldSolver', function() { return new \Asgard\Entityform\EntityFieldSolver; });
		$container->setParentClass('entityForm', 'Asgard\EntityForm\EntityFormInterface');
		$container->register('entityForm', function($container, $entity, $params=[], $request=null) {
			if($request === null)
				$request = $container['httpKernel']->getRequest();
			$EntityFieldSolver = clone $container['EntityFieldSolver'];
			$form = new \Asgard\Entityform\EntityForm($entity, $params, $request, $EntityFieldSolver, $container['dataMapper']);
			$form->setWidgetManager(clone $container['WidgetManager']);
			$form->setTranslator($container['translator']);
			$form->setValidatorFactory($container['validator_factory']);
			return $form;
		});
		$container->setParentClass('form', 'Asgard\Form\FormInterface');
		$container->register('form', function($container, $name=null, $params=[], $request=null, $fields=[]) {
			if($request === null)
				$request = $container['httpKernel']->getRequest();
			$form = new \Asgard\Form\Form($name, $params, $request, $fields);
			$form->setWidgetManager(clone $container['WidgetManager']);
			$form->setTranslator($container['translator']);
			$form->setValidatorFactory($container['validator_factory']);
			return $form;
		});

		#Hook
		$container->setParentClass('hooks', 'Asgard\Hook\HookManagerInterface');
		$container->register('hooks', function($container) { return new \Asgard\Hook\HookManager($container); } );
		$container->setParentClass('hooksAnnotationReader', 'Asgard\Hook\AnnotationReader');
		$container->register('hooksAnnotationReader', function($container) { return $container['kernel']->getHooksAnnotationReader(); } );

		#Http
		$container->setParentClass('httpKernel', 'Asgard\Http\HttpKernelInterface');
		$container->register('httpKernel', function($container) {
			$httpKernel = new \Asgard\Http\HttpKernel($container);
			$httpKernel->setDebug($container['config']['debug']);
			if($container->has('templateEngine_factory'))
				$httpKernel->setTemplateEngineFactory($container['templateEngine_factory']);
			$httpKernel->setHookManager($container['hooks']);
			if($container->has('errorHandler'))
				$httpKernel->setErrorHandler($container['errorHandler']);
			$httpKernel->setTranslator($container['translator']);
			$httpKernel->setResolver($container['resolver']);
			$container['resolver']->setHttpKernel($httpKernel);
			return $httpKernel;
		});
		$container->setParentClass('resolver', 'Asgard\Http\ResolverInterface');
		$container->register('resolver', function($container) {
			return new \Asgard\Http\Resolver($container['cache']);
		});
		$container->setParentClass('browser', 'Asgard\Http\Browser\BrowserInterface');
		$container->register('browser', function($container) {
			$browser = new \Asgard\Http\Browser\Browser($container['httpKernel'], $container);
			if(getenv('catch') !== false)
				$browser->catchException((bool)getenv('catch'));
			return $browser;
		});
		$container->setParentClass('cookieManager', 'Asgard\Common\BagInterface');
		$container->register('cookies', function() {
			if(php_sapi_name() === 'cli')
				return new \Asgard\Common\Bag;
			else
				return new \Asgard\Http\CookieManager;
		});
		$container->setParentClass('sessionManager', 'Asgard\Common\BagInterface');
		$container->register('session', function() {
			if(php_sapi_name() === 'cli')
				return new \Asgard\Common\Bag;
			else
				return new \Asgard\Common\Session;
		});
		$container->setParentClass('html', 'Asgard\Http\Utils\HTMLInterface');
		$container->register('html', function($container) {
			return new \Asgard\Http\Utils\HTML($container['httpKernel']);
		});
		$container->setParentClass('flash', 'Asgard\Http\Utils\Flash');
		$container->register('flash', function($container) {
			return new \Asgard\Http\Utils\Flash($container);
		});
		$container->setParentClass('url', 'Asgard\Http\URLInterface');
		$container->register('url', function($container) {
			return $container['httpKernel']->getRequest()->url;
		});
		$container->setParentClass('controllersAnnotationReader', 'Asgard\Http\AnnotationReader');
		$container->register('controllersAnnotationReader', function($container) { return $container['kernel']->getControllersAnnotationReader(); } );

		#Migration
		$container->setParentClass('MigrationManager', 'Asgard\Migration\MigrationManagerInterface');
		$container->register('MigrationManager', function($container) {
			return new \Asgard\Migration\MigrationManager($container['kernel']['root'].'/migrations/', $container['db'], $container['schema'], $container);
		});

		#Common
		$container->setParentClass('intl', 'Asgard\Common\Intl');
		$container->register('intl', function($container) {
			return \Asgard\Common\Intl::singleton()->setTranslator($container['translator']);
		});
		$container->setParentClass('paginator', 'Asgard\Common\PaginatorInterface');
		$container->register('paginator', function($container, $count, $page, $per_page) {
			return new \Asgard\Common\Paginator($count, $page, $per_page, $container['httpKernel']->getRequest());
		});
		$container->setParentClass('paginator_factory', 'Asgard\Common\PaginatorFactoryInterface');
		$container->register('paginator_factory', function($container) {
			return new \Asgard\Common\PaginatorFactory($container['httpKernel']);
		});

		#Validation
		$container->setParentClass('validator', 'Asgard\Validation\ValidatorInterface');
		$container->register('validator', function($container) {
			$validator = new \Asgard\Validation\Validator;
			$validator->setRegistry($container['rulesregistry']);
			$validator->setTranslator($container['translator']);
			return $validator;
		});
		$container->setParentClass('validator_factory', 'Asgard\Validation\ValidatorFactoryInterface');
		$container->register('validator_factory', function($container) {
			return new \Asgard\Validation\ValidatorFactory($container['rulesRegistry'], $container['translator']);
		});
		$container->setParentClass('rulesregistry', 'Asgard\Validation\RulesRegistryInterface');
		$container->register('rulesregistry', function() { return new \Asgard\Validation\RulesRegistry; } );

		#ORMInterface
		$container->setParentClass('orm', 'Asgard\Orm\ORMInterface');
		$container->register('orm', function($container, $entityClass, $dataMapper, $locale, $prefix) {
			return new \Asgard\Orm\ORM($entityClass, $dataMapper, $locale, $prefix, $container['paginator_factory']);
		});
		$container->register('orm_factory', function($container) {
			return new \Asgard\Orm\ORMFactory($container['paginator_factory']);
		});
		$container->setParentClass('collectionOrmInterface', 'Asgard\Orm\CollectionORMInterface');
		$container->register('collectionOrm', function($container, $entityClass, $name, $dataMapper, $locale, $prefix) {
			return new \Asgard\Orm\CollectionORM($entityClass, $name, $dataMapper, $locale, $prefix, $container['paginator_factory']);
		});
		$container->register('collectionOrm_factory', function($container) {
			return new \Asgard\Orm\CollectionORMFactory($container['paginator_factory']);
		});
		$container->setParentClass('datamapper', 'Asgard\Orm\DataMapperInterface');
		$container->register('datamapper', function($container) {
			return new \Asgard\Orm\DataMapper(
				$container['db'],
				$container['entityManager'],
				$container['config']['locale'],
				$container['config']['database.prefix'],
				$container['orm_factory'],
				$container['collectionOrm_factory']
			);
		});

		#translations
		$container->register('translationResources', function() {
			return new \Asgard\Core\TranslationResources;
		});

		$container->register('translator', function($container) {
			$locale = $container['config']['locale'];
			$translator = new \Symfony\Component\Translation\Translator($locale, new \Symfony\Component\Translation\MessageSelector());
			$translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
			foreach($container['translationResources']->getFiles($locale) as $file)
				$translator->addResource('yaml', $file, $locale);
			return $translator;
		});
	}

	/**
	 * Run the bundle.
	 * @param \Asgard\Container\ContainerInterface $container
	 */
	public function run(\Asgard\Container\ContainerInterface $container) {
		parent::run($container);

		#Files
		$container['rulesregistry']->registerNamespace('Asgard\File\Rules');

		#ORMInterface
		$container['rulesregistry']->registerNamespace('Asgard\Orm\Rules');

		#Controllers Templates
		$container['httpKernel']->addTemplatePathSolver(function($viewable, $template) {
			if(!$viewable instanceof \Asgard\Http\LambdaController) {
				$r = new \ReflectionClass($viewable);
				$viewableName = basename(str_replace('\\', DIRECTORY_SEPARATOR, get_class($viewable)));
				$viewableName = strtolower(preg_replace('/Controller$/i', '', $viewableName));

				if($viewable instanceof \Asgard\Http\Controller)
					$format = $viewable->request->format();
				else
					$format = 'html';

				$file = realpath(dirname($r->getFileName()).'/../'.$format.'/'.$viewableName.'/'.$template.'.php');
				if(!file_exists($file))
					return realpath(dirname($r->getFileName()).'/../html/'.$viewableName.'/'.$template.'.php');
				else
					return $file;
			}
		});

		foreach(glob($this->getPath().'/../Common/translations/*') as $dir)
			$container['translationResources']->add(basename($dir), $dir);
		foreach(glob($this->getPath().'/../Validation/translations/*') as $dir)
			$container['translationResources']->add(basename($dir), $dir);
		foreach(glob($this->getPath().'/../Form/translations/*') as $dir)
			$container['translationResources']->add(basename($dir), $dir);

		if($container->has('console')) {
			$root = $container['kernel']['root'];
			$em = $container['entityManager'];

			#tester
			$httpKernel = $container['httpKernel'];
			$resolver = $container['resolver'];
			$db = $container->has('db') ? $container['db']:null;
			$mm = $container->has('migrationsManager') ? $container['migrationManager']:null;

			$runCommand = new \Asgard\Tester\Commands\RunCommand($httpKernel, $resolver, $db, $mm);
			$container['console']->add($runCommand);

			$curlCommand = new \Asgard\Tester\Commands\CurlCommand();
			$container['console']->add($curlCommand);

			$httpTests = new \Asgard\Tester\Commands\GenerateTestsCommand($container['kernel']['root'].'/tests');
			$container['console']->add($httpTests);

			$config = $container['config']['tester.coverage'];
			$coverageCommand = new \Asgard\Tester\Commands\CoverageCommand($config);
			$container['console']->add($coverageCommand);

			#if database is available
			if($container['config']['database']) {
				$mm = $container['MigrationManager'];
				$db = $container['db'];
				$schema = $container['schema'];
				$dataMapper = $container['dataMapper'];

				$migrationList = new \Asgard\Migration\Commands\ListCommand($container['kernel']['root'].'/migrations', $db);
				$container['console']->add($migrationList);

				$migrationRemove = new \Asgard\Migration\Commands\RemoveCommand($container['kernel']['root'].'/migrations', $db, $schema);
				$container['console']->add($migrationRemove);

				$migrationAdd = new \Asgard\Migration\Commands\AddCommand($root.'/migrations', $db, $schema);
				$container['console']->add($migrationAdd);

				$dbCreate = new \Asgard\Db\Commands\CreateCommand($db);
				$container['console']->add($dbCreate);

				$ormAutomigrate = new \Asgard\Orm\Commands\AutoMigrateCommand($em, $mm, $dataMapper);
				$container['console']->add($ormAutomigrate);

				$ormGenerateMigration = new \Asgard\Orm\Commands\GenerateMigrationCommand($em, $mm, $dataMapper);
				$container['console']->add($ormGenerateMigration);

				$dbEmpty = new \Asgard\Db\Commands\EmptyCommand($db);
				$container['console']->add($dbEmpty);

				$migrationMigrate = new \Asgard\Migration\Commands\MigrateCommand($container['kernel']['root'].'/migrations', $db, $schema);
				$migrationMigrateOne = new \Asgard\Migration\Commands\MigrateOneCommand($container['kernel']['root'].'/migrations', $db, $schema);
				$migrationRollback = new \Asgard\Migration\Commands\RollbackCommand($root.'/migrations', $db, $schema);
				$migrationUnmigrate = new \Asgard\Migration\Commands\UnmigrateCommand($root.'/migrations', $db, $schema);
				$migrationRefresh = new \Asgard\Migration\Commands\RefreshCommand($container['kernel']['root'].'/migrations', $db, $schema);
				$install = new \Asgard\Core\Commands\InstallCommand($db, $schema);
				$publish = new \Asgard\Core\Commands\PublishCommand($db, $schema);
			}
			else {
				$migrationMigrate = new \Asgard\Migration\Commands\MigrateCommand($container['kernel']['root'].'/migrations');
				$migrationMigrateOne = new \Asgard\Migration\Commands\MigrateOneCommand($container['kernel']['root'].'/migrations');
				$migrationRollback = new \Asgard\Migration\Commands\RollbackCommand($root.'/migrations');
				$migrationUnmigrate = new \Asgard\Migration\Commands\UnmigrateCommand($root.'/migrations');
				$migrationRefresh = new \Asgard\Migration\Commands\RefreshCommand($container['kernel']['root'].'/migrations');
				$install = new \Asgard\Core\Commands\InstallCommand;
				$publish = new \Asgard\Core\Commands\PublishCommand;
			}

			$container['console']->add($migrationMigrate);
			$container['console']->add($migrationMigrateOne);
			$container['console']->add($migrationRollback);
			$container['console']->add($migrationUnmigrate);
			$container['console']->add($migrationRefresh);
			$container['console']->add($install);
			$container['console']->add($publish);

			$translationResources = $container['translationResources'];
			$translator = $container['translator'];
			$root = $container['kernel']['root'];
			$dir = $container['config']['translation.directories'];
			if(!$dir)
				$dir = [$root.'/app'];

			$exportCsvCommand = new \Asgard\Translation\Commands\ExportCsvCommand($translationResources, $translator, $dir);
			$container['console']->add($exportCsvCommand);

			$importCommand = new \Asgard\Translation\Commands\ImportCommand();
			$container['console']->add($importCommand);

			$exportYamlCommand = new \Asgard\Translation\Commands\ExportYamlCommand($translationResources, $translator, $dir);
			$container['console']->add($exportYamlCommand);

			$migrationCreate = new \Asgard\Migration\Commands\CreateCommand($container['kernel']['root'].'/migrations');
			$container['console']->add($migrationCreate);

			$showEnv = new \Asgard\Core\Commands\ShowEnvironmentCommand($container['kernel']);
			$container['console']->add($showEnv);

			$switchEnv = new \Asgard\Core\Commands\SwitchEnvironmentCommand($container['kernel']);
			$container['console']->add($switchEnv);

			$httpRoutes = new \Asgard\Http\Commands\RoutesCommand($container['resolver']);
			$container['console']->add($httpRoutes);

			$containerServices = new \Asgard\Container\Commands\ListCommand($root);
			$container['console']->add($containerServices);

			$cacheClear = new \Asgard\Cache\Commands\ClearCommand($container['cache']);
			$container['console']->add($cacheClear);

			$configInit = new \Asgard\Config\Commands\InitCommand($container['kernel']['root'].'/config');
			$container['console']->add($configInit);

			$dbInit = new \Asgard\Db\Commands\InitCommand($container['kernel']['root'].'/config');
			$container['console']->add($dbInit);

			$httpBrowser = new \Asgard\Http\Commands\BrowserCommand($container['httpKernel']);
			$container['console']->add($httpBrowser);
		}
	}
}