<?php

namespace MediaWiki\Extension\UserGroupBadges;

use Html;
use Skin;
use Title;
use HtmlArmor;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;
use MediaWiki\Hook\BeforePageDisplayHook; // Moved in newer versions to 'MediaWiki\Output\Hook\BeforePageDisplayHook'
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Linker\Hook\HtmlPageLinkRendererBeginHook;
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
	 * @param LinkRenderer     $linkRenderer The mediawiki link renderer
	 * @param LinkTarget       $target   The link target
	 * @param string|HtmlArmor $text     Contents within the <a> tag
	 * @param string[]         $customAttribs     Attributes of the <a> tag
	 * @param array            $query    Associative array of link query parameters
	 * @param string           &$ret     The return value if we decide to return 'false' in the function
	 */
	public function onHtmlPageLinkRendererBegin( $linkRenderer, $target, &$text, &$customAttribs, &$query, &$ret ) {
		if (
            // Check that we're linking a User
		    !$target -> inNamespace( NS_USER )

		    // Has override text, eg; [[User:TheElm|Override]],
		    //   prevents some things like edit links having badges, or trying to spoof the user with another users badges [[User:TheElm|Bob]]
		    || self::userLinkHasCustomDisplayText( $target, $text, $customAttribs )
		) {
			return;
		}

        if ( $target instanceof Title ) {

            if ( /* Links to subpage */ $target -> isSubpage() )
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
		$groups = $this -> badges -> getGroups();

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
		$out -> addModuleStyles([
			'ext.usergroupbadges.styles',
		]);
	}

    /**
     * Check if the link to a Userpage contains an override, eg; [[User:TheElm|Override]]
     *   Also checks for various UI links to the User page such as "edit" links, where the text is simply "edit"
     * 
     * @param LinkTarget            $link    The User that is being linked to. Contains link Fragments, Subpage information, etc
     * @param string|HtmlArmor|null $text    The text for the LinkTarget, may be the same as the LinkTarget, or it may be an override
     * @param array                 $attribs An associate array of HTML attributes
     * @return bool
     */
    private static function userLinkHasCustomDisplayText( LinkTarget $link, string|HtmlArmor|null $text, array $attribs ): bool {
        $display = $text instanceof HtmlArmor ? HtmlArmor::getHtml( $text ) : $text;
        $target  = $link instanceof Title ? $link -> getFullText() : $link -> getText();

        return $target !== $display && !self::arrayContainsClass( $attribs, 'mw-userlink' );
    }

    /**
     * Checks if the given array contains a key named "class" and the class contains $class
     * 
     * @param ?array $needle An associative array that may contain the key 'class'
     * @param string $class  A CSS class to search the array for
     * @return bool If the associative array 'class' value contains the $class
     */
	private static function arrayContainsClass( ?array $needle, string $class ): bool {
        return $needle !== null && str_contains( $needle['class'] ?? '', $class );
    }
}
