<?php
/**
 * Created V/20/07/2012
 * Updated D/27/04/2014
 * Version 5
 *
 * Copyright 2012-2014 | Fabrice Creuzot (luigifab) <code~luigifab~info>
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

class Luigifab_Modules_Block_Adminhtml_List extends Mage_Adminhtml_Block_Abstract implements Varien_Data_Form_Element_Renderer_Interface {

	protected $_template = 'luigifab/modules/list.phtml';

	public function render(Varien_Data_Form_Element_Abstract $element) {
		return '<tr><td>'.$this->toHtml().'</td></tr>';
	}

	public function getCacheLifetime() {
		return null;
	}
}