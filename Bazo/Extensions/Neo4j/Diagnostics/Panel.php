<?php

/**
 * Copyright (c) 2012 Martin Bazik <martin@bazo.sk>
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Bazo\Extensions\Neo4j\Diagnostics;

use Everyman\Neo4j\Query;
use HireVoice\Neo4j;
use Nette;
use Nette\Diagnostics\Debugger;

/**
 * @author Martin Bazik <martin@bazo.sk>
 */
class Panel extends Nette\Object implements Nette\Diagnostics\IBarPanel
{

	/**
	 * @var int
	 */
	public static $maxLength = 1000;

	/**
	 * @var int
	 */
	private $totalTime = 0;

	/**
	 * @var array
	 */
	private $queries = array();

	private function getQueryType(Query $query = null)
	{
		if ($query === null)
			return null;
		$types = array(
			'Everyman\Neo4j\Cypher\Query' => 'cypher',
			'Everyman\Neo4j\Gremlin\Query' => 'gremlin'
		);
		$class = get_class($query);
		return $types[$class];
	}

	public function addQuery(Query $query = null, $parameters = array(), $time = null)
	{
		$type = $this->getQueryType($query);
		$queryEntry = (object) array(
					'type' => $type,
					'query' => ($query !== null) ? $query->getQuery() : '',
					'parameters' => $parameters,
					'time' => $time,
					'results' => ($query !== null) ? $query->getResultSet()->count() : 'n/a'
		);

		$this->totalTime += $time;
		$this->queries[] = $queryEntry;
	}

	/**
	 * Renders HTML code for custom tab.
	 * @return string
	 */
	public function getTab()
	{
		return '<span title="Neo4j">' .
				'<img alt="" src="data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAAQABADASIAAhEBAxEB/8QAFgABAQEAAAAAAAAAAAAAAAAACAYH/8QAHxAAAgIDAQEAAwAAAAAAAAAABAUDBgECBwgJEhMU/8QAFQEBAQAAAAAAAAAAAAAAAAAABwn/xAAdEQADAAMBAQEBAAAAAAAAAAADBAUBAgYTEgcR/9oADAMBAAIRAxEAPwCP5DdOE1vy5cq3buF0y99UtPQq7HVLy+KKFlRLBxUDCUBjMDquarkO2F7eAvau2IBg4w8IiP2AiXhmStP2r8wecefvOLrtlUu5k9gr0yTaw182EHarMYrI1DTYFpO0ukj8TCwpnCSLh05sJRqoUmWciMjX884B846d5m6cd0qk+l+gp67Wcoau+r1ae3IGkp3btXlyMU71fElgSQOawAftEIGGwFnNAdtckxGLxS4cHr0r2aawlH8iqvULrd+N8zd2hPQyLRfnVlEsCsBu2Erb6FYXKMmXwj1zYJalhAUwSQBfu3/o20NwMMltAuP/AKSOTydzoIAIVpTpu5FSnuUYnToV0pwl50Wg+coU/AM86xk0BrrhYdK4L0ZSIMc6B7Iig+zyyLO7SeykzIyCA4k0swXJTHGuPJD4JhgZsEb31+tQ+P8APncW5P/Z" />' .
				count($this->queries) . ' queries' .
				($this->queries ? ' / ' . sprintf('%0.1f', $this->totalTime * 1000) . ' ms' : '') .
				'</span>';
	}

	/**
	 * Renders HTML code for custom panel.
	 * @return string
	 */
	public function getPanel()
	{
		$s = '';
		$h = 'htmlSpecialChars';
		foreach ($this->queries as $query) {
			$s .= '<tr><td>' . sprintf('%0.3f', $query->time * 1000000) . '</td>';
			$s .= '<td class="neo4j-query">' .
					$h(self::$maxLength ? Nette\Utils\Strings::truncate($query->query, self::$maxLength) : $query->query)
					. '</td>';
			$parameters = '';
			foreach ($query->parameters as $key => $value) {
				$parameters .= $key . ': ' . $value . "\n";
			}
			$s .= '<td>' . $parameters . '</td>';
			$s .= '<td>' . $query->results . '</td>';
			$s .= '</tr>';
		}

		return empty($this->queries) ? '' :
				'<style> #nette-debug div.neo4j-panel table td { text-align: right }
			#nette-debug div.neo4j-panel table td.neo4j-query { background: white !important; text-align: left } </style>
			<h1>Queries: ' . count($this->queries) . ($this->totalTime ? ', time: ' . sprintf('%0.3f', $this->totalTime * 1000) . ' ms' : '') . '</h1>
			<div class="nette-inner neo4j-panel">
			<table>
				<tr><th>Time&nbsp;µs</th><th>Query</th><th>Parameters</th><th>Results</th></tr>' . $s . '
			</table>
			</div>';
	}

	/**
	 * @param Neo4j\Exception $e
	 * @return type 
	 */
	public static function renderException(\Exception $e = null)
	{
		if ($e instanceof Neo4j\Exception) {
			$panel = NULL;
			if ($e->getQuery() !== null) {
				$panel .= '<h3>Query</h3>'
						. '<pre class="nette-dump"><span class="php-string">'
						. $e->getQuery()->getQuery()
						. '</span></pre>';
			}

			if ($panel !== NULL) {
				$panel = array(
					'tab' => 'Neo4j',
					'panel' => $panel
				);
			}

			return $panel;
		}
	}

	/**
	 * @return \Bazo\Extensions\Neo4j\Diagnostics\Panel
	 */
	public static function register()
	{
		$panel = new static();
		Debugger::$blueScreen->addPanel(array($panel, 'renderException'));
		Debugger::$bar->addPanel($panel);
		return $panel;
	}

}