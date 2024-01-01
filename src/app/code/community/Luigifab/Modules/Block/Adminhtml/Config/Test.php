<?php
/**
 * Created M/02/01/2018
 * Updated S/09/12/2023
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

class Luigifab_Modules_Block_Adminhtml_Config_Test extends Mage_Adminhtml_Block_System_Config_Form_Field {

	public function render(Varien_Data_Form_Element_Abstract $element) {
		$element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue()->unsPath();
		return parent::render($element);
	}

	protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
		return sprintf('<input type="checkbox" name="%s" id="%s" />', $element->getHtmlId(), $element->getHtmlId()).' '.
			sprintf('<a href="%s" class="f-right">%s</a>', $this->getUrl('*/modules_index/preview'), $this->__('See a preview (after saving)'));
	}
}