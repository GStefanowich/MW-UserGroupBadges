<?php

use MediaWiki\Extension\UserGroupBadges\UserGroupBadges;
use MediaWiki\MediaWikiServices;

return [
    UserGroupBadges::SERVICE_NAME => static function (
        MediaWikiServices $services
    ): UserGroupBadges {
        return new UserGroupBadges(
            $services -> getUserGroupManager(),
            $services -> getRepoGroup()
        );
    },
];
