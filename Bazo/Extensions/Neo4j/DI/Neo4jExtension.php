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
		
		$container = $this->getContainerBuilder();
		
		$config = $this->getConfig($this->defaults);
		
		$container->addDefinition($this->prefix('neo4jClient'))
			->setClass('\Everyman\Neo4j\Client')
			->setFactory('Extensions\EntityManagerExtension::createNeo4jClient', array('@container', $config))
			->setAutowired(FALSE);
		
		$container->addDefinition($this->prefix('entityManager'))
			->setClass('\HireVoice\Neo4j\EntityManager')
			->setFactory('Extensions\EntityManagerExtension::createEntityManager', array('@container', $config))
			->setAutowired(FALSE);

		$container->addDefinition('entityManager')
			->setClass('\HireVoice\Neo4j\EntityManager')
			->setFactory('@container::getService', array($this->prefix('entityManager')));
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
		$em->setProxyFactory(new \HireVoice\Neo4j\Proxy\Factory($config['proxyDir'], true));
		return $em;
	}
	
	
}