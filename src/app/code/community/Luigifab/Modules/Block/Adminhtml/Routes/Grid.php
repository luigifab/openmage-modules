<?php
/**
 * Created J/23/11/2023
 * Updated S/25/11/2023
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

class Luigifab_Modules_Block_Adminhtml_Routes_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	public function __construct() {

		parent::__construct();

		$this->setId('modules_routes_grid');

		$this->setUseAjax(false);
		$this->setSaveParametersInSession(false);
		$this->setPagerVisibility(false);
		$this->setFilterVisibility(true);

		$this->setCollection(Mage::getModel('modules/source_routes')->getCollection());
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
			'width'     => '90px',
			'filter'    => false,
			'sortable'  => false,
		]);

		$this->addColumn('route', [
			'header'    => $this->__('Name').' (front name)',
			'index'     => 'route',
			'filter'    => false,
			'sortable'  => false,
		]);

		$this->addColumn('status', [
			'header'    => $this->__('Status'),
			'index'     => 'status',
			'type'      => 'options',
			'options'   => [
				'enabled'  => $this->helper('modules')->_('Enabled'),
				'disabled' => $this->helper('modules')->_('Disabled'),
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
		return ($row->getData('status') == 'disabled') ? 'disabled' : '';
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
}