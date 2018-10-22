<?php

use ILIAS\GlobalScreen\Collector\MainMenu\ItemInformation;
use ILIAS\GlobalScreen\Collector\MainMenu\TypeHandler;
use ILIAS\GlobalScreen\Collector\StorageFacade;
use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\MainMenu\hasTitle;
use ILIAS\GlobalScreen\MainMenu\isChild;
use ILIAS\GlobalScreen\MainMenu\isItem;
use ILIAS\GlobalScreen\MainMenu\isTopItem;

/**
 * Class ilMMItemInformation
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
class ilMMItemInformation implements ItemInformation {

	/**
	 * @var array
	 */
	private $translations = [];
	/**
	 * @var array
	 */
	private $items = [];
	/**
	 * @var StorageFacade
	 */
	private $storage;


	/**
	 * ilMMItemInformation constructor.
	 *
	 * @param StorageFacade $storage
	 */
	public function __construct(StorageFacade $storage) {
		$this->storage = $storage;
		$this->items = ilMMItemStorage::getArray('identification');
		$this->translations = ilMMItemTranslationStorage::getArray('id', 'translation');
	}


	/**
	 * @inheritDoc
	 */
	public function translateItemForUser(hasTitle $item): hasTitle {
		/**
		 * @var $item isItem
		 */
		global $DIC;
		static $usr_language_key;
		if (!$usr_language_key) {
			$usr_language_key = $DIC->language()->getUserLanguage() ? $DIC->language()->getUserLanguage() : $DIC->language()->getDefaultLanguage();
		}

		if ($item instanceof hasTitle && $this->translations["{$item->getProviderIdentification()->serialize()}|$usr_language_key"]) {
			$item = $item->withTitle((string)$this->translations["{$item->getProviderIdentification()->serialize()}|$usr_language_key"]);
		}

		return $item;
	}


	/**
	 * @inheritDoc
	 */
	public function getPositionOfSubItem(isChild $child): int {
		$position = $this->getPosition($child);

		return $position;
	}


	/**
	 * @inheritDoc
	 */
	public function getPositionOfTopItem(isTopItem $top_item): int {
		return $this->getPosition($top_item);
	}


	private function getPosition(isItem $item): int {
		if (isset($this->items[$item->getProviderIdentification()->serialize()]['position'])) {
			return (int)$this->items[$item->getProviderIdentification()->serialize()]['position'];
		}

		return 99;
	}


	/**
	 * @inheritDoc
	 */
	public function isItemActive(isItem $item): bool {
		$serialize = $item->getProviderIdentification()->serialize();
		if (isset($this->items[$serialize]['active'])) {
			return $this->items[$serialize]['active'] === "1";
		}

		return false;
	}


	/**
	 * @inheritDoc
	 */
	public function getParent(isChild $item): IdentificationInterface {
		global $DIC;
		$parent_string = $item->getProviderIdentification()->serialize();
		if (isset($this->items[$parent_string]['parent_identification'])) {
			return $DIC->globalScreen()->identification()->fromSerializedIdentification($this->items[$parent_string]['parent_identification']);
		}

		return $item->getParent();
	}


	/**
	 * @inheritDoc
	 */
	public function getTypeHandlerForType(isItem $item): TypeHandler {
		switch (true) {
			case ($item instanceof \ILIAS\GlobalScreen\MainMenu\Item\Link):
			case ($item instanceof \ILIAS\GlobalScreen\MainMenu\TopItem\TopLinkItem):
			case ($item instanceof \ILIAS\GlobalScreen\MainMenu\TopItem\TopParentItem):
				return new ilMMTypeHandlerLink();
			default:
				throw new LogicException("No typehandler found");
		}
	}
}
