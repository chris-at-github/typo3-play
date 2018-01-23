<?php

namespace Cext\Play\Service;

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Json Service Class
 */
class JsonService {

	/**
	 * toJson
	 *
	 * @param mixed $value
	 * @param array $options
	 * @return string
	 */
	public function toJson($value, $options = []) {
		if(gettype($value) === 'object') {
			$value = $this->toArray($value, $options);
		}

//		DebuggerUtility::var_dump($value);

		return json_encode($value);
	}

	/**
	 * toArray
	 *
	 * @param mixed $value
	 * @param array $options
	 * @return array
	 */
	public function toArray($value, $options = []) {
		if($value instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
			$value = $this->extbaseObjectToArray($value, $options);
		}

		if($value instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult) {
			$value = $this->extbaseCollectionToArray($value, $options);
		}

		return $value;
	}

	/**
	 * toJson -> ExtbaseObject
	 *
	 * @param \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object
	 * @param array $options
	 * @return array
	 */
	public function extbaseObjectToArray(\TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject $object, $options) {
		$data = [];
		$properties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($object);

		foreach($properties as $propertyName) {
			if(empty($options) === true || isset($options[$propertyName]) === true || in_array($propertyName, $options) === true) {
				$propertyValue = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, $propertyName);

				if(gettype($propertyValue) === 'array' || gettype($propertyValue) === 'object') {
					if($propertyValue instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity || $propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {

						if(isset($options[$propertyName]) === true && gettype($options[$propertyName]) === 'array') {
							if($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
								$propertyValue = $propertyValue->_loadRealInstance();
							}

							$propertyValue = $this->extbaseObjectToArray($propertyValue, $options[$propertyName]);

						} else {
							$propertyValue = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($propertyValue, 'uid');
						}

					} elseif($propertyValue instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage) {
						$propertyValue = $this->extbaseCollectionToArray($propertyValue, []);
					}
				}

				$data[$propertyName] = $propertyValue;
			}
		}

		return $data;
	}

	/**
	 * toJson -> ExtbaseCollection
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult|\TYPO3\CMS\Extbase\Persistence\ObjectStorage $collection
	 * @param array $options
	 * @return array
	 */
	public function extbaseCollectionToArray($collection, $options) {
		$data = [];

		if(empty($options) === true) {
			foreach($collection as $object) {
				if($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity || $object instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
					$data[] = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, 'uid');
				}
			}
		} else {
			foreach($collection as $object) {
				if($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity || $object instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
					$uid = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, 'uid');
					$data[$uid] = $this->toArray($object, $options);

				} else {
					$data[] = $this->toArray($object, $options);
				}
			}
		}

		return $data;
	}
}