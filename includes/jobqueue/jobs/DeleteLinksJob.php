<?php
/**
 * Job to update link tables for pages
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup JobQueue
 */

use MediaWiki\Deferred\LinksUpdate\LinksDeletionUpdate;
use MediaWiki\Deferred\LinksUpdate\LinksUpdate;
use MediaWiki\MediaWikiServices;

/**
 * Job to prune link tables for pages that were deleted
 *
 * Only DataUpdate classes should construct these jobs
 *
 * @ingroup JobQueue
 * @since 1.27
 */
class DeleteLinksJob extends Job {
	public function __construct( Title $title, array $params ) {
		parent::__construct( 'deleteLinks', $title, $params );
		$this->removeDuplicates = true;
	}

	public function run() {
		if ( $this->title === null ) {
			$this->setLastError( "deleteLinks: Invalid title" );
			return false;
		}

		$pageId = $this->params['pageId'];

		// Serialize links updates by page ID so they see each others' changes
		$scopedLock = LinksUpdate::acquirePageLock( wfGetDB( DB_PRIMARY ), $pageId, 'job' );
		if ( $scopedLock === null ) {
			$this->setLastError( 'LinksUpdate already running for this page, try again later.' );
			return false;
		}

		$services = MediaWikiServices::getInstance();
		$wikiPageFactory = $services->getWikiPageFactory();
		if ( $wikiPageFactory->newFromID( $pageId, WikiPage::READ_LATEST ) ) {
			// The page was restored somehow or something went wrong
			$this->setLastError( "deleteLinks: Page #$pageId exists" );
			return false;
		}

		$factory = $services->getDBLoadBalancerFactory();
		$timestamp = $this->params['timestamp'] ?? null;
		$page = $wikiPageFactory->newFromTitle( $this->title ); // title when deleted

		$update = new LinksDeletionUpdate( $page, $pageId, $timestamp );
		$update->setTransactionTicket( $factory->getEmptyTransactionTicket( __METHOD__ ) );
		$update->doUpdate();

		return true;
	}
}
