<?php

namespace StingerSoft\Select2FormBundle;

use StingerSoft\Select2FormBundle\Form\Select2ChoiceType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use StingerSoft\Select2FormBundle\Form\Select2AsyncTagType;
use StingerSoft\Select2FormBundle\Form\Select2SyncTagType;
use StingerSoft\Select2FormBundle\Form\Select2EntityType;
use StingerSoft\Select2FormBundle\Form\Select2HierarchicalType;

class BaseTestType extends TypeTestCase {

	protected function setUp() {
		parent::setUp();

		$this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
		$this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \Symfony\Component\Form\Test\FormIntegrationTestCase::getExtensions()
	 */
	protected function getExtensions() {
		return [
			new PreloadedExtension($this->getPreloadedExtensionTypes(), $this->getTypeExtensions()),
		];
	}

	protected function getMockContainer() {
		$mock = $this->getMockBuilder(ContainerInterface::class)->disableOriginalConstructor()->setMethods([
			'get',
		])->getMockForAbstractClass();
		$mock->method('get')->willReturnCallback(function ($serviceName) {
			return null;
		});
		return $mock;
	}

	/**
	 * Init with Entity Form Type and StingerSoftSelect2
	 */
	protected function getPreloadedExtensionTypes() {
		$stingerSoftSelect2ChoiceType = new Select2ChoiceType($this->getMockContainer());
		$stingerSoftSelect2AsyncType = new Select2AsyncTagType($this->getMockContainer());
		$stingerSoftSelect2SyncType = new Select2SyncTagType($this->getMockContainer());
		$stingerSoftSelect2EntityType = new Select2EntityType($this->getMockContainer());
		$stingerSoftSelect2HierarchicalType = new Select2HierarchicalType($this->getMockContainer());

		return [
			$stingerSoftSelect2ChoiceType,
			$stingerSoftSelect2AsyncType,
			$stingerSoftSelect2SyncType,
			$stingerSoftSelect2EntityType,
			$stingerSoftSelect2HierarchicalType,
		];
	}

	protected function getTypeExtensions() {
		return [];
	}
}