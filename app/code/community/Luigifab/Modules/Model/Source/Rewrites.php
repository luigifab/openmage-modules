<?php
/**
 * Created S/02/08/2014
 * Updated S/30/08/2014
 * Version 7
 *
 * Copyright 2012-2015 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://redmine.luigifab.info/projects/magento/wiki/modules
 *
 * This program is free software, you can redistribute it or modify
 * it under the terms of the GNU General Public License (GPL) as published
 * by the free software foundation, either version 2 of the license, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but without any warranty, without even the implied warranty of
 * merchantability or fitness for a particular purpose. See the
 * GNU General Public License (GPL) for more details.
 */

class Luigifab_Modules_Model_Source_Rewrites extends Varien_Data_Collection {

	public function getCollection() {

		// getName() = le nom du tag xml
		// => /config/global/models/cron/rewrite/observer
		// <global>                                          <= $config/../../../../$scope
		//  <models>                                         <= $config/../../../$type
		//   <cron>                                          <= $config/../../$module
		//    <rewrite>
		//     <observer>Luigifab_Cronlog_Model_Rewrite_Cron <= $config
		$nodes = Mage::getConfig()->getXpath('/config/*/*/*/rewrite/*');
		$all = $this->getAllRewrites();

		foreach ($nodes as $config) {

			$scope  = $config->getParent()->getParent()->getParent()->getParent()->getName();
			$type   = $config->getParent()->getParent()->getParent()->getName();
			$module = $config->getParent()->getParent()->getName();
			$class  = $config->getName();

			//   class=Luigifab_Cronlog_Model_Rewrite_Cron
			//   first=Cronlog_Model_Rewrite_Cron
			//  second=Model_Rewrite_Cron
			// module2=Cronlog
			//  class2=Rewrite_Cron
			//    name=Luigifab/Cronlog
			$first   = substr($config, strpos($config, '_') + 1);
			$second  = substr($first, strpos($first, '_') + 1);
			$module2 = substr($first, 0, strpos($first, '_'));
			$class2  = substr($second, strpos($second, '_') + 1);

			$moduleName = substr($config, 0, strpos($config, '_')).'/'.$module2;

			// surcharge en conflit
			// - au moins deux fichiers config définissent plus ou moins la même chose
			// - ce qui est affiché = ce qui est actif sur Magento
			$isConflict = (isset($all[$type][$module.'/'.$class]) && (count($all[$type][$module.'/'.$class]) > 1));

			$item = new Varien_Object();
			$item->setModule($moduleName);
			$item->setScope($scope);
			$item->setType(substr($type, 0, -1));
			$item->setCoreClass($module.'/'.$class);
			$item->setRewriteClass(strtolower($module2.'/'.$class2));
			$item->setStatus($isConflict ? 'disabled' : 'enabled'); // disabled=conflict / enabled=ok

			$this->addItem($item);
		}

		usort($this->_items, array($this, 'sort'));
		return $this;
	}

	private function sort($a, $b) {
		$test = strcmp($a->getScope(), $b->getScope());
		if ($test === 0)
			$test = strcmp($a->getType(), $b->getType());
		if ($test === 0)
			$test = strcmp($a->getCoreClass(), $b->getCoreClass());
		return $test;
	}

	private function getAllRewrites() {

		$folders = array('app/code/local/', 'app/code/community/');
		$files = $rewrites = array();

		foreach ($folders as $folder)
			$files = array_merge($files, glob($folder.'*/*/etc/config.xml'));

		foreach ($files as $file) {

			$dom = new DOMDocument;
			$dom->loadXML(file_get_contents($file));

			$qry = new DOMXPath($dom);
			$nodes = $qry->query('/config/*/*/*/rewrite/*');

			// => /config/global/models/cron/rewrite/observer
			// <global>
			//  <models>                                         <= $config/../../../$type
			//   <cron>                                          <= $config/../../$module
			//    <rewrite>
			//     <observer>Luigifab_Cronlog_Model_Rewrite_Cron <= $config
			foreach ($nodes as $config) {

				$type   = $config->parentNode->parentNode->parentNode->tagName;
				$module = $config->parentNode->parentNode->tagName;
				$class  = $config->tagName;

				$rewrites[$type][$module.'/'.$class][] = $config->nodeValue;
			}
		}

		return $rewrites;
	}
}