<?php
/**
 * Created S/22/11/2014
 * Updated S/22/11/2014
 * Version 1
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

class Luigifab_Modules_Model_Source_Channel extends Luigifab_Modules_Helper_Data {

	public function toOptionArray() {

		return array(
			array('value' => 'alpha',  'label' => $this->__('Alpha')),
			array('value' => 'beta',   'label' => $this->__('Beta')),
			array('value' => 'stable', 'label' => $this->__('Stable'))
		);
	}
}