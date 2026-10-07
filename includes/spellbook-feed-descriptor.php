<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** The callbacks of this plugin's Spellbook settings descriptor (includes/spellbook-settings.json). */
class GFFN_Spellbook_Feed_Descriptor {
	const NATIVE = array(
		'name'                 => 'feed_name',
		'startDate'            => 'start_date',
		'endDate'              => 'end_date',
		'advanceDays'          => 'advance_days',
		'message'              => 'message',
		'disableDefaultStyles' => 'disable_default_styles',
	);

	/**
	 * The saved notice; an empty text is no value. A saved value this review cannot show is returned as it is saved,
	 * so Spellbook keeps it out of the review.
	 */
	public static function read( $resource ) {
		$meta   = $resource['native'];
		$values = array();
		foreach ( self::NATIVE as $key => $native ) {
			if ( ! array_key_exists( $native, $meta ) || $meta[ $native ] === '' && $key !== 'disableDefaultStyles' ) {
				continue;
			}
			$value = $meta[ $native ];
			if ( $key === 'advanceDays' ) {
				$values[ $key ] = Spellbook_Assistant_Adapter_Utils::native_integer( $value, 0, 3660 ) ? (int) $value : $value;
			} elseif ( $key === 'disableDefaultStyles' ) {
				$flag           = Spellbook_Assistant_Adapter_Utils::native_bool( $value );
				$values[ $key ] = $flag === null ? $value : $flag;
			} else {
				$values[ $key ] = $value;
			}
		}
		return $values;
	}

	public static function review( $values, $resource ) {
		return array(
			'effects' => array(
				'Active notices appear above the form and in existing notice shortcodes during their scheduled dates. Dates follow the native site-local clock; the end date includes the native final minute (11:59pm).',
				'Advance days start displaying the message before its start date. Saving does not send notifications, submit the form, or change entries.',
			),
		);
	}

	public static function apply( $meta, $values, $resource ) {
		$create   = empty( $resource['feed'] );
		$activate = isset( $resource['action'] ) && $resource['action'] === 'activate';
		if ( ( $create || $activate ) && array_diff( array( 'name', 'startDate', 'endDate', 'message' ), array_keys( $values ) ) ) {
			return Spellbook_Assistant_Adapter_Utils::error( 'A notice needs a name, start and end dates, and a message before it can be created or activated.' );
		}
		if ( $create ) {
			$meta = array(
				'advance_days'           => '0',
				'disable_default_styles' => '0',
			);
		}
		foreach ( self::NATIVE as $key => $native ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}
			if ( $key === 'name' && trim( $values[ $key ] ) === '' || $key === 'message' && ( trim( $values[ $key ] ) === '' || ! Spellbook_Assistant_Adapter_Utils::allowed_html( $values[ $key ] ) ) ) {
				return Spellbook_Assistant_Adapter_Utils::error( 'Use a nonempty notice name and a message allowed by your Gravity Forms markup permissions.' );
			}
			$meta[ $native ] = $key === 'disableDefaultStyles' ? ( $values[ $key ] ? '1' : '0' ) : (string) $values[ $key ];
		}
		if ( isset( $values['startDate'] ) || isset( $values['endDate'] ) || isset( $values['advanceDays'] ) || $create || $activate ) {
			foreach ( array( 'start_date', 'end_date' ) as $key ) {
				if ( ! isset( $meta[ $key ] ) || ! is_string( $meta[ $key ] ) || ! preg_match( '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/D', $meta[ $key ], $matches ) || ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
					return Spellbook_Assistant_Adapter_Utils::error( 'Use valid absolute YYYY-MM-DD start and end dates. Review recurring or relative dates in native settings.' );
				}
			}
			if ( $meta['start_date'] > $meta['end_date'] ) {
				return Spellbook_Assistant_Adapter_Utils::error( 'The notice start date cannot be after its end date.' );
			}
		}
		return $meta;
	}
}
