nette-neo4j-extension
=====================

Neo4j extension for Nette Framework

Provides configured entity manager from https://github.com/lphuberdeau/Neo4j-PHP-OGM and a debug panel

Instal via composer

```json
"require": {
		"bazo/nette-neo4j-extension": "@dev"
    }
``` 

register in bootstrap

```php
$configurator->onCompile[] = function($configurator, $compiler) {
		$compiler->addExtension('neo4j', new \Bazo\Extensions\Neo4j\DI\Neo4jExtension);
};
```

configuration options in config.neon:

```yaml
neo4j:
	host: localhost
	port: 7474
	cachePrefix: neo4j
	metaDataCache: apc
	proxyDir: %appDir%/models/proxies
```

enjoy!