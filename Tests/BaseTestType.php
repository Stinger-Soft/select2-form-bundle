<?php

namespace StingerSoft\Select2FormBundle\Tests;

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
	 * Init with Entity Form Type and PecSelect2
	 */
	protected function getPreloadedExtensionTypes() {
		$pecSelect2ChoiceType = new Select2ChoiceType($this->getMockContainer());
		$pecSelect2AsyncType = new Select2AsyncTagType($this->getMockContainer());
		$pecSelect2SyncType = new Select2SyncTagType($this->getMockContainer());
		$pecSelect2EntityType = new Select2EntityType($this->getMockContainer());
		$pecSelect2HierarchicalType = new Select2HierarchicalType($this->getMockContainer());

		return [
			$pecSelect2ChoiceType,
			$pecSelect2AsyncType,
			$pecSelect2SyncType,
			$pecSelect2EntityType,
			$pecSelect2HierarchicalType,
		];
	}

	protected function getTypeExtensions() {
		return [];
	}
}