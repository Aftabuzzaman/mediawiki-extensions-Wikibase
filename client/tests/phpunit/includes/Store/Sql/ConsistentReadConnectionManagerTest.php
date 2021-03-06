<?php

namespace Wikibase\Client\Tests\Store\Sql;

use IDatabase;
use LoadBalancer;
use PHPUnit_Framework_MockObject_MockObject;
use Wikibase\Client\Store\Sql\ConsistentReadConnectionManager;

/**
 * @covers Wikibase\Client\Store\Sql\ConsistentReadConnectionManager
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @license GPL-2.0+
 * @author DanielKinzler
 */
class ConsistentReadConnectionManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return IDatabase|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getIDatabaseMock() {
		return $this->getMock( IDatabase::class );
	}

	/**
	 * @return LoadBalancer|PHPUnit_Framework_MockObject_MockObject
	 */
	private function getLoadBalancerMock() {
		$lb = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();

		return $lb;
	}

	public function testGetReadConnection() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_SLAVE )
			->will( $this->returnValue( $database ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$actual = $manager->getReadConnection();

		$this->assertSame( $database, $actual );
	}

	public function testGetWriteConnection() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $database ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$actual = $manager->getWriteConnection();

		$this->assertSame( $database, $actual );
	}

	public function testForceMaster() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $database ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->forceMaster();
		$manager->getReadConnection();
	}

	public function testReleaseConnection() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $database )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->releaseConnection( $database );
	}

	public function testBeginAtomicSection() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->exactly( 2 ) )
			->method( 'getConnection' )
			->with( DB_MASTER )
			->will( $this->returnValue( $database ) );

		$database->expects( $this->once() )
			->method( 'startAtomic' )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->beginAtomicSection( 'TEST' );

		// Should also ask for a DB_MASTER connection.
		// This is asserted by the $lb mock.
		$manager->getReadConnection();
	}

	public function testCommitAtomicSection() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $database )
			->will( $this->returnValue( null ) );

		$database->expects( $this->once() )
			->method( 'endAtomic' )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->commitAtomicSection( $database, 'TEST' );
	}

	public function testRollbackAtomicSection() {
		$database = $this->getIDatabaseMock();
		$lb = $this->getLoadBalancerMock();

		$lb->expects( $this->once() )
			->method( 'reuseConnection' )
			->with( $database )
			->will( $this->returnValue( null ) );

		$database->expects( $this->once() )
			->method( 'rollback' )
			->will( $this->returnValue( null ) );

		$manager = new ConsistentReadConnectionManager( $lb );
		$manager->rollbackAtomicSection( $database, 'TEST' );
	}

}
