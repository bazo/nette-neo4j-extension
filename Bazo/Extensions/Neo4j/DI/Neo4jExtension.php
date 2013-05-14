<?php

namespace Bazo\Extensions\Neo4j\DI;

use Doctrine\Common\Annotations\AnnotationReader;

/**
 * Description of Neo4jExtension
 *
 * @author Martin Bažík
 */
class Neo4jExtension extends \Nette\DI\CompilerExtension
{

	/** @var array */
	public $defaults = array(
		'host' => 'localhost',
		'port' => 7474,
		'cachePrefix' => 'neo4j',
		'metaDataCache' => 'array',
		'proxyDir' => '%appDir%/models/proxies',
		'debug' => false,
		'username' => null,
		'password' => null
	);
	
	/** @var array */
	private static $cacheClassMap = array(
		'array' => '\Doctrine\Common\Cache\ArrayCache',
		'apc' => '\Doctrine\Common\Cache\ApcCache',
		'filesystem' => '\Doctrine\Common\Cache\FilesystemCache',
		'phpFile' => '\Doctrine\Common\Cache\PhpFileCache',
		'winCache' => '\Doctrine\Common\Cache\WinCacheCache',
		'xcache' => '\Doctrine\Common\Cache\XcacheCache',
		'zendData' => '\Doctrine\Common\Cache\ZendDataCache'
	);

	/**
	 * Processes configuration data
	 *
	 * @return void
	 */
	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();

		$config = $this->getConfig($this->defaults);

		$builder->addDefinition($this->prefix('client'))
				->setClass('\Everyman\Neo4j\Client')
				->setFactory('Bazo\Extensions\Neo4j\DI\Neo4jExtension::createNeo4jClient', array('@container', $config))
				->setAutowired(FALSE);

		$builder->addDefinition($this->prefix('entityManager'))
				->setClass('\HireVoice\Neo4j\EntityManager')
				->setFactory('Bazo\Extensions\Neo4j\DI\Neo4jExtension::createEntityManager', array('@container', $config))
				->setAutowired(FALSE);

		$builder->addDefinition('entityManager')
				->setClass('\HireVoice\Neo4j\EntityManager')
				->setFactory('@container::getService', array($this->prefix('entityManager')));

		$builder->addDefinition($this->prefix('panel'))
				->setFactory('\Bazo\Extensions\Neo4j\Diagnostics\Panel::register');
	}

	public static function createNeo4jClient(\Nette\DI\Container $container, $config)
	{
		return $container->neo4j->entityManager->getClient();
	}

	public static function createEntityManager(\Nette\DI\Container $container, $config)
	{
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Auto.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Entity.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Index.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/ManyToMany.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/ManyToOne.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Property.php');

		$metadataCacheClass = self::$cacheClassMap[$config['metaDataCache']];
		$metadataCache = new $metadataCacheClass;
		$metadataCache->setNamespace($config['cachePrefix']);

		$reader = new \Doctrine\Common\Annotations\CachedReader(
				new AnnotationReader, $metadataCache, false
		);

		$configuration = new \HireVoice\Neo4j\Configuration(array(
			'host' => $config['host'],
			'port' => $config['port'],
			'proxyDir' => $config['proxyDir'],
			'debug' => $config['debug'],
			'username' => $config['username'],
			'password' => $config['password'],
			'annotationReader' => $reader
		));

		$em = new \HireVoice\Neo4j\EntityManager($configuration);

		$panel = $container->neo4j->panel;
		$em->registerEvent(\HireVoice\Neo4j\EntityManager::QUERY_RUN, function($query, $parameters, $time)use($panel) {
					$panel->addQuery($query, $parameters, $time);
				});

		return $em;
	}

}