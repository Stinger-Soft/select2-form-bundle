<?php

namespace StingerSoft\Select2FormBundle\Tests;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

abstract class BaseDoctrineTest extends BaseTest {

	abstract protected function mockEntityManager();

	/**
	 *
	 * @param EntityManagerInterface|MockObject|null $em
	 * @return MockObject|QueryBuilder
	 */
	protected function mockQueryBuilder($em = null) {
		if($em === null) {
			$em = $this->mockEntityManager();
		}
		$qb = $this->getMockBuilder(QueryBuilder::class)->setConstructorArgs([
			$em,
		])->getMock();
		return $qb;
	}

	/**
	 *
	 * @return MockObject|MySqlPlatform
	 */
	protected function mockMysqlPlatform() {
		return $this->getMockBuilder(MySqlPlatform::class)->disableOriginalConstructor()->getMock();
	}
}