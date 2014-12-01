<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Repo\View\ItemView;

/**
 * @covers Wikibase\Repo\View\ItemView
 *
 * @group Wikibase
 * @group WikibaseItemView
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 * @group Database
 * @group medium
 */
class ItemViewTest extends EntityViewTest {

	protected function makeEntity( EntityId $id, array $statements = array() ) {
		$item = Item::newEmpty();
		$item->setId( $id );
		$item->setLabel( 'en', "label:$id" );
		$item->setDescription( 'en', "description:$id" );

		foreach ( $statements as $statement ) {
			$item->addClaim( $statement );
		}

		return $item;
	}

	/**
	 * Generates a suitable entity ID based on $n.
	 *
	 * @param int|string $n
	 *
	 * @return EntityId
	 */
	protected function makeEntityId( $n ) {
		return new ItemId( "Q$n");
	}

	public function provideTestGetHtml() {
		$itemView = new ItemView(
			$this->getMockBuilder( 'Wikibase\Repo\View\FingerprintView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMockBuilder( 'Wikibase\Repo\View\ClaimsView' )
				->disableOriginalConstructor()
				->getMock(),
			$this->getMock( 'Language' ),
			array()
		);

		return array(
			array(
				$itemView,
				$this->newEntityRevisionForStatements( array() ),
				array(),
				true,
				'/wb-item/'
			)
		);
	}
}