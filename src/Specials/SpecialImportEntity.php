<?php

namespace Wikibase\Import\Specials;

use ErrorPageError;
use Html;
use HTMLForm;
use MediaWiki\MediaWikiServices;
use SpecialPage;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Import\EntityImporter;
use Wikibase\Import\EntityImporterFactory;
use Wikibase\Import\LoggerFactory;
use Wikibase\Import\Store\ImportedEntityMappingStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\WikibaseRepo;

class SpecialImportEntity extends SpecialPage {

	/**
	 * @var EntityImporter
	 */
	private $entityIdImporter;

	/**
	 * @var ImportedEntityMappingStore
	 */
	private $entityMappingStore;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	public static function newFromGlobalState() {
		$repo = WikibaseRepo::getDefaultInstance();
		$logger = LoggerFactory::newLogger( 'wikibase-import', /* quiet */ true );
		$entityImporterFactory = new EntityImporterFactory(
			$repo->getStore()->getEntityStore(),
			wfGetLB(),
			$logger,
			MediaWikiServices::getInstance()->getMainConfig()->get( 'WBImportSourceApi' )
		);
		return new self(
			$entityImporterFactory->newEntityImporter(),
			$entityImporterFactory->getImportedEntityMappingStore(),
			$repo->getEntityIdParser(),
			$repo->getEntityTitleLookup()
		);
	}

	/**
	 * @param EntityImporter $entityIdImporter
	 */
	public function __construct(
		EntityImporter $entityIdImporter,
		ImportedEntityMappingStore $entityMappingStore,
		EntityIdParser $idParser,
		EntityTitleLookup $entityTitleLookup
	) {
		parent::__construct( 'ImportEntity' );
		$this->entityIdImporter = $entityIdImporter;
		$this->entityMappingStore = $entityMappingStore;
		$this->idParser = $idParser;
		$this->entityTitleLookup = $entityTitleLookup;
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return string
	 */
	public function getDescription() {
		return $this->msg( 'wikibaseimport-importentity-desc' )->escaped();
	}

	/**
	 * @see SpecialPage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		if ( $this->getContext()->getRequest()->wasPosted() ) {
			$this->doImport();
		} else {
			$this->showPage( $subPage );
		}
	}

	private function doImport() {
		$entityId = $this->getContext()->getRequest()->getText( 'wpEntityId' );
		$importStatements = $this->getContext()->getRequest()->getCheck( 'wpImportStatements' );
		if ( $importStatements ) {
			// TODO fix bug with importStatements and then remove this if clause and enable the checkbox
			throw new \MWException( 'Importing statements is not yet supported!' );
		}
		$this->entityIdImporter->importEntities( [ $entityId ], $importStatements );
		// redirect to imported entity
		$imported = $this->entityMappingStore->getLocalId( $this->idParser->parse( $entityId ) );
		if ( $imported ) {
			$this->getOutput()->redirect(
				$this->entityTitleLookup->getTitleForId( $imported )->getLocalUrl()
			);
		} else {
			throw new ErrorPageError(
				"wikibaseimport-importentity-error-no-local-id-title",
				"wikibaseimport-importentity-error-no-local-id-message"
			);
		}
	}

	/**
	 * Show the special page with explanation and form.
	 *
	 * @param string|null $subPage
	 */
	private function showPage( $subPage ) {
		$this->setHeaders();
		// show explanation
		$this->getOutput()->addHTML(
			Html::rawElement(
				'div',
				[ 'class' => 'wikibaseimport-importentity-explanation' ],
				$this->msg( 'wikibaseimport-importentity-explanation' )->rawParams(
					Html::element(
						'a',
						[ 'href' => $this->getConfig()->get( 'WBImportSourceURL' ) ],
						$this->getConfig()->get( 'WBImportSourceName' )
					)
				)->escaped()
			)
		);
		// show entity ID form
		$formDescription = [];
		$formDescription['EntityId'] = [
			'type' => 'text',
			'section' => 'section',
			'label-message' => [
				// message ID
				'wikibaseimport-importentity-form-entityid-label',
				// message parameters
				$this->getConfig()->get( 'WBImportSourceName' )
			],
			'placeholder' => $this->msg( 'wikibaseimport-importentity-form-entityid-placeholder' )->escaped(),
		];
		if ( is_string( $subPage ) && preg_match( '/(P|Q)[0-9]+/', $subPage ) ) {
			$formDescription['EntityId']['default'] = $subPage;
		}
		$formDescription['ImportStatements'] = [
			'type' => 'check',
			'section' => 'section',
			'label-message' => 'wikibaseimport-importentity-form-importstatements-label',
			'disabled' => true, // TODO remove this once importing statements works
		];
		HTMLForm::factory(
			'ooui',
			$formDescription,
			$this->getContext(),
			'wikibaseimport-importentity-form'
		)
			->setSubmitText( $this->msg( 'wikibaseimport-importentity-form-submit-label' )->escaped() )
			->setSubmitCallback(
				function() {
					return false;
				}
			)
			->setMethod( 'post' )
			->prepareForm()
			->displayForm( false );
	}

}
