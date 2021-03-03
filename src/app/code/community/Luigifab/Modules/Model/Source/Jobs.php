<?php
/**
 * Created D/10/02/2013
 * Updated V/12/02/2021
 *
 * Copyright 2012-2021 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
 * https://www.luigifab.fr/openmage/modules
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

class Luigifab_Modules_Model_Source_Jobs extends Varien_Data_Collection {

	public function getCollection($type = null) {

		// getName() = le nom du tag xml
		// => /config/crontab/jobs/modules_send_report
		// <crontab>
		//  <jobs>
		//   <modules_send_report>                       <= $node
		//    <run>
		//     <model>modules/observer::sendMail</model>
		//    <schedule>
		//     <disabled>1</disabled>
		$nodes = Mage::getModel('core/config')->loadBase()->loadModules()->loadDb();
		$nodes = $nodes->getXpath('/config/crontab/jobs/*');

		foreach ($nodes as $node) {

			$jobcode = $node->getName();
			$config  = Mage::getConfig()->getNode('default/crontab/jobs/'.$jobcode);

			$expr = empty($node->schedule->config_path) ? null  : Mage::getStoreConfig((string) $node->schedule->config_path);
			$expr = empty($node->schedule->cron_expr)   ? $expr : $node->schedule->cron_expr;
			$expr = empty($config->schedule->config_path) ? $expr : Mage::getStoreConfig((string) $config->schedule->config_path);
			$expr = empty($config->schedule->cron_expr)   ? $expr : $config->schedule->cron_expr;
			$expr = empty(trim($expr)) ? null : trim($expr);

			$model = empty($node->run->model) ? null : $node->run->model;
			$model = empty($config->run->model) ? $model : $config->run->model;

			$className  = Mage::getConfig()->getModelClassName($model);
			$className  = mb_substr($className, 0, mb_strpos($className, ':'));
			$moduleName = mb_substr($className, 0, mb_strpos($className, '_', mb_strpos($className, '_') + 1));

			// tâche désactivée si
			// - balise disabled
			// - ou configuration disabled
			// - ou pas de programmation (= ni balise config_path/cron_expr, ni configuration config_path/cron_expr)
			$isDisabled = (!empty($node->schedule->disabled) || !empty($config->schedule->disabled) || empty($expr)) ?
				'disabled' : 'enabled';

			// tâche en lecture seule si
			// - balise disabled
			// - ou pas de balise de programmation (= pas de balise config_path/cron_expr)
			// - ou pas de programmation (= ni balise config_path/cron_expr, ni configuration config_path/cron_expr)
			$isReadOnly = !empty($node->schedule->disabled) ||
			              (empty($node->schedule->config_path) && empty($node->schedule->cron_expr)) ||
			               empty($expr);

			$item = new Varien_Object();
			$item->setData('class_name', $className);
			$item->setData('module', $moduleName);
			$item->setData('job_code', $jobcode);
			$item->setData('cron_expr', $expr);
			$item->setData('model', $model);
			$item->setData('status', $isDisabled);
			$item->setData('is_read_only', $isReadOnly);

			if ((($type == 'ro') && $isReadOnly) || (($type == 'rw') && !$isReadOnly) || empty($type))
				$this->addItem($item);
		}

		usort($this->_items, static function ($a, $b) {
			return strnatcasecmp($a->getData('job_code'), $b->getData('job_code'));
		});

		return $this;
	}

	public function toOptionArray() {
		return $this->getCollection()->_toOptionArray('job_code', 'job_code');
	}
}