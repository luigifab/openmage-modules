<?php
/**
 * Created V/23/05/2014
 * Updated V/20/05/2022
 *
 * Copyright 2012-2023 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

class Luigifab_Modules_Block_Adminhtml_Config_Help extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	public function render(Varien_Data_Form_Element_Abstract $element) {
		return sprintf('<p class="box">%s %s <span class="right">Stop russian war. <b>🇺🇦 Free Ukraine!</b> | <a href="https://www.%s">%3$s</a> | ⚠ IPv6</span></p>',
			'Luigifab/Modules', $this->helper('modules')->getVersion(), 'luigifab.fr/openmage/modules');
	}
}