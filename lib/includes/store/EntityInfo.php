<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * A wrapper for a simple array structure representing pre-fetched data about entities.
 *
 * The entity info is represented by a nested array structure. On the top level,
 * entity id strings are used as keys that refer to entity "records".
 *
 * Each record is an associative array with at least the fields "id" and "type".
 * Which other fields are present depends on which methods have been called on
 * the EntityInfoBuilder in order to gather information about the entities.
 *
 * The array structure should be compatible with the structure generated by
 * EntitySerializer and related classes. It should be suitable for serialization,
 * and must thus not contain any objects.
 *
 * @see EntityInfoBuilder
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityInfo {

	/**
	 * @var array[]
	 */
	private $info;

	/**
	 * @param array[] $info An array of entity info records.
	 * See the class level documentation for information about the expected structure of this array.
	 */
	public function __construct( array $info ) {
		$this->info = $info;
	}

	/**
	 * Returns the array of entity info records as provided to the constructor.
	 * See the class level documentation for information about the expected structure of this array.
	 *
	 * @return array[]
	 */
	public function asArray() {
		return $this->info;
	}

	/**
	 * @param EntityId $id
	 *
	 * @return bool
	 */
	public function hasEntityInfo( EntityId $id ) {
		$key = $id->getSerialization();

		return isset( $this->info[$key] );
	}

	/**
	 * @param EntityId $id
	 *
	 * @return array An array structure representing information about the given entity.
	 *         If that entity isn't know, the resulting structure will contain only the ID.
	 */
	public function getEntityInfo( EntityId $id ) {
		$key = $id->getSerialization();

		if ( isset( $this->info[$key] ) ) {
			return $this->info[$key];
		} else {
			return array( 'id' => $key );
		}
	}

	/**
	 * @param EntityId $id
	 * @param string $languageCode
	 *
	 * @throws StorageException
	 * @return string|null The entity's label in the given language,
	 *         or null if no such descriptions is known.
	 */
	public function getLabel( EntityId $id, $languageCode ) {
		return $this->getTermValue( $id, 'labels', $languageCode );
	}

	/**
	 * @param EntityId $id
	 * @param string $languageCode
	 *
	 * @throws StorageException
	 * @return string|null The entity's description in the given language,
	 *         or null if no such descriptions is known.
	 */
	public function getDescription( EntityId $id, $languageCode ) {
		return $this->getTermValue( $id, 'descriptions', $languageCode );
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws StorageException
	 * @return string[] The given entity's labels, keyed by language.
	 */
	public function getLabels( EntityId $id ) {
		return $this->getTermValues( $id, 'labels' );
	}

	/**
	 * @param EntityId $id
	 *
	 * @throws StorageException
	 * @return string[] The given entity's descriptions, keyed by language.
	 */
	public function getDescriptions( EntityId $id ) {
		return $this->getTermValues( $id, 'descriptions' );
	}

	/**
	 * @param EntityId $id
	 * @param string $termField The term field (e.g. 'labels' or 'descriptions').
	 * @param string $languageCode
	 *
	 * @throws StorageException
	 * @return string|null The term value, or null if no such term is known.
	 */
	private function getTermValue( EntityId $id, $termField, $languageCode ) {
		$entityInfo = $this->getEntityInfo( $id );

		if ( !isset( $entityInfo[$termField][$languageCode] ) ) {
			return null;
		}

		if ( !isset( $entityInfo[$termField][$languageCode]['value'] ) ) {
			throw new StorageException( 'Term record is missing `value` key (' . $id->getSerialization() . ')' );
		}

		return $entityInfo[$termField][$languageCode]['value'];
	}

	/**
	 * @param EntityId $id
	 * @param string $termField The term field (e.g. 'labels' or 'descriptions').
	 *
	 * @throws StorageException
	 * @return string[] The entity's term values, keyed by language.
	 */
	private function getTermValues( EntityId $id, $termField ) {
		$entityInfo = $this->getEntityInfo( $id );

		if ( !isset( $entityInfo[$termField] ) ) {
			return array();
		}

		$values = array();

		foreach ( $entityInfo[$termField] as $key => $entry ) {
			if ( !isset( $entry['value'] ) ) {
				throw new StorageException( 'Term record is missing `value` key (' . $id->getSerialization() . ')' );
			}

			$values[$key] = $entry['value'];
		}

		return $values;
	}

}