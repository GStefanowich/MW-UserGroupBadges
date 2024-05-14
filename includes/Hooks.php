<?php

namespace UserGroupBadges;

use Html;
use User;
use Config;
use Message;
use HtmlArmor;
use RepoGroup;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\Hook\BeforePageDisplayHook; // Moved in newer versions to 'MediaWiki\Output\Hook\BeforePageDisplayHook'
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Linker\Hook\HtmlPageLinkRendererBeginHook;
use MediaWiki\ResourceLoader\Context;

class Hooks implements HtmlPageLinkRendererBeginHook, BeforePageDisplayHook {
	private $repo;
	private $lang;
	private $groups; // Simple cache of groups so we don't run file checks a bunch

	public function __construct( RepoGroup $repo, Language $lang ) {
		$this -> repo = $repo;
		$this -> lang = $lang;
		$this -> groups = null; // Don't init groups in the constructor, wfMessages aren't allowed yet
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

		// Get the user that is being linked to
		$user = User::newFromName($target -> getText());

		// If the user doesn't exist, skip
		if ( $user -> getId() === 0 ) {
			return;
		}

		// Get the plaintext inner content
		$plain = $text;
		if ($text instanceof HtmlArmor) {
			$plain = HtmlArmor::getHtml($text);
		}

		$updated = false;
		$groups = $this -> groups ??= $this -> getGroups();

		// Iterate each group that the user is a member of, check if there is a badge defined for that group
		foreach ($user -> getGroups() as $group) {
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

		if ($updated) {
			$text = new HtmlArmor($plain);
		}
	}

	/**
	 * Generate page CSS for displaying badges in links to User pages
	 *
	 * @param OutputPage $out  The current page
	 * @param Skin       $skin The current wiki skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$inline = null;
		$groups = $this -> groups ??= $this -> getGroups();

		// Loop through our groups
		foreach($groups as $group => $data) {
			// Set the badge basic styles
			if ( !$inline ) {
				$inline = 'i.group-badge {' . join(';', [
					'display: inline-block',
					'width: 16px',
					'height: 16px',
					'margin-right: 3px',
					'margin-top: -2px',
					'vertical-align: middle',
					'background-size: 16px 16px'
				]) . '}';
			}

			// Append the badge styling
			$inline .= 'i.group-badge.role-' . $group . '{ background-image: url("' . $data['url'] . '") }';
		}

		// If we generated our inline formatting, append it to the page
		if ( $inline ) {
			$out -> addInlineStyle($inline);
		}
	}

	private function getGroups(): array {
		global $wgGroupPermissions; // Get the group names

		$groups = [];
		foreach(array_keys($wgGroupPermissions) as $group) {
			$i18n = wfMessage('group-' . $group . '-badge');

			// Check that something is set for the translation
			if ( $i18n -> exists() ) {
				$path = $this -> substrFilepath($i18n);
				$url  = null;

				if ( str_starts_with( $path, 'data:' ) ) {
					// Allow data paths
					$url = $path;
				} else {
					$image = $this -> repo -> findFile($path);

					// If the file doesn't exist (Only check if it's a LocalFile)
					if ( !$image || ($image instanceof LocalFile && !$image -> exists()) ) {
						continue;
					}

					$url = $image -> getFullUrl();
				}

				$groups[$group] = [
					'title' => wfMessage('group-' . $group) -> plain(),
					'url'   => $url
				];
			}
		}

		return $groups;
	}

	/**
	 * Strip away the "File:" namespace from an Interface Message
	 *
	 * @param  Message $i18n A MessageKey referencing a File location
	 * @return Returns the name of a File
	 */
	private function substrFilepath( Message $i18n ): string {
		$path = $i18n -> plain();
		$ns   = $this -> lang -> getFormattedNsText(NS_FILE);

		// If the given message starts with the "File:" (Translated) namespace, strip it
		if ( str_starts_with($path, $ns . ':' ) ) {
			return substr($path, strlen($ns . ':'));
		}

		return $path;
	}
}

?>
