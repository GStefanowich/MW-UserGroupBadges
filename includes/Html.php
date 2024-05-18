<?php

namespace MediaWiki\Extension\UserGroupBadges;

class Html {
    private const REGEX = '/data:([a-zA-Z-\/\+\.]*)([a-zA-Z0-9\-\_\;\=\.\+]+)?,(.*)/';

    public static function match( string $input ): ?array {
        $data = [];

        if ( preg_match(self::REGEX, $input, $data) ) {
            return $data;
        }

        return null;
    }

    /**
     * Escape "data:" urls to prevent breaking css styles (Intentionally or not)
     * @param array $data
     * @return string
     */
    public static function encodeDataSource( array $data ): string {
        [ /* Discard full match */, $media, $extra, $html ] = $data;

        // Encode anything in the '$html' match; '$media' only allows (a-zA-Z-\/\+\.) and '$extra' only allows (a-zA-Z0-9\-\_\;\=\.\+)
        return 'data:' . $media . $extra . ',' . self::jsEncodeURI($html);
    }

    /**
     * Mock the Browsers 'encodeURI' method, as PHPs version is a little overzealous
     * @param string $uri
     * @return string
     */
    public static function jsEncodeURI( string $uri ): string {
        // Start by decoding the input, if the input is already encoded (As it should be), we don't want to double-encode
        $decoded = rawurldecode($uri);

        // Url Encode and then replace some escaped back to normal
        return strtr(rawurlencode($decoded), [
            '%2D' => '-',
            '%5F' => '_',
            '%2E' => '.',
            '%21' => '!',
            '%7E' => '~',
            '%2A' => '*',
            '%27' => "'",
            '%28' => '(',
            '%29' => ')',
            '%3B' => ';',
            '%2C' => ',',
            '%2F' => '/',
            '%3F' => '?',
            '%3A' => ':',
            '%40' => '@',
            '%26' => '&',
            '%3D' => '=',
            '%2B' => '+',
            '%24' => '$',
            '%20' => ' '
        ]);
    }
}