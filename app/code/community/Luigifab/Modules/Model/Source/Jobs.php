<?php
/**
 * Created D/10/02/2013
 * Updated S/21/02/2015
 * Version 14+2
 *
 * Copyright 2012-2015 | Fabrice Creuzot (luigifab) <code~luigifab~info>
 * https://redmine.luigifab.info/projects/magento/wiki/modules « https://redmine.luigifab.info/projects/magento/wiki/cronlog
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

	public function getCollection($type = 'all') {

		// getName() = le nom du tag xml
		// => /config/crontab/jobs/cronlog_send_report
		// <crontab>
		//  <jobs>
		//   <cronlog_send_report>                         <= $config
		//    <run>
		//     <model>cronlog/observer::sendMail</model>
		//    <schedule>
		//     <disabled>1</disabled>
		// Mage::getConfig()->getXpath('/config/crontab/jobs/*'); <= souvent vide, enculé !
		$nodes = (array) Mage::getConfig()->getNode('crontab/jobs');

		foreach ($nodes as $config) {

			$jobcode = $config->getName();
			$configurable = Mage::getConfig()->getNode('default/crontab/jobs/'.$jobcode);

			$expr  = (isset($config->schedule->config_path)) ? $config->schedule->config_path : null;
			$expr  = (isset($config->schedule->cron_expr))   ? $config->schedule->cron_expr   : $expr;
			$expr  = (isset($configurable->schedule->config_path)) ? $configurable->schedule->config_path : $expr;
			$expr  = (isset($configurable->schedule->cron_expr))   ? $configurable->schedule->cron_expr   : $expr;
			$expr  = (strlen(trim($expr)) > 0) ? trim($expr) : null;

			$model = (isset($config->run->model)) ? $config->run->model : null;
			$model = (isset($configurable->run->model)) ? $configurable->run->model : $model;

			$moduleName = Mage::getConfig()->getModelClassName($model);
			$moduleName = substr($moduleName, 0, strpos($moduleName, '_', strpos($moduleName, '_') + 1));
			$moduleName = str_replace('_', '/', $moduleName);

			// tâche désactivée
			// - la balise disabled
			// - ou configuration disabled
			// - ou pas de programmation (= ni balises config_path/cron_expr, ni configuration config_path/cron_expr)
			$isDisabled = (isset($config->schedule->disabled) || isset($configurable->schedule->disabled) || is_null($expr)) ? 'disabled' : 'enabled';

			// tâche en lecture seule
			// - la balise disabled
			// - ou pas de balises de programmation (= pas de balises config_path/cron_expr)
			// - ou pas de programmation (= ni balises config_path/cron_expr, ni configuration config_path/cron_expr)
			$isReadOnly = (isset($config->schedule->disabled) || (!isset($config->schedule->config_path) && !isset($config->schedule->cron_expr)) || is_null($expr));

			$item = new Varien_Object();
			$item->setModule($moduleName);
			$item->setJobCode($jobcode);
			$item->setCronExpr($expr);
			$item->setModel($model);
			$item->setStatus($isDisabled);
			$item->setIsReadOnly($isReadOnly);

			if ((($type === 'ro') && $isReadOnly) || (($type === 'rw') && !$isReadOnly) || ($type === 'all'))
				$this->addItem($item);
		}

		usort($this->_items, array($this, 'sort'));
		return $this;
	}

	private function sort($a, $b) {
		return strcmp($a->getJobCode(), $b->getJobCode());
	}
}