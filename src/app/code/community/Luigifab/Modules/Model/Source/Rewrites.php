<?php
/**
 * Created S/02/08/2014
 * Updated S/02/12/2023
 *
 * Copyright 2012-2024 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://github.com/luigifab/openmage-modules
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

		$rewrites = $this->searchAllRewrites();

		// getName() = xml tag name
		// => /config/global/models/cron/rewrite/observer
		// <global>                                          <= $node/../../../../$scope
		//  <models>                                         <= $node/../../../$type
		//   <cron>                                          <= $node/../../$srcModule
		//    <rewrite>
		//     <observer>Luigifab_Modules_Model_Rewrite_Cron <= $node
		// => /config/admin/routers/adminhtml/args/modules/Luigifab_Modules
		// <admin>
		//  <routers>
		//   <adminhtml>
		//    <args>
		//     <modules>
		//      <Luigifab_Modules before="Mage_Adminhtml">   <= node
		$xml   = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes = array_merge($xml->getXpath('/config/*/*/*/rewrite/*'), $xml->getXpath('/config/*/routers/*/args/modules/*'));

		foreach ($nodes as $node) {

			$scope  = $node->getParent()->getParent()->getParent()->getParent()->getName();
			$type   = $node->getParent()->getParent()->getParent()->getName();

			if ($type == 'routers')
				continue;

			if ($scope == 'routers') {

				$moduleName = $node->getName();
				if (empty($node->getAttribute('before')) || in_array($moduleName, ['widget', 'oauth', 'api2', 'importexport']) || (strncmp($moduleName, 'Mage_', 5) === 0))
					continue;

				// item
				$item = new Varien_Object();
				$item->setData('module', $moduleName);
				$item->setData('scope', $type);
				$item->setData('type', 'router');
				$item->setData('source_class', $node->getAttribute('before'));
				$item->setData('rewrite_class', $moduleName);
				$item->setData('status', 'enabled');
			}
			else {
				$srcModule     = $node->getParent()->getParent()->getName();                     // short
				$srcClass      = $node->getName();                                               // short
				$srcClassName  = $this->getFullClassName($xml, $srcModule.'/'.$srcClass, $type); // short => full
				//$srcModuleName = mb_substr($srcClassName, 0, mb_strpos($srcClassName, '_', mb_strpos($srcClassName, '_') + 1)); // full

				$dstClass      = $this->getShortClassName($xml, (string) $node, $type); // full/short => short
				$dstClassName  = $this->getFullClassName($xml, $dstClass, $type);       // short => full
				//$dstModule   = mb_substr($dstClass, 0, mb_strpos($dstClass, '/'));    // short
				$dstModuleName = mb_substr($dstClassName, 0, mb_strpos($dstClassName, '_', mb_strpos($dstClassName, '_') + 1)); // full

				// surcharge en conflit
				// - au moins deux fichiers config définissent plus ou moins la même chose
				// - ce qui est affiché = ce qui est actif
				$isConflict = !empty($rewrites[$type][$srcModule.'/'.$srcClass]) && (count($rewrites[$type][$srcModule.'/'.$srcClass]) > 1);

				// item
				$item = new Varien_Object();
				$item->setData('source_ofe_file', $this->getOpenFileEditorData($srcClassName));
				$item->setData('source_class_name', $srcClassName);
				$item->setData('rewrite_ofe_file', $this->getOpenFileEditorData($dstClassName));
				$item->setData('rewrite_class_name', $dstClassName);
				$item->setData('module', $dstModuleName);
				$item->setData('scope', $scope);
				$item->setData('type', mb_substr($type, 0, -1));
				$item->setData('source_class', $srcModule.'/'.$srcClass);

				if ($isConflict) {
					$text = $dstClass.'<br />- '.implode('<br />- ', array_keys($rewrites[$type][$srcModule.'/'.$srcClass]));
					$item->setData('rewrite_class', $text);
					$item->setData('status', 'disabled'); // disabled=conflict enabled=ok
				}
				else {
					$item->setData('rewrite_class', $dstClass);
					$item->setData('status', 'enabled');  // disabled=conflict enabled=ok
				}
			}

			$this->addItem($item);
		}

		usort($this->_items, static function ($a, $b) {
			$test = strnatcasecmp($a->getData('scope'), $b->getData('scope'));
			if ($test == 0)
				$test = strnatcasecmp($a->getData('type'), $b->getData('type'));
			if ($test == 0)
				$test = strnatcasecmp($a->getData('source_class'), $b->getData('source_class'));
			return $test;
		});

		$this->_setIsLoaded();
		return $this;
	}

	protected function getShortClassName(object $xml, string $name, string $scope = 'models') {

		// $name = Luigifab_Modules_Model_Rewrite_Demo
		if (str_contains($name, '/'))
			return $name;

		// module actif
		// config/global/models/modules/class => Luigifab_Modules_Model
		$nodes = $xml->getXpath('/config/*/'.$scope.'/*');
		foreach ($nodes as $node) {
			// $node->getName = modules
			// $node->class   = Luigifab_Modules_Model
			// result         = modules/rewrite_demo
			if (!empty($node->class) && (mb_stripos($name, (string) $node->class) === 0))
				return $node->getName().'/'.implode('_', array_map('lcfirst', explode('_', str_replace($node->class.'_', '', $name))));
		}

		// module inactif
		return '*'.$name;
	}

	protected function getFullClassName(object $xml, string $name, string $scope = 'models') {

		// $name = modules/rewrite_demo
		if (!str_contains($name, '/'))
			return $name;

		// module actif
		// config/global/models/modules/class => Luigifab_Modules_Model
		$key   = mb_substr($name, 0, mb_strpos($name, '/'));
		$type  = ucfirst(mb_substr($scope, 0, -1)); // = Model
		$nodes = $xml->getXpath('/config/*/'.$scope.'/*');
		foreach ($nodes as $node) {
			// $node->getName = modules
			// $node->class   = Luigifab_Modules_Model
			// result         = Mage_Modules_Model_Rewrite_Demo, Luigifab_Modules_Model_Rewrite_Demo
			if ($key == $node->getName()) {
				return empty($node->class) ?
					'Mage_'.uc_words($key.'_'.$type.'_'.mb_substr($name, mb_strpos($name, '/') + 1)) :
					$node->class.'_'.uc_words(mb_substr($name, mb_strpos($name, '/') + 1));
			}
		}

		// module inactif
		return str_contains($name, '_'.$type.'_') ? $name : '*'.$name;
	}

	protected function searchAllRewrites() {

		$folders  = ['app/code/local/', 'app/code/community/'];
		$rewrites = [];
		$files    = [];

		foreach ($folders as $folder)
			$files = array_merge($files, glob($folder.'*/*/etc/config.xml'));

		foreach ($files as $file) {

			$dom = new DOMDocument();
			$dom->loadXML(file_get_contents($file));
			$qry = new DOMXPath($dom);
			$nodes = $qry->query('/config/*/*/*/rewrite/*');

			// => /config/global/models/cron/rewrite/observer
			// <global>
			//  <models>                                         <= $node/../../../$type
			//   <cron>                                          <= $node/../../$module
			//    <rewrite>
			//     <observer>Luigifab_Modules_Model_Rewrite_Cron <= $node
			// tagName/nodeValue === string
			foreach ($nodes as $node) {

				$type   = $node->parentNode->parentNode->parentNode->tagName;
				$module = $node->parentNode->parentNode->tagName;
				$class  = $node->tagName;

				$rewrites[$type][$module.'/'.$class][$file] = $node->nodeValue;
			}
		}

		return $rewrites;
	}

	protected function getOpenFileEditorData(string $className) {

		try {
			$reflector = new ReflectionClass($className);
			return $reflector->getFileName();
		}
		catch (Throwable $t) {
			return null;
		}
	}
}