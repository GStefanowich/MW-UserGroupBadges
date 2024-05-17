<?php

namespace MediaWiki\Extension\UserGroupBadges;

use Html;
use User;
use Title;
use Config;
use Message;
use HtmlArmor;
use RepoGroup;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\Hook\BeforePageDisplayHook; // Moved in newer versions to 'MediaWiki\Output\Hook\BeforePageDisplayHook'
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Linker\Hook\HtmlPageLinkRendererBeginHook;
use MediaWiki\ResourceLoader\Context;
use OutputPage;

class Hooks implements HtmlPageLinkRendererBeginHook, BeforePageDisplayHook {
	private UserFactory $users;
	private UserGroupManager $groups;
	private UserGroupBadges $badges;

	public function __construct(
	    UserFactory $users,
	    UserGroupManager $groups,
		UserGroupBadges $badges
	) {
		$this -> users = $users;
		$this -> groups = $groups;
		$this -> badges = $badges;
	}

	/**
	 *
	 * @param LinkRenderer     $renderer The mediawiki link renderer
	 * @param LinkTarget       $target   The link target
	 * @param string|HtmlArmor $text     Contents within the <a> tag
	 * @param string[]         $attr     Attributes of the <a> tag
	 * @param $query Associative array of link query parameters
	 * @param $ret The return value if we decide to return 'false' in the function
	 */
	public function onHtmlPageLinkRendererBegin( $renderer, $target, &$text, &$attr, &$query, &$ret ) {
		// Check that we're linking a User
		if ( !$target -> inNamespace( NS_USER ) ) {
			return;
		}

        if ( $target instanceof Title ) {
            if ( $target -> isSubpage() )
                return;
            $text = $target -> getRootText();
        } else {
            $text = $target -> getText();
        }

		// Get the user that is being linked to
		$user = $this -> users -> newFromName( $text );

		// If the user doesn't exist, skip
		if ( !$user || $user -> getId() === 0 ) {
			return;
		}

		// Get the plaintext inner content
		$plain = $text;
		if ( $text instanceof HtmlArmor ) {
			$plain = HtmlArmor::getHtml($text);
		}

		$updated = false;
		$groups = $this->badges->getGroups();

		// Iterate each group that the user is a member of, check if there is a badge defined for that group
		foreach ( $this -> groups -> getUserGroups($user) as $group ) {
			$data = $groups[$group] ?? null;

			// Check if the [MediaWiki:group-[key]-badge] message exists
			if ( $data ) {
				// Create an HTML element for the badge
				$html = Html::rawElement('i', [
					'class' => 'group-badge role-' . $group,
					'title' => $data['title']
				]);

				// Prepend our HTML badge to the existing HTML
				$plain = $html . $plain;

				// We've changed the HTML contents
				$updated = true;
			}
		}

		if ( $updated ) {
			$text = new HtmlArmor( $plain );
		}
	}

	/**
	 * Generate page CSS for displaying badges in links to User pages
	 *
	 * @param OutputPage $out  The current page
	 * @param Skin       $skin The current wiki skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModuleStyles( [
			'ext.usergroupbadges',
		] );
	}
}
