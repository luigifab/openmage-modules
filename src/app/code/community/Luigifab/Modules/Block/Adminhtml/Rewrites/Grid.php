<?php
/**
 * Created M/22/07/2014
 * Updated S/19/02/2022
 *
 * Copyright 2012-2022 | Fabrice Creuzot (luigifab) <code~luigifab~fr>
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

class Luigifab_Modules_Block_Adminhtml_Rewrites_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_rewrites_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(true);

		$this->setCollection(Mage::getModel('modules/source_rewrites')->getCollection());
	}

	protected function _prepareCollection() {
		//$this->setCollection() dans __construct() pour getCount()
		return parent::_prepareCollection();
	}

	protected function _prepareColumns() {

		$this->addColumn('module', [
			'header'    => $this->__('Module name'),
			'index'     => 'module',
			'filter'    => false,
			'sortable'  => false,
		]);

		$this->addColumn('scope', [
			'header'    => $this->__('Scope'),
			'index'     => 'scope',
			'align'     => 'center',
			'width'     => '90px',
			'filter'    => false,
			'sortable'  => false,
		]);

		$this->addColumn('type', [
			'header'    => $this->__('Type'),
			'index'     => 'type',
			'align'     => 'center',
			'width'     => '150px',
			'filter'    => false,
			'sortable'  => false,
		]);

		$this->addColumn('rewrite_class', [
			'header'    => $this->__('Source').' → '.$this->__('Destination'),
			'index'     => 'rewrite_class',
			'filter'    => false,
			'sortable'  => false,
			'frame_callback' => [$this, 'decorateRewriteClass'],
		]);

		$this->addColumn('status', [
			'header'    => $this->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'options'   => [
				'enabled'  => $this->helper('modules')->_('Enabled'),
				'disabled' => $this->__('Conflict'),
			],
			'width'     => '120px',
			'filter'    => false,
			'sortable'  => false,
			'frame_callback' => [$this, 'decorateStatus'],
		]);

		return parent::_prepareColumns();
	}

	public function getCount() {
		return $this->getCollection()->getSize();
	}


	public function getRowClass($row) {
		return ($row->getData('status') == 'disabled') ? 'conflict' : '';
	}

	public function getRowUrl($row) {
		return false;
	}

	public function canDisplayContainer() {
		return false;
	}

	public function getMessagesBlock() {
		return Mage::getBlockSingleton('adminhtml/template');
	}


	public function decorateStatus($value, $row, $column, $isExport) {
		return $isExport ? $value : sprintf('<span class="modules-status grid-%s">%s</span>', $row->getData('status'), $value);
	}

	public function decorateRewriteClass($value, $row, $column, $isExport) {
		return ($isExport || empty($row->getData('core_class_name'))) ?
			str_replace('app/code/', '', sprintf('%s → %s', $row->getData('core_class'), $row->getData('rewrite_class'))) :
			str_replace(['app/code/', '_Model_', '_Block_', '_Helper_'], ['', '_<b>Model</b>_', '_<b>Block</b>_', '_<b>Helper</b>_'], sprintf(
				'%s → %s <div>%s<br />%s</div>',
				$row->getData('core_class'), $row->getData('rewrite_class'),
				$row->getData('core_class_name'), $row->getData('rewrite_class_name')));
	}
}