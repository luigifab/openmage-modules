<?php
/**
 * Created V/20/07/2012
 * Updated J/04/02/2021
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

class Luigifab_Modules_Helper_Data extends Mage_Core_Helper_Abstract {

	public function getVersion() {
		return (string) Mage::getConfig()->getModuleConfig('Luigifab_Modules')->version;
	}

	public function _(string $data, ...$values) {
		$text = $this->__(' '.$data, ...$values);
		return ($text[0] == ' ') ? $this->__($data, ...$values) : $text;
	}

	public function escapeEntities($data, bool $quotes = false) {
		return htmlspecialchars($data, $quotes ? ENT_SUBSTITUTE | ENT_COMPAT : ENT_SUBSTITUTE | ENT_NOQUOTES);
	}
}