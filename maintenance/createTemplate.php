<?php

namespace DPL\Maintenance;

use CommentStoreComment;
use LoggedUpdateMaintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use Title;
use User;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

/*
 * Creates the DPL template when updating.
 */
class CreateTemplate extends LoggedUpdateMaintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Handle inserting DPL\'s necessary template for content inclusion.' );
	}

	/**
	 * Get the unique update key for this logged update.
	 *
	 * @return string
	 */
	protected function getUpdateKey() {
		return 'dynamic-page-list-create-template';
	}

	/**
	 * Message to show that the update was done already and was just skipped
	 *
	 * @return string
	 */
	protected function updateSkippedMessage() {
		return 'Template already created.';
	}

	/**
	 * Handle inserting DPL's necessary template for content inclusion.
	 *
	 * @return bool
	 */
	protected function doDBUpdates() {
		$title = Title::newFromText( 'Template:Extension DPL' );

		// Make sure template does not already exist
		if ( !$title->exists() ) {
			$services = MediaWikiServices::getInstance();
			// MW 1.36+
			if ( method_exists( $services, 'getWikiPageFactory' ) ) {
				$wikiPageFactory = $services->getWikiPageFactory();
				$page = $wikiPageFactory->newFromTitle( $title );
			}
			else {
				$page = \WikiPage::factory( $title );
			}
			$updater = $page->newPageUpdater( User::newSystemUser( 'DynamicPageList3 extension' ) );
			$content = $page->getContentHandler()->makeContent( '<noinclude>This page was automatically created. It serves as an anchor page for all \'\'\'[[Special:WhatLinksHere/Template:Extension_DPL|invocations]]\'\'\' of [https://www.mediawiki.org/wiki/Special:MyLanguage/Extension:DynamicPageList3 Extension:DynamicPageList3].</noinclude>', $title );
			$updater->setContent( SlotRecord::MAIN, $content );
			$comment = CommentStoreComment::newUnsavedComment( 'Autogenerated DPL\'s necessary template for content inclusion' );

			$updater->saveRevision(
				$comment,
				EDIT_NEW | EDIT_FORCE_BOT
			);
		}

		return true;
	}
}

$maintClass = CreateTemplate::class;
require_once RUN_MAINTENANCE_IF_MAIN;
