<?php
namespace Bazo\Extensions\Neo4j\DI;

use Doctrine\Common\ClassLoader,
	Doctrine\Common\Annotations\AnnotationReader;

/**
 * Description of Neo4jExtension
 *
 * @author Martin Bažík
 */
class Neo4jExtension extends \Nette\Config\CompilerExtension
{
	/**
	 * @var array
	 */
	public $defaults = array(
		'host' => 'localhost',
		'port' => 7474
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
		
		$builder->addDefinition('neo4jClient')
			->setClass('\Everyman\Neo4j\Client')
			->setFactory('@container::getService', array($this->prefix('client')));
		
		$builder->addDefinition($this->prefix('entityManager'))
			->setClass('\HireVoice\Neo4j\EntityManager')
			->setFactory('Bazo\Extensions\Neo4j\DI\Neo4jExtension::createEntityManager', array('@container', $config))
			->setAutowired(FALSE);

		$builder->addDefinition('entityManager')
			->setClass('\HireVoice\Neo4j\EntityManager')
			->setFactory('@container::getService', array($this->prefix('entityManager')));
		
		$builder->addDefinition($this->prefix('panel'))
			->setFactory('Kdyby\Extension\Redis\Diagnostics\Panel::register');
	}
	
	public static function createNeo4jClient(\Nette\DI\Container $container, $config)
	{
		return new \Everyman\Neo4j\Client($config['host'], $config['port']);
	}
	
	public static function createEntityManager(\Nette\DI\Container $container, $config)
	{
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Auto.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Entity.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Index.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/ManyToMany.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/ManyToOne.php');
		\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(VENDOR_DIR . '/hirevoice/neo4jphp-ogm/lib/HireVoice/Neo4j/Annotation/Property.php');
		
		$metadataCache = new $config['metaDataCacheClass'];
		$metadataCache->setNamespace($config['cachePrefix']);
		
		$reader = new \Doctrine\Common\Annotations\CachedReader(
			new AnnotationReader,
			$metadataCache,
			false
		);
		
		$client = $container->neo4jClient;
		$metaRepository = new \HireVoice\Neo4j\Meta\Repository($reader);
		$em = new \HireVoice\Neo4j\EntityManager($client, $metaRepository);
		
		$panel = $container->neo4j->panel;
		$em->registerEvent(HireVoice\Neo4j\EntityManager::QUERY_RUN, function($query, $parameters, $time)use($panel){
			$panel->addQuery($query, $parameters, $time);
		});
		
		$em->setProxyFactory(new \HireVoice\Neo4j\Proxy\Factory($config['proxyDir'], true));
		return $em;
	}
	
	
}