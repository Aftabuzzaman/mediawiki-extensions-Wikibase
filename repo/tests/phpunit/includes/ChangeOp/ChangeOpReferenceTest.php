<?php

namespace Wikibase\Test;

use DataValues\NumberValue;
use DataValues\StringValue;
use InvalidArgumentException;
use Wikibase\ChangeOp\ChangeOpException;
use Wikibase\ChangeOp\ChangeOpReference;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\Statement;

/**
 * @covers Wikibase\ChangeOp\ChangeOpReference
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group ChangeOp
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Daniel Kinzler
 */
class ChangeOpReferenceTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ChangeOpTestMockProvider
	 */
	private $mockProvider;

	/**
	 * @param string|null $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct( $name = null, array $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->mockProvider = new ChangeOpTestMockProvider( $this );
	}

	public function invalidArgumentProvider() {
		$item = new Item( new ItemId( 'Q42' ) );

		$guidGenerator = new GuidGenerator();
		$guid = $guidGenerator->newGuid( $item->getId() );
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 7201010, new StringValue( 'o_O' ) );
		$validReference = new Reference( $snaks );
		$validReferenceHash = $validReference->getHash();

		return [
			[ 123, $validReference, $validReferenceHash ],
			[ '', $validReference, $validReferenceHash ],
			[ $guid, $validReference, 123 ],
			[ $guid, $validReference, $validReferenceHash, 'string' ],
		];
	}

	/**
	 * @dataProvider invalidArgumentProvider
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidConstruct(
		$guid,
		Reference $reference,
		$referenceHash,
		$index = null
	) {
		new ChangeOpReference(
			$guid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator(),
			$index
		);
	}

	public function changeOpAddProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );

		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$newReference,
			'',
			$this->mockProvider->getMockSnakValidator()
		);
		$referenceHash = $newReference->getHash();

		return [
			[ $item, $changeOp, $referenceHash ],
		];
	}

	/**
	 * @dataProvider changeOpAddProvider
	 */
	public function testApplyAddNewReference(
		Item $item,
		ChangeOpReference $changeOp,
		$referenceHash
	) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), 'Reference not found' );
	}

	public function changeOpAddProviderWithIndex() {
		$snak = new PropertyNoValueSnak( 1 );
		$args = array();

		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		$references = array(
			new Reference( new SnakList( array( new PropertyNoValueSnak( 1 ) ) ) ),
			new Reference( new SnakList( array( new PropertyNoValueSnak( 2 ) ) ) ),
		);

		$referenceList = $statement->getReferences();
		$referenceList->addReference( $references[0] );
		$referenceList->addReference( $references[1] );

		$newReference = new Reference( new SnakList( array( new PropertyNoValueSnak( 3 ) ) ) );
		$newReferenceIndex = 1;

		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$newReference,
			'',
			$this->mockProvider->getMockSnakValidator(),
			$newReferenceIndex
		);

		$args[] = array( $item, $changeOp, $newReference, $newReferenceIndex );

		return $args;
	}

	/**
	 * @dataProvider changeOpAddProviderWithIndex
	 */
	public function testApplyAddNewReferenceWithIndex(
		Item $item,
		ChangeOpReference $changeOp,
		Reference $newReference,
		$expectedIndex
	) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertEquals( $expectedIndex, $references->indexOf( $newReference ) );
	}

	public function changeOpSetProvider() {
		$snak = new PropertyValueSnak( 2754236, new StringValue( 'test' ) );
		$args = array();

		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'newQualifier' ) );
		$newReference = new Reference( $snaks );
		$statement->getReferences()->addReference( $newReference );
		$referenceHash = $newReference->getHash();
		$snaks = new SnakList();
		$snaks[] = new PropertyValueSnak( 78462378, new StringValue( 'changedQualifier' ) );
		$changedReference = new Reference( $snaks );
		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$changedReference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator()
		);
		$args[] = array( $item, $changeOp, $changedReference->getHash() );

		// Just change a reference's index:
		$item = $this->newItem( $snak );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );

		/** @var Reference[] $references */
		$references = array(
			new Reference( new SnakList( array( new PropertyNoValueSnak( 1 ) ) ) ),
			new Reference( new SnakList( array( new PropertyNoValueSnak( 2 ) ) ) ),
		);

		$referenceList = $statement->getReferences();
		$referenceList->addReference( $references[0] );
		$referenceList->addReference( $references[1] );

		$changeOp = new ChangeOpReference(
			$statement->getGuid(),
			$references[1],
			$references[1]->getHash(),
			$this->mockProvider->getMockSnakValidator(),
			0
		);
		$args[] = array( $item, $changeOp, $references[1]->getHash() );

		return $args;
	}

	/**
	 * @dataProvider changeOpSetProvider
	 */
	public function testApplySetReference(
		Item $item,
		ChangeOpReference $changeOp,
		$referenceHash
	) {
		$changeOp->apply( $item );
		$statements = $item->getStatements()->toArray();
		/** @var Statement $statement */
		$statement = reset( $statements );
		$references = $statement->getReferences();
		$this->assertTrue( $references->hasReferenceHash( $referenceHash ), 'Reference not found' );
	}

	/**
	 * @param Snak $snak
	 *
	 * @return Item
	 */
	private function newItem( Snak $snak ) {
		$item = new Item( new ItemId( 'Q123' ) );

		$item->getStatements()->addNewStatement(
			$snak,
			null,
			null,
			$item->getId()->getSerialization() . '$D8494TYA-25E4-4334-AG03-A3290BCT9CQP'
		);

		return $item;
	}

	public function provideApplyInvalid() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$goodGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );
		$badGuid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old reference" ) );
		$oldReference = new Reference( new SnakList( array( $oldSnak ) ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( array( $oldSnak ) );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $goodGuid );

		$goodSnak = new PropertyValueSnak( $p11, new StringValue( 'good' ) );

		$goodReference = new Reference( new SnakList( array( $goodSnak ) ) );

		$refHash = $oldReference->getHash();
		$badRefHash = sha1( 'baosdfhasdfj' );

		return [
			'malformed statement guid' => [ $item, 'NotAGuid', $goodReference, '' ],
			'unknown statement guid' => [ $item, $badGuid, $goodReference, $refHash ],
			'unknown reference hash' => [ $item, $goodGuid, $goodReference, $badRefHash ],
		];
	}

	/**
	 * @dataProvider provideApplyInvalid
	 */
	public function testApplyInvalid(
		EntityDocument $entity,
		$guid,
		Reference $reference,
		$referenceHash
	) {
		$this->setExpectedException( ChangeOpException::class );

		$changeOpReference = new ChangeOpReference(
			$guid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$changeOpReference->apply( $entity );
	}

	public function provideValidate() {
		$p11 = new PropertyId( 'P11' );
		$q17 = new ItemId( 'Q17' );

		$item = new Item( $q17 );
		$guid = $this->mockProvider->getGuidGenerator()->newGuid( $q17 );

		$oldSnak = new PropertyValueSnak( $p11, new StringValue( "old reference" ) );
		$oldReference = new Reference( new SnakList( array( $oldSnak ) ) );

		$snak = new PropertyNoValueSnak( $p11 );
		$qualifiers = new SnakList( array( $oldSnak ) );
		$item->getStatements()->addNewStatement( $snak, $qualifiers, null, $guid );

		//NOTE: the mock validator will consider the string "INVALID" to be invalid.
		$badSnak = new PropertyValueSnak( $p11, new StringValue( 'INVALID' ) );
		$brokenSnak = new PropertyValueSnak( $p11, new NumberValue( 23 ) );

		$badReference = new Reference( new SnakList( array( $badSnak ) ) );
		$brokenReference = new Reference( new SnakList( array( $brokenSnak ) ) );

		$refHash = $oldReference->getHash();

		return [
			'invalid snak value' => [ $item, $guid, $badReference, '' ],
			'invalid snak value type' => [ $item, $guid, $brokenReference, $refHash ],
		];
	}

	/**
	 * @dataProvider provideValidate
	 */
	public function testValidate(
		EntityDocument $entity,
		$guid,
		Reference $reference,
		$referenceHash
	) {
		$changeOpReference = new ChangeOpReference(
			$guid,
			$reference,
			$referenceHash,
			$this->mockProvider->getMockSnakValidator()
		);

		$result = $changeOpReference->validate( $entity );
		$this->assertFalse( $result->isValid(), 'isValid()' );
	}

}
