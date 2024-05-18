<?php

namespace MediaWiki\Extension\UserGroupBadges;

use Title;
use HtmlArmor;
use RepoGroup;
use LocalFile;
use MediaWiki\User\UserGroupManager;

class UserGroupBadges {
    public const SERVICE_NAME = 'ExtUserGroupBadges';

	private UserGroupManager $groups;
	private RepoGroup $files;
	private ?array $cache; // Simple cache of groups so we don't run file checks a bunch

	public function __construct(
	    UserGroupManager $groups,
	    RepoGroup $files
	) {
		$this -> groups = $groups;
		$this -> files = $files;
		$this -> cache = null; // Don't initialise just yet, there's a possibility the request won't need this
	}

    public function getGroups(): array {
        if ( $this -> cache !== null ) {
            return $this -> cache;
        }

		$this -> cache = [];

		foreach( $this -> groups -> listAllGroups() as $group ) {
			$url = $this -> getBadgeUrl( $group );
			if ( $url !== null ) {
				$this -> cache[$group] = [
					'title' => wfMessage( 'group-' . $group ) -> inContentLanguage() -> plain(),
					'url'   => $url
				];
			}
		}

		return $this -> cache;
	}

    private function getBadgeUrl( string $group ): ?string {
        $i18n = wfMessage( 'group-' . $group . '-badge' ) -> inContentLanguage();

        // Check that something is set for the translation
        if ( $i18n -> exists() ) {
            $plain = $i18n -> plain();

            // Allow data paths
            if ( $data = Html::match($plain) ) {
                return Html::encodeDataSource( $data );
            }

            $path = $this -> fileNameFromTitle( $plain );
            $image = $this -> files -> findFile( $path );

            // If the file doesn't exist (Only check if it's a LocalFile)
            if ( $image && ( !$image instanceof LocalFile || $image -> exists() ) ) {
                return $image -> getFullUrl();
            }
        }

        return null;
    }

	/**
	 * Strip away the "File:" namespace from an Interface Message
	 *
	 * @param  string $plain A string referencing a File location
	 * @return string Returns the name of a File
	 */
	private function fileNameFromTitle(string $plain): string {
		$title = Title::newFromText($plain, NS_FILE);
		return $title ? $title -> getText() : $plain;
	}
}
