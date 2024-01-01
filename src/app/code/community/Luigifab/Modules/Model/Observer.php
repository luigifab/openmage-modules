<?php
/**
 * Created S/22/11/2014
 * Updated D/17/12/2023
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

class Luigifab_Modules_Model_Observer extends Luigifab_Modules_Helper_Data {

	// EVENT admin_system_config_changed_section_modules (adminhtml)
	public function updateConfig() {

		$config = Mage::getModel('core/config_data');
		$config->load('crontab/jobs/modules_send_report/schedule/cron_expr', 'path');

		if (Mage::getStoreConfigFlag('modules/email/enabled')) {

			// hebdomadaire, tous les lundi à 1h00 (hebdomadaire/weekly)
			// minute hour day-of-month month-of-year day-of-week (Dimanche = 0, Lundi = 1...)
			// 0      1    *            *             0|1         => weekly
			$config->setData('value', '0 1 * * '.Mage::getStoreConfig('general/locale/firstday'));
			$config->setData('path', 'crontab/jobs/modules_send_report/schedule/cron_expr');
			$config->save();

			// test email
			if (!empty(Mage::app()->getRequest()->getPost('modules_email_test')))
				Mage::getSingleton('modules/report')->send(null, true);
		}
		else {
			$config->delete();
		}

		Mage::getConfig()->reinit();
	}
}