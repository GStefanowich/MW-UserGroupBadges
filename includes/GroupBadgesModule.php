<?php
namespace MediaWiki\Extension\UserGroupBadges;

use MediaWiki\Extension\ThemeToggle\ExtensionConfig;
use MediaWiki\Extension\ThemeToggle\ThemeAndFeatureRegistry;
use MediaWiki\MediaWikiServices;
use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\FileModule;

class GroupBadgesModule extends FileModule {
    public function getStyles( Context $context ) {
        $styles = parent::getStyles( $context );
        return $styles;
    }

    public function enableModuleContentVersion(): bool {
        return true;
    }
}
