# User Group Badges

This repo is for an Extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki).

This is a very simple extension that is configurable from within the Wiki by modifying interface pages.

## Hooks Used

- `HtmlPageLinkRendererBegin`
- `BeforePageDisplay`

## Adding badges

This extension will add any number of badges before links to a User (A link in the `User` (Language Determinant) namespace), as long as the groups the User belongs to have an assigned badge.

MediaWiki uses the following pages to declare details about a User Group:

- `MediaWiki:Group-${group}`: Name of the group
- `MediaWiki:Group-${group}-member`: Name of a member of the group
- `MediaWiki:Grouppage:${group}` Name of the group page

To give a Group a badge, simply define the badge on the following page:
- `MediaWiki:Group:${group}-badge` A resource that should be used as the badge

A group badge can be
- A data tag `data:...` (Eg `data:image/svg+xml;charset=utf-8,...`)
- A wiki File (With or without the `File:` namespace)

# Notes

- The extension styling makes no attempts to keep badges and names together (Eg; no `white-space:nowrap`)