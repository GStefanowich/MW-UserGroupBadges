<?php

namespace MediaWiki\Extension\UserGroupBadges;

use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;

class GroupBadgesModule extends FileModule {
    public function getStyles( Context $context ) {
        $styles = parent::getStyles( $context );

        /** @var UserGroupBadges */
        $badges = MediaWikiServices::getInstance() -> getService( UserGroupBadges::SERVICE_NAME );

		// Loop through our groups
        $badgeCss = [];
		foreach ( $badges -> getGroups() as $group => $data ) {
			$badgeCss[] = 'i.group-badge.role-' . $group . '{ background-image: url("' . $data['url'] . '") }';
		}

        // Add our generated CSS to the bundle (for all media types).
		$styles['all'] = ( $styles['all'] ?? '' ) . PHP_EOL . implode( '', $badgeCss );

        return $styles;
    }

    public function enableModuleContentVersion(): bool {
        return true;
    }
}
