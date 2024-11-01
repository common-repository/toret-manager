<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Toret_Manager_DateTime extends DateTime{

    /**
     * UTC offset in seconds.
     *
     * @var int
     */
    protected int $utc_offset = 0;

    /**
     * Output an ISO 8601 date string in local (WordPress) timezone.
     *
     * @return string
     */
    public function __toString() {
        return $this->format( DATE_ATOM );
    }

    /**
     * Set UTC offset - this is a fixed offset instead of a timezone.
     *
     * @param int $offset
     */
    public function set_utc_offset( $offset ) {
        $this->utc_offset = intval( $offset );
    }

    /**
     * Get UTC offset if set, or default to the DateTime object's offset.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function getOffset() {
        return $this->utc_offset ?: parent::getOffset();
    }

    /**
     * Set timezone.
     *
     * @param DateTimeZone $timezone
     */
    #[\ReturnTypeWillChange]
    public function setTimezone( $timezone ) {
        $this->utc_offset = 0;
        return parent::setTimezone( $timezone );
    }

    /**
     * Missing in PHP 5.2 so just here so it can be supported consistently.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function getTimestamp() {
        return method_exists( 'DateTime', 'getTimestamp' ) ? parent::getTimestamp() : $this->format( 'U' );
    }

    /**
     * Get the timestamp with the WordPress timezone offset added or subtracted.
     *
     * @return int
     */
    public function getOffsetTimestamp() {
        return $this->getTimestamp() + $this->getOffset();
    }

    /**
     * Format a date based on the offset timestamp.
     *
     * @param string $format
     * @return false|string
     */
    public function date(string $format ) {
        return gmdate( $format, $this->getOffsetTimestamp() );
    }

    /**
     * Return a localised date based on offset timestamp. Wrapper for date_i18n function.
     *
     * @param string $format
     * @return string
     */
    public function date_i18n(string $format = 'Y-m-d' ) {
        return date_i18n( $format, $this->getOffsetTimestamp() );
    }

    /**
     * Format a date for the API.
     *
     * @param mixed $date
     * @param bool $utc
     * @return false|string|null
     * @throws Exception
     */
    static function format_date_for_api($date, bool $utc = true ) {
        if ( is_numeric( $date ) ) {
            $date = new Toret_Manager_DateTime( "@$date", new DateTimeZone( 'UTC' ) );
            $date->setTimezone( new DateTimeZone( self::timezone_string() ) );
        } elseif ( is_string( $date ) ) {
            $date = new Toret_Manager_DateTime( $date, new DateTimeZone( 'UTC' ) );
            $date->setTimezone( new DateTimeZone(  self::timezone_string() ) );
        }


        if ( ! is_a( $date, 'Toret_Manager_DateTime' ) && ! is_a( $date, 'WC_DateTime' )) {
            return null;
        }

        // Get timestamp before changing timezone to UTC.
        return gmdate( 'Y-m-d\TH:i:s', $utc ? $date->getTimestamp() : $date->getOffsetTimestamp() );
    }

    /**
     * Get timezone string
     *
     * @return mixed|string
     */
    private static function timezone_string() {
        // Added in WordPress 5.3 Ref https://developer.wordpress.org/reference/functions/wp_timezone_string/.
        if ( function_exists( 'wp_timezone_string' ) ) {
            return wp_timezone_string();
        }

        // If site timezone string exists, return it.
        $timezone = get_option( 'timezone_string' );
        if ( $timezone ) {
            return $timezone;
        }

        // Get UTC offset, if it isn't set then return UTC.
        $utc_offset = floatval( get_option( 'gmt_offset', 0 ) );
        if ( ! is_numeric( $utc_offset ) || 0.0 === $utc_offset ) {
            return 'UTC';
        }

        // Adjust UTC offset from hours to seconds.
        $utc_offset = (int) ( $utc_offset * 3600 );

        // Attempt to guess the timezone string from the UTC offset.
        $timezone = timezone_name_from_abbr( '', $utc_offset );
        if ( $timezone ) {
            return $timezone;
        }

        // Last try, guess timezone string manually.
        foreach ( timezone_abbreviations_list() as $abbr ) {
            foreach ( $abbr as $city ) {
                // WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone.
                if ( (bool) date( 'I' ) === (bool) $city['dst'] && $city['timezone_id'] && intval( $city['offset'] ) === $utc_offset ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                    return $city['timezone_id'];
                }
            }
        }

        // Fallback to UTC.
        return 'UTC';
    }

}
